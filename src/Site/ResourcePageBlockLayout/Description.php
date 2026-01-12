<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

/**
 * Display the description of the resource.
 *
 * @see \Omeka\Api\Representation\AbstractResourceEntityRepresentation::displayDescription()
 */
class Description extends AbstractResourcePageBlockBase
{
    protected $label = 'Description'; // @translate
    protected $partial = 'common/resource-page-block-layout/description';
}
