<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Append a generic html div start tag with class "block-resource block-division block-division-more".
 */
class HtmlDivMoreStart extends AbstractResourcePageBlockBase
{
    protected $label = 'Html div more (start)'; // @translate
    protected $partial = 'common/resource-page-block-layout/html-div-more-start';
}
