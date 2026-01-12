<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Append a generic html article start tag with class "block-resource block-article".
 */
class HtmlArticleStart extends AbstractResourcePageBlockBase
{
    protected $label = 'Html article (start)'; // @translate
    protected $partial = 'common/resource-page-block-layout/html-article-start';
}
