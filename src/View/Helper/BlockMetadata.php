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
     * The block Page Metadata may be needed for some metadata.
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

        $page = $block
            ? $block->page()
            : $this->currentPage();
        if (!$page) {
            return null;
        }

        $requireBlockMetadata = !in_array($metadata, $this->require['page_metadata'], true);
        if ($requireBlockMetadata) {
            if (!$block || $block->layout() !== 'pageMetadata') {
                $block = $this->currentBlockMetadata($page);
                if (!$block) {
                    return null;
                }
            }
        }

        return $this->metadataBlock($metadata, $page, $block);
    }
}
