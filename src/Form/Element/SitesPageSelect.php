<?php declare(strict_types=1);
namespace BlockPlus\Form\Element;

class SitesPageSelect extends AbstractGroupBySiteSelect
{
    public function getResourceName()
    {
        return 'site_pages';
    }

    public function getValueLabel($resource)
    {
        return $resource->title();
    }
}
