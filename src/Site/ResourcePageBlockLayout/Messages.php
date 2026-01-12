<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Display the messages in the resource pages.
 */
class Messages extends AbstractResourcePageBlockBase
{
    protected $label = 'Messages'; // @translate
    protected $partial = 'common/resource-page-block-layout/messages';
}
