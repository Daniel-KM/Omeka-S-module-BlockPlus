<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;

class ListOfPages extends \Omeka\Site\BlockLayout\ListOfPages
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/list-of-pages';

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['listOfPages'];
        $blockFieldset = \BlockPlus\Form\ListOfPagesFieldset::class;

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        if (empty($data['pagelist'])) {
            $data['pagelist'] = '';
        } else {
            $nodes = json_decode($data['pagelist'], true);
            $data['pagelist'] = $this->getPageNodeURLs($nodes, $block);
        }
        $data['pagelist'] = json_encode($data['pagelist']);

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        $html = <<<'HTML'
<button type="button" class="site-page-add" data-sidebar-content-url="%s">%s</button>
<div class="block-pagelist-tree" data-jstree-data="%s"></div>
<div class="inputs">%s</div>

HTML;
        $escape = $view->plugin('escapeHtml');
        $formRow = $view->plugin('formRow');
        $html = sprintf(
            '%s' . $html . '%s',
            $formRow($fieldset->get('o:block[__blockIndex__][o:data][heading]')),
            $escape($page->url('sidebar-pagelist')),
            $view->translate('Add pages'),
            $escape($data['pagelist']),
            $formRow($fieldset->get('o:block[__blockIndex__][o:data][pagelist]')),
            $formRow($fieldset->get('o:block[__blockIndex__][o:data][template]'))
        );

        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $nodes = json_decode($block->dataValue('pagelist'), true);
        if (!$nodes) {
            return '';
        }

        $pageTree = $this->getPageNodeURLs($nodes, $block);

        $vars = [
            'heading' => $block->dataValue('heading'),
            'pageList' => $pageTree,
        ];

        $template = $block->dataValue('template', self::PARTIAL_NAME);
        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }
}
