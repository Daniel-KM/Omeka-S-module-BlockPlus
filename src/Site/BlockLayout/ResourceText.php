<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\HtmlPurifier;

/**
 * Replace media + html and simplify management of right/left media beside text.
 *
 * @link https://omeka.org/s/docs/user-manual/sites/site_pages/#media
 * @link https://omeka.org/s/docs/user-manual/sites/site_pages/#html
 */
class ResourceText extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    use CommonTrait;

    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/resource-text';

    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    /**
     * @param HtmlPurifier $htmlPurifier
     */
    public function __construct(HtmlPurifier $htmlPurifier)
    {
        $this->htmlPurifier = $htmlPurifier;
    }

    public function getLabel()
    {
        return 'Resource with text'; // @translate
    }

    public function prepareRender(PhpRenderer $view): void
    {
        $view->headLink()->appendStylesheet($view->assetUrl('css/block-plus.css', 'BlockPlus'));
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = $block->getData();
        $data['html'] = isset($data['html'])
            ? $this->fixEndOfLine($this->htmlPurifier->purify($data['html']))
            : '';
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['resourceText'];
        $blockFieldset = \BlockPlus\Form\ResourceTextFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        // Display manually to inset collapsible options.
        $plugins = $view->getHelperPluginManager();
        $formRow = $plugins->get('formRow');
        $translate = $plugins->get('translate');
        $html = '';
        $element = $fieldset->get('o:block[__blockIndex__][o:data][heading]');
        $html .= $formRow($element);
        $html .= $view->blockAttachmentsForm($block);
        $element = $fieldset->get('o:block[__blockIndex__][o:data][html]');
        $html .= $formRow($element);
        $html .= '<a href="#" class="expand" aria-label="' . $translate('Expand') . '"><h4>' . $translate('Options') . '</h4></a>';
        $html .= '<div class="collapsible no-override">';
        $html .= '<style>.collapsible.no-override {overflow:visible;}</style>';
        $optionsElements = [
            'o:block[__blockIndex__][o:data][thumbnail_type]',
            'o:block[__blockIndex__][o:data][alignment]',
            'o:block[__blockIndex__][o:data][show_title_option]',
            'o:block[__blockIndex__][o:data][caption_position]',
            'o:block[__blockIndex__][o:data][template]',
        ];
        foreach ($optionsElements as $element) {
            $element = $fieldset->get($element);
            $html .= $formRow($element);
        }
        $html .= '</div>';
        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        $attachments = $block->attachments();
        $html = $block->dataValue('html', '');
        if (!$attachments && !$html) {
            return '';
        }

        $vars = [
            'block' => $block,
            'heading' => $block->dataValue('heading', ''),
            'attachments' => $attachments,
            'html' => $html,
            'alignmentClass' => $block->dataValue('alignment', 'left'),
            'thumbnailType' => $block->dataValue('thumbnail_type', 'square'),
            'showTitleOption' => $block->dataValue('show_title_option', 'item_title'),
            'captionPosition' => $block->dataValue('caption_position', 'center'),
        ];
        $template = $block->dataValue('template', self::PARTIAL_NAME);
        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        // TODO Add captions (they are not added in the core)?
        return $block->dataValue('heading', '')
            . ' ' . $block->dataValue('html', '');
    }
}
