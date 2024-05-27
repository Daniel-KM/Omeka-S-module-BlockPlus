<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\Navigation\Navigation;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;

class TableOfContents extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/table-of-contents';

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

        // Force default value to 1.
        $depthValue = 1;
        if ($block) {
            $blockDepth = (int) $block->dataValue('depth');
            if ($blockDepth > 1) {
                $depthValue = $blockDepth;
            }
        }

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        $view->pageViewModel->setVariable('displayNavigation', false);

        /**
         * @var \Laminas\View\Helper\Navigation $container
         */
        $nav = $block->page()->site()->publicNav();
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
        try {
            $subNav = new Navigation($newPages);
        } catch (\Laminas\Navigation\Exception\InvalidArgumentException $e) {
            $view->logger()->warn(sprintf(
                'Cannot index and/or render a table of contents block in a mirror page for now: %s.', // @translate
                $e
            ));
            $subNav = new Navigation([]);
        }

        // Don't use dataValue's default here; we need to handle empty/non-numerics anyway
        $depth = (int) $block->dataValue('depth');
        if ($depth < 1) {
            $depth = 1;
        }

        $vars = [
            'block' => $block,
            'subNav' => $subNav,
            'maxDepth' => $depth - 1,
        ];
        return $view->partial($templateViewScript, $vars);
    }
}
