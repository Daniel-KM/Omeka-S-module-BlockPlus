<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Append a generic html section start tag with class "block-resource block-section".
 */
class HtmlSectionStart extends AbstractResourcePageBlockBase
{
    protected $label = 'Html section (start)'; // @translate
    protected $partial = 'common/resource-page-block-layout/html-section-start';
}
