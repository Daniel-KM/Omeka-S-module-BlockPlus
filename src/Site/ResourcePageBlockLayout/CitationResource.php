<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Display the citation of the resource.
 */
class CitationResource extends AbstractResourcePageBlockBase
{
    protected $label = 'Citation'; // @translate
    protected $partial = 'common/resource-page-block-layout/citation-resource';
}
