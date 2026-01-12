<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Abstract base class for simple resource page blocks.
 */
abstract class AbstractResourcePageBlockBase implements ResourcePageBlockLayoutInterface
{
    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $partial;

    /**
     * @var array
     */
    protected $resourceNames = [
        'items',
        'media',
        'item_sets',
    ];

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCompatibleResourceNames(): array
    {
        return $this->resourceNames;
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource): string
    {
        return $view->partial($this->partial, [
            'resource' => $resource,
        ]);
    }
}
