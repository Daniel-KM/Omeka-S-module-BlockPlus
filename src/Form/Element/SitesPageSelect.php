<?php declare(strict_types=1);

namespace BlockPlus\Form\Element;

use Omeka\Api\Representation\SitePageRepresentation;

/**
 * Used in:
 *
 * @see \BlockPlus\Form\Element\SitesPageSelect
 * @see \Internationalisation\Form\Element\SitesPageSelect
 */
class SitesPageSelect extends AbstractGroupBySiteSelect
{
    public function getResourceName(): string
    {
        return 'site_pages';
    }

    public function getValueLabel(SitePageRepresentation $resource): string
    {
        return $resource->title();
    }
}
