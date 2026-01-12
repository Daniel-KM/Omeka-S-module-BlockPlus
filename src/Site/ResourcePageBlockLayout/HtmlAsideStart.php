<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Append a generic html aside start tag with class "block-resource block-aside".
 */
class HtmlAsideStart extends AbstractResourcePageBlockBase
{
    protected $label = 'Html aside (start)'; // @translate
    protected $partial = 'common/resource-page-block-layout/html-aside-start';
}
