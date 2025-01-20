<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;

class PageMetadata extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Page metadata (deprecated)'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = $block->getData() + ['summary' => '', 'params' => '', 'tags' => []];
        $data['summary'] = str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $data['summary']);
        $data['params'] = str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $data['params']);
        if (empty($data['tags'])) {
            $data['tags'] = [];
        } elseif (!is_array($data['tags'])) {
            $data['tags'] = array_filter(array_map('trim', explode(',', $data['tags'])));
        }
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['pageMetadata'];
        $blockFieldset = \BlockPlus\Form\PageMetadataFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        if (is_array($data['tags'])) {
            $data['tags'] = implode(', ', $data['tags']);
        }

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        $translate = $view->plugin('translate');
        $html = '<p>'
            . $translate('This block doesn’t display anything, but stores various metadata for themes. It will be removed soon and replaced by the main page metadata above.') // @translate
            . '</p>';
        $html .= $view->formCollection($fieldset, false);
        $html .= $view->blockAttachmentsForm($block);
        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return '';
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        // TODO Add captions (they are not added in the core)?
        return trim(
            $block->dataValue('summary', '')
            . ' ' . $block->dataValue('credits', '')
            . ' ' . implode(', ', $block->dataValue('tags', []))
        );
    }
}
