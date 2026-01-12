<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Append a generic html div end tag (for div-more).
 */
class HtmlDivMoreEnd extends AbstractResourcePageBlockBase
{
    protected $label = 'Html div more (end)'; // @translate
    protected $partial = 'common/resource-page-block-layout/html-div-more-end';
}
