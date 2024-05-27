<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;

class Separator extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Separator'; // @translate
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        return '<p>'
            . $view->translate('This block adds a simple div with class "break separator" to clear css.') // @translate
            . '</p>';
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return '<div class="break separator"></div>';
    }
}
