<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;

class D3Graph extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/d3-graph';

    public function getLabel()
    {
        return 'D3 Graph'; // @translate
    }

    public function prepareRender(PhpRenderer $view): void
    {
        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->appendStylesheet($assetUrl('css/block-plus.css', 'BlockPlus'));
        $view->headScript()
            ->appendFile($assetUrl('vendor/d3/d3.v3.min.js', 'BlockPlus'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('js/d3-graph.js', 'BlockPlus'), 'text/javascript', ['defer' => 'defer']);
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['d3Graph'];
        $blockFieldset = \BlockPlus\Form\D3GraphFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        // TODO Store params as array.
        $vars = ['block' => $block] + $block->data();
        $vars['params'] = @json_decode($vars['params'], true) ?: [];

        if (empty($vars['params'])) {
            $view->logger()->warn(
                'A list of resources as json queries by resource name should be defined for block D3 Graph in page {page_url}.', // @translate
                ['page_url' => $block->page()->siteUrl()]
            );
            return;
        }

        return $view->partial($templateViewScript, $vars);
    }
}
