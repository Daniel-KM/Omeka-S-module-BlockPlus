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

        // Parent block is not a form, but complex, so it's simpler to use an
        // adapted view.
        // @todo Use a standard Laminas form. See previous version.
        return $view->partial('common/block-layout/admin/asset-block-form', [
            'fieldset' => $fieldset,
            'block' => $dataForm,
            'siteId' => $site->id(),
            'apiUrl' => $site->apiUrl(),
            'attachments' => $this->prepareAssetAttachments($view, $dataForm),
            'alignmentClassSelect' => $fieldset->get('o:block[__blockIndex__][o:data][alignment]'),
        ]);
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
}
