<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Append a generic html div start tag with class "block-resource block-tools".
 */
class HtmlDivToolsStart extends AbstractResourcePageBlockBase
{
    protected $label = 'Html div for tools (start)'; // @translate
    protected $partial = 'common/resource-page-block-layout/html-div-tools-start';
}
