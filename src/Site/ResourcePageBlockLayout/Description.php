<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Display the description of the resource.
 */
class Description implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Description'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'items',
            'media',
            'item_sets',
        ];
    }

    /**
     *@see \Omeka\Api\Representation\AbstractResourceEntityRepresentation::displayDescription()
     *
     * {@inheritDoc}
     * @see \Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface::render()
     */
    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource): string
    {
        return $view->partial('common/resource-page-block-layout/description', [
            'resource' => $resource,
        ]);
    }
}
