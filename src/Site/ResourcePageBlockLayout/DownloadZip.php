<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Download resource files as zip archive.
 */
class DownloadZip implements ResourcePageBlockLayoutInterface
{
    public function getLabel(): string
    {
        return 'Download zip'; // @translate
    }

    public function getCompatibleResourceNames(): array
    {
        return [
            'items',
            'media',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource): string
    {
        return $view->partial('common/resource-page-block-layout/download-zip', [
            'resource' => $resource,
        ]);
    }
}
