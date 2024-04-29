<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Display the link to the item in media page.
 */
class MediaPartOfItem implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Media part of item'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'media',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial('common/resource-page-block-layout/media-part-of-item', [
            'resource' => $resource,
            'item' => $resource->item(),
        ]);
    }
}
