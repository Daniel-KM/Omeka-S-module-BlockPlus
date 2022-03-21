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
     * Get metadata of the current block through the block Page Metadata.
     *
     * @param string|SitePageBlockRepresentation $metadata
     * @param SitePageBlockRepresentation $block The block metadata if empty.
     * @return \Omeka\Api\Representation\SitePageBlockRepresentation|mixed
     */
    public function __invoke($metadata = null, SitePageBlockRepresentation $block = null)
    {
        $view = $this->getView();

        if (is_object($metadata) && $metadata instanceof SitePageBlockRepresentation) {
            $block = $metadata;
            $metadata = null;
        } elseif (!$block) {
            if (empty($view->block)) {
                $page = $this->currentPage();
                if (!$page) {
                    return null;
                }
                $block = $this->currentBlockMetadata($page);
                if (!$block) {
                    return null;
                }
            } else {
                $block = $view->block;
            }
        }

        return $this->metadataBlock($metadata, $block);
    }
}
