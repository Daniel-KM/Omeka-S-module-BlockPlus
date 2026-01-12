<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Display the large thumbnail of the resource (asset or primary media).
 */
class Thumbnail extends AbstractResourcePageBlockBase
{
    protected $label = 'Thumbnail'; // @translate
    protected $partial = 'common/resource-page-block-layout/thumbnail';
}
