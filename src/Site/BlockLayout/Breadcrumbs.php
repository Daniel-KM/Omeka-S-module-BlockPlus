<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Common\Stdlib\PsrMessage;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;

class Breadcrumbs extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
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
        return '<p>'
            . new PsrMessage(
                $view->translate('This block uses the options set in the {link}site settings{link_end}, unless you use the standard template.'), // @translate
                ['link' => '<a href="' . $view->url('admin/site/slug', ['action' => 'edit'], ['fragment' => 'site-settings'], true) . '">', 'link_end' => '</a>']
            )
            . '</p>';
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        $page = $block->page();
        $site = $page->site();

        $vars = ['block' => $block] + $block->data();
        $vars['site'] = $site;
        $vars['page'] = $page;

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

        return $view->partial($templateViewScript, $vars);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return strip_tags((string) $this->render($view, $block));
    }
}
