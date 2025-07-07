<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Display a simple block when no media is available for an item.
 */
class NoMedia implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'No Media'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'items',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial('common/resource-page-block-layout/no-media', [
            'resource' => $resource,
            'item' => $resource,
        ]);
    }
}
