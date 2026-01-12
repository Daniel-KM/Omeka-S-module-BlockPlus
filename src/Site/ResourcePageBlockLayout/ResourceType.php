<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Display the type of the resource.
 */
class ResourceType extends AbstractResourcePageBlockBase
{
    protected $label = 'Resource type'; // @translate
    protected $partial = 'common/resource-page-block-layout/resource-type';
}
