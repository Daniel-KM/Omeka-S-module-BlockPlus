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
     * The block Page Metadata may be needed for some metadata (attachments).
     * Anyway, the use of the block Page Metadata is deprecated.
     *
     * @param string $metadata
     * @param SitePageRepresentation $page Current page if empty.
     * @return \Omeka\Api\Representation\SitePageRepresentation|mixed|null
     */
    public function __invoke(?string $metadata = null, ?SitePageRepresentation $page = null)
    {
        if (!$page) {
            $page = $this->currentPage();
            if (!$page) {
                return null;
            }
        }

        $priorizeBlockMetadata = !in_array($metadata, $this->require['page_metadata'], true);
        $block = $priorizeBlockMetadata
            ? $this->currentBlockMetadata($page)
            : null;

        return $this->metadataBlock($metadata, $page, $block);
    }
}
