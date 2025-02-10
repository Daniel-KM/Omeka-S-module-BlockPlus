<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\SitePageBlockRepresentation;

/**
 * View helper to get metadata about the current block.
 */
class BlockMetadata extends AbstractHelper
{
    use PageBlockMetadataTrait;

    /**
     * Get metadata of the current page or block.
     *
     * The block Page Metadata may be needed for some metadata (attachments).
     * Anyway, the use of the block Page Metadata is deprecated.
     *
     * @param string $metadata
     * @param SitePageBlockRepresentation $block The block metadata if empty.
     * @return \Omeka\Api\Representation\SitePageBlockRepresentation|mixed
     */
    public function __invoke(?string $metadata = null, ?SitePageBlockRepresentation $block = null)
    {
        // There are two cases. Metadata may require:
        // - page data,
        // - block metadata data.
        // All other

        $page = $block
            ? $block->page()
            : $this->currentPage();
        if (!$page) {
            return null;
        }

        $priorizeBlockMetadata = !in_array($metadata, $this->require['page_metadata'], true);
        if ($priorizeBlockMetadata
            && (!$block || $block->layout() !== 'pageMetadata')
        ) {
            $block = $this->currentBlockMetadata($page);
        }

        return $this->metadataBlock($metadata, $page, $block);
    }
}
