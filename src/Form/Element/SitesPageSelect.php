<?php declare(strict_types=1);

namespace BlockPlus\Form\Element;

use Omeka\Api\Representation\SitePageRepresentation;

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
