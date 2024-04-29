<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;

class ItemShowcase extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/item-showcase';

    public function getLabel()
    {
        return 'Item showcase'; // @translate
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        // This block doesn't use a form, but mainly view partials. The form is
        // only used for some standard fields (heading, partials).

        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['itemShowcase'];
        $blockFieldset = \BlockPlus\Form\ItemShowcaseFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        $html = '';
        $html .= $view->blockAttachmentsForm($block);

        $html .= '<a href="#" class="collapse" aria-label="collapse"><h4>' . $view->translate('Options') . '</h4></a>';
        $html .= '<div class="collapsible no-override">';
        $html .= '<style>.collapsible.no-override {overflow:visible;}</style>';
        $html .= $view->formCollection($fieldset);
        $html .= '</div>';

        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        $attachments = $block->attachments();
        if (!$attachments) {
            return '';
        }

        $vars = [
            'block' => $block,
            'attachments' => $attachments,
            'thumbnailType' => $block->dataValue('thumbnail_type', 'square'),
            'showTitleOption' => $block->dataValue('show_title_option', 'item_title'),
            'heading' => $block->dataValue('heading', ''),
        ];
        $template = $block->dataValue('template', self::PARTIAL_NAME);
        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }
}
