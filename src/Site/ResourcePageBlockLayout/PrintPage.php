<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Display a button to print the current page.
 */
class PrintPage extends AbstractResourcePageBlockBase
{
    protected $label = 'Print'; // @translate
    protected $partial = 'common/resource-page-block-layout/print';
}
