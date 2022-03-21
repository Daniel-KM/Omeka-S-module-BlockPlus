<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\SitePageRepresentation;

/**
 * View helper to get metadata about the current page.
 */
class PageMetadata extends AbstractHelper
{
    use PageBlockMetadataTrait;

    /**
     * Get metadata of the current page.
     *
     * @param string|SitePageRepresentation $metadata
     * @param SitePageRepresentation $page Current page if empty.
     * @return \Omeka\Api\Representation\SitePageRepresentation|mixed|null
     */
    public function __invoke($metadata = null, ?SitePageRepresentation $page = null)
    {
        if (is_object($metadata) && $metadata instanceof SitePageRepresentation) {
            $page = $metadata;
            $metadata = null;
        } elseif (!$page) {
            $page = $this->currentPage();
            if (!$page) {
                return null;
            }
        }

        $block = $this->currentBlockMetadata($page);
        return $this->metadataBlock($metadata, $block);
    }
}
