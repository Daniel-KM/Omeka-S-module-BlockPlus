<?php
namespace BlockPlus\Site\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Zend\Navigation\Navigation;
use Zend\View\Renderer\PhpRenderer;

class TableOfContents extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Table of contents'; // @translate
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['tableOfContents'];
        $blockFieldset = \BlockPlus\Form\TableOfContentsFieldset::class;

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $view->pageViewModel->setVariable('displayNavigation', false);
        $nav = $block->page()->site()->publicNav();

        /** @var \Zend\View\Helper\Navigation $container */
        $container = $nav->getContainer();
        if ($block->dataValue('root')) {
            $activePage = ['page' => $container, 'depth' => 0];
        } else {
            $activePage = $nav->findActive($container);
            if (!$activePage) {
                return null;
            }
        }

        // Make new copies of the pages so we don't disturb the regular nav
        $pages = $activePage['page']->getPages();
        $newPages = [];
        foreach ($pages as $page) {
            $newPages[] = $page->toArray();
        }
        $subNav = new Navigation($newPages);

        $depth = (int) $block->dataValue('depth', 1);

        $template = $block->dataValue('template') ?: 'common/block-layout/table-of-contents';

        return $view->partial($template, [
            'block' => $block,
            'heading' => $block->dataValue('heading'),
            'subNav' => $subNav,
            'maxDepth' => $depth - 1,
        ]);
    }
}
