<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Append a generic html aside start tag with class "block-resource block-aside".
 */
class HtmlAsideStart implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Html aside (start)'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'items',
            'media',
            'item_sets',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial('common/resource-page-block-layout/html-aside-start', [
            'resource' => $resource,
        ]);
    }
}
