<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\HtmlPurifier;

class Asset extends AbstractBlockLayout
{
    use CommonTrait;

    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/asset';

    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    public function __construct(
        HtmlPurifier $htmlPurifier
    ) {
        $this->htmlPurifier = $htmlPurifier;
    }

    public function getLabel()
    {
        return 'Asset'; // @translate
    }

    public function prepareForm(PhpRenderer $view): void
    {
        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->appendStylesheet($assetUrl('css/asset-form.css', 'Omeka'));
        $view->headScript()
            ->appendFile($assetUrl('js/asset-form.js', 'Omeka'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('js/block-plus.js', 'BlockPlus'), 'text/javascript', ['defer' => 'defer']);
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = $block->getData();

        // Normalize values and purify html.
        $defaultAttachment = [
            'id' => '',
            'page' => '',
            'alt_link_title' => '',
            'caption' => '',
            'class' => '',
            'url' => '',
        ];
        $data['attachments'] = [];
        foreach ($data as &$dataValue) {
            if (is_array($dataValue) && array_key_exists('id', $dataValue)) {
                $dataValue = array_intersect_key($dataValue, $defaultAttachment) + $defaultAttachment;
                $dataValue = array_map('trim', array_map('strval', $dataValue));
                if ($dataValue['caption']) {
                    $dataValue['caption'] = $this->fixEndOfLine($this->htmlPurifier->purify($dataValue['caption']));
                }
                // Stricter than w3c standard.
                $dataValue['class'] = preg_replace('/[^A-Za-z0-9_ -]/', '', $dataValue['class']);
                // To be compatible with upstream storage and default templates,
                // assets are stored on root too. They are skipped in old
                // specific templates.
                $data['attachments'][] = $dataValue;
            }
        }
        unset($dataValue);

        $block->setData($data);
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['asset'];
        $fieldset = $formElementManager->get(\BlockPlus\Form\AssetFieldset::class);

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            if ($key === 'attachments') {
                $value = json_encode($value);
            }
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset->populateValues($dataForm);

        return $this->adminForm($view, $site, $fieldset, $data, $block);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $block->data();

        $data['attachments'] = $this->prepareAssetAttachments($view, $data ?: []);

        // For compatibility with old themees templates, key "assets" is kept.
        $vars = $data;
        $vars['assets'] = $data['attachments'];
        unset($vars['template']);

        $template = $block->dataValue('template', self::PARTIAL_NAME);
        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }

    public function prepareAssetAttachments(PhpRenderer $view, array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $api = $view->api();

        // Check if data are upstream one (without key "attachments").
        if (!array_key_exists('attachments', $data)) {
            $data['attachments'] = [];
            foreach ($data as $value) {
                if (is_array($value) && isset($value['id'])) {
                    $data['attachments'][] = $value;
                }
            }
        }

        // In new upstream version, the asset is not required, neither any data.
        // In upstream version, exception are not caught.
        foreach ($data['attachments'] as &$assetData) {
            $assetData['asset'] = null;
            if (!empty($assetData['id'])) {
                try {
                    $assetData['asset'] = $api->read('assets', $assetData['id'])->getContent();
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                    // Skip.
                }
            }
            unset($assetData['id']);
            try {
                $assetData['page'] = empty($assetData['page'])
                    ? null
                    : $api->read('site_pages', $assetData['page'])->getContent();
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
                $assetData['page'] = null;
            }
            // For compatibility with old themes templates, the key "title" and "url" are kept.
            $assetData['title'] = (string) $assetData['alt_link_title'];
            $assetData['url'] = empty($assetData['url']) ? null : $assetData['url'];
        }

        return $data['attachments'];
    }

    /**
     * @todo Use a standard Laminas form. See previous version.
     */
    protected function adminForm(
        PhpRenderer $view,
        SiteRepresentation $site,
        \BlockPlus\Form\AssetFieldset $fieldset,
        array $data,
        ?SitePageBlockRepresentation $block = null
    ) {
        $plugins = $view->getHelperPluginManager();
        $url = $plugins->get('url');
        $escape = $plugins->get('escapeHtml');
        $translate = $plugins->get('translate');
        $hyperlink = $plugins->get('hyperlink');
        $formRow = $plugins->get('formRow');
        $thumbnail = $plugins->get('thumbnail');

        $strings = [];
        $strings['openAssetOptions'] = $hyperlink('', '#', ['class' => 'asset-options-configure o-icon-configure button', 'title' => $translate('Open asset options')]); // @translate
        $strings['deleteAttachment'] = $hyperlink('', '#', ['class' => 'o-icon-delete button', 'title' => $translate('Delete attachment')]); // @translate
        $strings['restoreAttachment'] = $hyperlink('', '#', ['class' => 'o-icon-undo button', 'title' => $translate('Restore attachment')]); // @translate
        $strings['collapse'] = $translate('Collapse'); // @translate
        $strings['assets'] = $translate('Assets'); // @translate
        $strings['urlSidebarSelect'] = $escape($url('admin/default', ['controller' => 'asset', 'action' => 'sidebar-select']));
        $strings['addAsset'] = $translate('Add asset'); // @translate
        $strings['options'] = $translate('Options'); // @translate

        $attachmentRowTemplate = <<<HTML
 <div class="attachment%s">
    <span class="sortable-handle"></span>
    <div class="asset-title"><div class="thumbnail">%s</div>%s</div>
    <ul class="actions">
        <li>{$strings['openAssetOptions']}</li>
        <li class="delete">{$strings['deleteAttachment']}</li>
        <li class="undo">{$strings['restoreAttachment']}</li>
    </ul>
    <input type="hidden" class="asset-option asset" name="o:block[__blockIndex__][o:data][__attachmentIndex__][id]" value="%s"/>
    <input type="hidden" class="asset-option asset-page-id" name="o:block[__blockIndex__][o:data][__attachmentIndex__][page]" data-page-title="%s" data-page-url="%s" value="%s"/>
    <input type="hidden" class="asset-option alternative-link-title" name="o:block[__blockIndex__][o:data][__attachmentIndex__][alt_link_title]" value="%s"/>
    <input type="hidden" class="asset-option asset-caption" name="o:block[__blockIndex__][o:data][__attachmentIndex__][caption]" value="%s"/>
    <input type="hidden" class="asset-option asset-class" name="o:block[__blockIndex__][o:data][__attachmentIndex__][class]" value="%s"/>
    <input type="hidden" class="asset-option asset-url" name="o:block[__blockIndex__][o:data][__attachmentIndex__][url]" value="%s"/>
</div>
HTML;
        $strings['attachmentRowTemplateDefault'] = $escape(sprintf($attachmentRowTemplate, ' new', '', '', '', '', '', '', '', '', '', ''));

        $html = <<<HTML
<style>
.collapse + .collapsible { overflow: visible; }
</style>
<div class="asset-attachments-form" data-site-id="{$site->id()}" data-page-api-url="{$site->apiUrl()}">
    <a href="#" class="collapse" aria-label="{$strings['collapse']}" title="{$strings['collapse']}"><h4>{$strings['assets']}</h4></a>
    <div class="attachments collapsible" data-template="{$strings['attachmentRowTemplateDefault']}">
        %s
        <button type="button" class="add-asset-attachment" data-sidebar-content-url="{$strings['urlSidebarSelect']}">{$strings['addAsset']}</button>
    </div>
</div>
<a class="collapse" href="#" aria-label="{$strings['collapse']}">
    <h4>{$strings['options']}</h4>
</a>
<div class="collapsible">
    %s
    %s
    %s
    %s
</div>
HTML;

        $attachs = '';
        $attachments = $this->prepareAssetAttachments($view, $data);
        foreach ($attachments as $attachment) {
            $attachs .= sprintf(
                $attachmentRowTemplate,
                '',
                $attachment['asset'] ? $thumbnail($attachment['asset'], 'square') : '',
                $attachment['asset'] ? $escape($attachment['asset']->name()) : $escape($translate('No asset selected')), // @translate
                $attachment['asset'] ? $attachment['asset']->id() : '',
                $attachment['page'] ? $escape($attachment['page']->title()) : '',
                $attachment['page'] ? $escape($attachment['page']->siteUrl()) : '',
                $attachment['page'] ? $escape($attachment['page']->id()) : '',
                // Unlike upstream, the title is saved in any case.
                $escape($attachment['alt_link_title']),
                $escape($attachment['caption']),
                // Managed via js.
                empty($attachment['class']) ? '' : $escape($attachment['class']),
                empty($attachment['url']) ? '' : $escape($attachment['url'])
            ) . PHP_EOL;
        }

        return sprintf(
            $html,
            $attachs,
            $formRow($fieldset->get('o:block[__blockIndex__][o:data][heading]')),
            $formRow($fieldset->get('o:block[__blockIndex__][o:data][className]')),
            $formRow($fieldset->get('o:block[__blockIndex__][o:data][alignment]')),
            $formRow($fieldset->get('o:block[__blockIndex__][o:data][template]'))
        );
    }
}
