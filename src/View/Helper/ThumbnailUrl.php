<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\AbstractRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;

/**
 * View helper to get a thumbnail url.
 */
class ThumbnailUrl extends AbstractHelper
{
    /**
     * Get a thumbnail url of a representation.
     *
     * The thumbnail may be specified directly, or be the primary media one.
     * More generic than the default method of representation "thumbnailDisplayUrl()".
     *
     * @see \Omeka\View\Helper\Thumbnail
     */
    public function __invoke(AbstractRepresentation $representation, ?string $type = 'square'): ?string
    {
        if ($representation instanceof SitePageRepresentation) {
            $representation = $this->thumbnailUrlPage($representation);
            if (!$representation) {
                return null;
            }
        } elseif ($representation instanceof SiteRepresentation) {
            $representation = $this->thumbnailUrlSite($representation);
            if (!$representation) {
                return null;
            }
        }
        return $representation->thumbnailDisplayUrl($type ?: 'square');
    }

    protected function thumbnailUrlSite(SiteRepresentation $site): ?AbstractRepresentation
    {
        $view = $this->getView();
        $api = $view->plugin('api');

        // First media from pages in the order of the navigation.
        $pages = $site->linkedPages();
        foreach ($pages as $page) {
            $representation = $this->thumbnailUrlPage($page);
            if ($representation) {
                return $representation;
            }
        }

        // Any other page in the site.
        $pages = $site->notLinkedPages();
        foreach ($pages as $page) {
            $representation = $this->thumbnailUrlPage($page);
            if ($representation) {
                return $representation;
            }
        }

        // Any media in the site.
        // FIXME This works only with module AdvancedSearchPlus or ApiInfo.
        return $api->searchOne('media', ['site_id' => $site->id(), 'has_thumbnails' => true])->getContent();
    }

    protected function thumbnailUrlPage(SitePageRepresentation $page): ?AbstractRepresentation
    {
        $view = $this->getView();
        $api = $view->plugin('api');

        $layoutsWithResource = [
            // 'html',
            // Core.
            'asset',
            'browsePreview',
            'itemShowcase',
            'itemShowCase',
            'itemWithMetadata',
            'media',
            // BlockPlus.
            'pageMetadata',
            'resourceText',
            // Deprecated from BlockPlus.
            'assets',
        ];

        $blocks = $page->blocks();
        foreach ($blocks as $block) {
            $layout = $block->layout();
            if (!in_array($layout, $layoutsWithResource)) {
                continue;
            }
            switch ($layout) {
                case 'asset':
                    // Asset can be the standard one (with attachments) or
                    // improved from the last version of block plus before
                    // integration.
                    // Note: This is not an array of attachements, but data.
                    /** @var \Omeka\Site\BlockLayout\Asset $assetBlockLayout */
                    $assetBlockLayout = $block->getServiceLocator()->get('Omeka\BlockLayoutManager')->get('asset');
                    foreach ($assetBlockLayout->prepareAssetAttachments($view, $block->data(), $page->site()) as $attachmentData) {
                        if (!empty($attachmentData['asset'])) {
                            return $attachmentData['asset'];
                        }
                        if (!empty($attachmentData['page'])) {
                            $repr = $this->thumbnailUrlPage($attachmentData['page']);
                            if ($repr) {
                                return $repr ;
                            }
                        }
                    }
                    break;
                case 'media':
                case 'itemShowcase':
                case 'itemShowCase':
                case 'itemWithMetadata':
                case 'resourceText':
                    /** @var \Omeka\Api\Representation\SiteBlockAttachmentRepresentation $attachement */
                    $attachments = $block->attachments();
                    if (empty($attachments)) {
                        break;
                    }
                    $attachment = reset($attachments);
                    return $attachment->media() ?: $attachment->item();

                case 'browsePreview':
                    $resourceType = $block->dataValue('resource_type', 'items');
                    $query = [];
                    parse_str(ltrim($block->dataValue('query'), "? \t\n\r\0\x0B"), $query);
                    $site = $block->page()->site();
                    if ($view->siteSetting('browse_attached_items', false)) {
                        $query['site_attachments_only'] = true;
                    }
                    $query['site_id'] = $site->id();
                    if (!isset($query['sort_by'])) {
                        $query['sort_by'] = 'created';
                    }
                    if (!isset($query['sort_order'])) {
                        $query['sort_order'] = 'desc';
                    }
                    $representation = $api->searchOne($resourceType, $query)->getContent();
                    if ($representation) {
                        return $representation;
                    }
                    break;

                case 'pageMetadata':
                    // TODO Always use the page metadata cover if there is one, even if it's not the first block.
                    $asset = $block->dataValue('cover');
                    if ($asset) {
                        try {
                            /** @var \Omeka\Api\Representation\AssetRepresentation $asset */
                            return $api->read('assets', $asset)->getContent();
                        } catch (\Omeka\Api\Exception\NotFoundException $e) {
                        }
                    }
                    // No break.
                // Deprecated.
                case 'assets':
                    $assets = $block->dataValue('assets', []);
                    foreach ($assets as $assetData) {
                        if (empty($assetData['asset'])) {
                            continue;
                        }
                        try {
                            /** @var \Omeka\Api\Representation\AssetRepresentation $asset */
                            return $api->read('assets', $assetData['asset'])->getContent();
                        } catch (\Omeka\Api\Exception\NotFoundException $e) {
                        }
                    }
                    break;
            }
        }
        return null;
    }
}
