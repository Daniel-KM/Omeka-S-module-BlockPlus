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

        $requireBlockMetadata = !in_array($metadata, $this->require['page_metadata'], true);
        if ($requireBlockMetadata) {
            $block = $this->currentBlockMetadata($page);
            if (!$block && !in_array($metadata, $this->require['fallback_block_metadata'], true)) {
                return null;
            }
        } else {
            $block = null;
        }

        return $this->metadataBlock($metadata, $page, $block);
    }
}
