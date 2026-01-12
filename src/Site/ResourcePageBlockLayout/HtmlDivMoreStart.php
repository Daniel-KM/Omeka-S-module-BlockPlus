<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Append a generic html div start tag with class "block-resource block-division block-division-more".
 */
class HtmlDivMoreStart implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Html div more (start)'; // @translate
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
        return $view->partial('common/resource-page-block-layout/html-div-more-start', [
            'resource' => $resource,
        ]);
    }
}
