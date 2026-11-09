<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Append a html div start tag with a button to collapse it.
 *
 * The other blocks of the same container may use the freed space, for example
 * to display the viewer on the whole width.
 */
class CollapsibleStart extends AbstractResourcePageBlockBase
{
    protected $label = 'Collapsible (start)'; // @translate
    protected $partial = 'common/resource-page-block-layout/collapsible-start';
}
