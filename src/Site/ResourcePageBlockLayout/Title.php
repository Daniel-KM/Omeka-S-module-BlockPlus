<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Display the title of the resource and set it as page title.
 *
 * @see \Omeka\Api\Representation\AbstractResourceEntityRepresentation::displayTitle()
 */
class Title extends AbstractResourcePageBlockBase
{
    protected $label = 'Title'; // @translate
    protected $partial = 'common/resource-page-block-layout/title';
}
