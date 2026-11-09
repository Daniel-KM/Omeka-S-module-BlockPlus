<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Append the html div end tag of a collapsible block.
 */
class CollapsibleEnd extends AbstractResourcePageBlockBase
{
    protected $label = 'Collapsible (end)'; // @translate
    protected $partial = 'common/resource-page-block-layout/collapsible-end';
}
