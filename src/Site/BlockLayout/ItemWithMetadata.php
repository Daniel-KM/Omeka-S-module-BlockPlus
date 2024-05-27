<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;

class ItemWithMetadata extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/item-with-metadata';

    public function getLabel()
    {
        return 'Item with metadata'; // @translate
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['itemWithMetadata'];
        $blockFieldset = \BlockPlus\Form\ItemWithMetadataFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        $html = '';
        $html .= $view->blockAttachmentsForm($block);
        $html .= $view->formCollection($fieldset);

        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        $attachments = $block->attachments();
        if (!$attachments) {
            return 'No item selected'; // @translate
        }

        $vars = [
            'block' => $block,
            'attachments' => $attachments,
        ];
        $template = $block->dataValue('template', self::PARTIAL_NAME);
        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }
}
