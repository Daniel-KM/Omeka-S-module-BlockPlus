<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;

class Breadcrumbs extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/breadcrumbs';

    public function getLabel()
    {
        return 'Breadcrumbs'; // @translate
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['breadcrumbs'];
        $blockFieldset = \BlockPlus\Form\BreadcrumbsFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        $html = '<p>'
            . sprintf(
                $view->translate('This block uses the options set in the %1$ssite settings%2$s, unless you use the standard template.'), // @translate
                '<a href="' . $view->url('admin/site/slug', ['action' => 'edit'], ['fragment' => 'site-settings'], true) . '">', '</a>'
            )
            . '</p>';
        $html .= $view->formCollection($fieldset, false);
        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $page = $block->page();
        $site = $page->site();

        $vars = $block->data();
        $vars['site'] = $site;
        $vars['page'] = $page;
        $vars['block'] = $block;

        $template = empty($vars['template']) ? self::PARTIAL_NAME : $vars['template'];
        unset($vars['template']);

        // Check if the page is in the navigation menu, for example an isolated
        // page "mentions légales" in the footer.
        $vars['nav'] = $site->publicNav();
        $vars['activePage'] = $vars['nav']->findActive($vars['nav']->getContainer());

        $plugins = $view->getHelperPluginManager();
        $siteSetting = $plugins->get('siteSetting');

        $crumbs = $siteSetting('blockplus_breadcrumbs_crumbs', []);
        $vars['prependHome'] = in_array('home', $crumbs);
        $vars['appendCurrent'] = in_array('current', $crumbs);
        $vars['separator'] = (string) $siteSetting('blockplus_breadcrumbs_separator');

        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return strip_tags((string) $this->render($view, $block));
    }
}
