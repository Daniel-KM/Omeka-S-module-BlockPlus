<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Display a simple block when no item is available for an item set.
 */
class NoItem implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'No Item'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'item_sets',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial('common/resource-page-block-layout/no-item', [
            'resource' => $resource,
            'itemSet' => $resource,
        ]);
    }
}
