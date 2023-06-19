<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Display buttons "Previous" and "Next" resources according to browse.
 *
 * Requires module EasyAdmin.
 * @todo Do not require EasyAdmin in a simpler version?
 */
class PreviousNext implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Buttons Previous / Next'; // @translate
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
        $plugins = $view->getHelperPluginManager();
        if (!$plugins->has('previousNext')) {
            return '';
        }
        $previousNext = $plugins->get('previousNext');
        return $previousNext($resource, [
            'template' => 'common/resource-page-block-layout/previous-next',
        ]);
    }
}
