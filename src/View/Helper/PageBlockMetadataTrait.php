<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\Navigation\Navigation;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;

/**
 * View helper to get metadata about the current page.
 */
trait PageBlockMetadataTrait
{
    /**
     * There are two cases. Metadata may require:
     * - page data
     * - block metadata data
     * Threre are two situations:
     * - from the page
     * - from a block.
     *
     * @var array
     */
    protected $require = [
        'page_metadata' => [
            'page',
            'title',
            'slug',
            'theme_dir',
            'template',
            'template_name',
            'type',
            'first_image',
            'is_home_page',
            'nav_data',
            'root',
            'subroot',
            'sub_root',
            'parent',
            'parents',
            'prev',
            'previous',
            'next',
            'children',
            'siblings',
            'exhibit',
            'exhibit_nav',
            // Pre-rdf metadata.
            'dcterms:title',
            'dcterms:creator',
            'dcterms:description',
            'dcterms:subject',
            'curation:featured',
            'curation:new',
            // This is the raw block params.
            'curation:data',
        ],
        // Priority to old block metadata.
        'block_metadata' => [
            'block',
            null,
            'type',
            'credits',
            'summary',
            'tags',
            'type_label',
            'featured',
            'new',
            'cover',
            'cover_url',
            'attachments',
            'main_image',
            'params',
            'params_raw',
            'params_json',
            'params_json_array',
            'params_json_object',
            'params_ini',
            'params_key_value_array',
            'params_key_value',
            // And others.
        ],
        'fallback_block_metadata' => [
            'credits',
            'summary',
            'tags',
            'featured',
            'new',
            'params',
            'params_raw',
            'params_json',
            'params_json_array',
            'params_json_object',
            'params_ini',
            'params_key_value_array',
            'params_key_value',
        ],
    ];

    /**
     * Get metadata of a page or a block metadata.
     *
     * The block should be the block metadata of the page.
     * If the block is not available, only common page metadata are available.
     */
    protected function metadataBlock(
        ?string $metadata,
        SitePageRepresentation $page,
        ?SitePageBlockRepresentation $block
    ) {
        $view = $this->getView();

        $mapMetadata = [
            'credits' => 'dcterms:creator',
            'summary' => 'dcterms:description',
            'tags' => 'dcterms:subject',
            'featured' => 'curation:featured',
            'new' => 'curation:new',
            'params' => 'curation:data',
        ];

        $getParams = function () use ($page, $block) {
            $p = $block ? trim((string) $block->dataValue('params')) : '';
            return $p === ''
                ? (string) $page->layoutDataValue('curation:data')
                : $p;
        };

        switch ($metadata) {
            case 'block':
            case is_null($metadata):
                return $block;

            case 'page':
                return $page;
            case 'dctermsdcterms:title':
            case 'title':
                return $page->title();
            case 'slug':
                return $page->slug();

            case 'theme_dir':
                return OMEKA_PATH . '/themes/' . $this->currentSite()->theme();

            case 'template':
            case 'template_name':
                return $page->layoutDataValue('template_name') ?: null;

            case 'dcterms:creator':
            case 'dcterms:description':
            case 'dcterms:subject':
            case 'curation:featured':
            case 'curation:new':
            case 'curation:data':
                return $page->layoutDataValue($metadata) ?: null;

            case 'type':
                $view->logger()->warn('Since Omeka S 4.1, the metadata "type" is replaced by the page template name (key "template"). Check your theme.'); // @translate
                return $page->layoutDataValue('template_name') ?: null;

            case 'credits':
            case 'summary':
                if ($block) {
                    $result = $block->dataValue($metadata);
                    if ($result !== null && $result !== '' && $result !== []) {
                        return $result;
                    }
                }
                return $page->layoutDataValue($mapMetadata[$metadata]);
            case 'tags':
                if ($block) {
                    $result = $block->dataValue('tags');
                    if ($result !== null && $result !== '' && $result !== []) {
                        return (array) $result;
                    }
                }
                $result = $page->layoutDataValue($mapMetadata[$metadata], []) ?: [];
                return is_array($result)
                    ? $result
                    : array_map('trim', explode(',', $result));

            case 'type_label':
                if (!$block) {
                    return null;
                }
                $type = $block->dataValue('type');
                $pageTypes = $view->siteSetting('blockplus_page_types', []);
                return $pageTypes[$type] ?? null;

            case 'featured':
            case 'new':
                if ($block) {
                    $result = $block->dataValue($metadata);
                    if ($result !== null && $result !== '' && $result !== []) {
                        return (bool) $result;
                    }
                }
                return (bool) $page->layoutDataValue($mapMetadata[$metadata]);

            case 'cover':
            case 'cover_url':
                if (!$block) {
                    return null;
                }
                $asset = $block->dataValue('cover');
                if (!$asset) {
                    return null;
                }
                try {
                    /** @var \Omeka\Api\Representation\AssetRepresentation $asset */
                    $asset = $view->api()->read('assets', ['id' => $asset])->getContent();
                } catch (NotFoundException $e) {
                    return null;
                }
                return $metadata === 'cover_url' ? $asset->assetUrl() : $asset;

            case 'attachments':
                if (!$block) {
                    return [];
                }
                return $block->attachments();

            case 'first_image':
                // First image in the page in any block (pageMetadata or asset).
                $api = $view->api();
                // Search for a block pageMetadata or asset.
                foreach ($page->blocks() as $block) {
                    $layout = $block->layout();
                    if ($layout === 'pageMetadata') {
                        $asset = $block->dataValue('cover');
                        if ($asset) {
                            try {
                                return $api->read('assets', ['id' => $asset])->getContent();
                            } catch (\Omeka\Api\Exception\NotFoundException $e) {
                            }
                        }
                    } elseif ($layout === 'asset') {
                        foreach ($block->data() as $asset) {
                            try {
                                return $api->read('assets', ['id' => $asset['id']])->getContent();
                            } catch (\Omeka\Api\Exception\NotFoundException $e) {
                            }
                        }
                    }
                    // Search for attachments of the current block.
                    foreach ($block->attachments() as $attachment) {
                        $media = $attachment->media();
                        if ($media && ($media->hasThumbnails() || $media->thumbnail())) {
                            return $media;
                        }
                        $item = $attachment->item();
                        if ($item) {
                            if ($thumbnail = $item->thumbnail()) {
                                return $thumbnail;
                            }
                            $media = $item->primaryMedia();
                            if ($media && ($media->hasThumbnails() || $media->thumbnail())) {
                                return $media;
                            }
                        }
                    }
                }
                return null;
            case 'main_image':
                // Main image checks current block only, metadata or asset.
                if (!$block) {
                    return null;
                }
                $api = $view->api();
                $layout = $block->layout();
                // Check if acover is defined in the current block.
                if ($layout === 'pageMetadata') {
                    $asset = $block->dataValue('cover');
                    if ($asset) {
                        try {
                            return $api->read('assets', ['id' => $asset])->getContent();
                        } catch (\Omeka\Api\Exception\NotFoundException $e) {
                        }
                    }
                } elseif ($layout === 'asset') {
                    foreach ($block->data() as $asset) {
                        try {
                            return $api->read('assets', ['id' => $asset['id']])->getContent();
                        } catch (\Omeka\Api\Exception\NotFoundException $e) {
                        }
                    }
                }
                // Search for attachments of the current block.
                foreach ($block->attachments() as $attachment) {
                    $media = $attachment->media();
                    if ($media && ($media->hasThumbnails() || $media->thumbnail())) {
                        return $media;
                    }
                    $item = $attachment->item();
                    if ($item) {
                        if ($thumbnail = $item->thumbnail()) {
                            return $thumbnail;
                        }
                        $media = $item->primaryMedia();
                        if ($media && ($media->hasThumbnails() || $media->thumbnail())) {
                            return $media;
                        }
                    }
                }
                return null;

            case 'is_home_page':
                return $view->isHomePage($page);

            case 'nav_data':
                return $this->findPageInNavigation($page->id(), $page->site()->navigation());
            case 'root':
                $parents = $this->parentPages($page);
                return empty($parents) ? $page : array_pop($parents);
            case 'subroot':
            case 'sub_root':
                $parents = $this->parentPages($page);
                if (empty($parents)) {
                    return null;
                }
                if (count($parents) === 1) {
                    return $page;
                }
                array_pop($parents);
                return array_pop($parents);
            case 'parent':
                $parents = $this->parentPages($page);
                return empty($parents) ? null : reset($parents);
            case 'parents':
                return $this->parentPages($page);
            case 'prev':
            case 'previous':
                return $this->previousNextPages($page, 'previous');
            case 'next':
                return $this->previousNextPages($page, 'next');
            case 'children':
                return $this->childrenPages($page);
            case 'siblings':
                return $this->siblingPages($page);

            case 'exhibit':
                $type = $page->layoutDataValue('template_name') ?: null;
                switch ($type) {
                    case 'exhibit-page':
                    case 'exhibit_page':
                        $parentPages = $this->parentPages($page);
                        foreach ($parentPages as $parentPage) {
                            if ($view->pageMetadata('type', $parentPage) === 'exhibit') {
                                return $parentPage;
                            }
                        }
                        return null;
                    case 'exhibit':
                        return $page;
                    default:
                        return null;
                }
                break;
            case 'exhibit_nav':
                /** @var \Omeka\Api\Representation\SitePageRepresentation $exhibit */
                $exhibit = $view->pageMetadata('exhibit', $page);
                return $exhibit
                    ? $this->navigationForPage($exhibit)
                    : null;

            case 'params':
            case 'params_raw':
                return $getParams();
            case 'params_json':
            case 'params_json_array':
                $p = $getParams();
                return @json_decode($p, true) ?: [];
            case 'params_json_object':
                $p = $getParams();
                return @json_decode($p) ?: (object) [];
            case 'params_ini':
                $reader = new \Laminas\Config\Reader\Ini();
                $p = $getParams();
                return $reader->fromString($p);
            case 'params_key_value_array':
                $p = $getParams();
                $params = array_map('trim', explode("\n", $p));
                $list = [];
                foreach ($params as $keyValue) {
                    $list[] = array_map('trim', explode('=', $keyValue, 2)) + ['', ''];
                }
                return $list;
            case 'params_key_value':
            default:
                $p = $getParams();
                $params = array_filter(array_map('trim', explode("\n", $p)), 'strlen');
                $list = [];
                foreach ($params as $keyValue) {
                    [$key, $value] = mb_strpos($keyValue, '=') === false
                        ? [trim($keyValue), '']
                        : array_map('trim', explode('=', $keyValue, 2));
                    if ($key !== '') {
                        $list[$key] = $value;
                    }
                }
                if ($metadata === 'params_key_value') {
                    return $list;
                }
                return $list[$metadata] ?? null;
        }
    }

    protected function navigationForPage(SitePageRepresentation $page)
    {
        $nav = $page->site()->publicNav();
        /** @var \Laminas\Navigation\Navigation $container */
        $container = $nav->getContainer();
        $navPage = $container->findOneBy('params', ['site-slug' => $page->site()->slug(), 'page-slug' => $page->slug()]);

        // See \Omeka\Site\BlockLayout\TableOfContents::render()
        // Make new copies of the pages so we don't disturb the regular nav
        $pages = [];
        foreach ($navPage->getPages() as $page) {
            $pages[] = $page->toArray();
        }
        return new Navigation($pages);
    }

    protected function sitePages(SiteRepresentation $site)
    {
        static $pagesBySite;

        $siteId = $site->id();
        if (!isset($pagesBySite[$siteId])) {
            $pagesBySite[$siteId] = [];
            foreach ($site->pages() as $sitePage) {
                $pagesBySite[$siteId][$sitePage->id()] = $sitePage;
            }
        }

        return $pagesBySite[$siteId];
    }

    /**
     * Get the parent pages of a page.
     *
     * @todo Improve the process to get the parent pages.
     *
     * @param SitePageRepresentation $page
     * @return SitePageRepresentation[]
     */
    protected function parentPages(SitePageRepresentation $page)
    {
        $site = $page->site();
        $sitePages = $this->sitePages($site);
        $navigation = $site->navigation();
        $pages = [];
        $pageId = $page->id();
        while (true) {
            $pageData = $this->findPageInNavigation($pageId, $navigation);
            if (empty($pageData['parent_id'])) {
                return $pages;
            }
            $pages[] = $sitePages[$pageData['parent_id']] ?? null;
            $pageId = $pageData['parent_id'];
        }
        return $pages;
    }

    /**
     * Get the previous or next page.
     *
     * @param SitePageRepresentation $page
     * @param string $sibling "previous" or "next"
     * @return SitePageRepresentation|null
     */
    protected function previousNextPages(SitePageRepresentation $page, $sibling)
    {
        static $pages = [];

        $pageId = $page->id();
        if (!isset($pages[$pageId][$sibling])) {
            $pages[$pageId]['previous'] = null;
            $pages[$pageId]['next'] = null;

            // @see \Omeka\View\Helper\SitePagePagination::setPage()
            $linkedPages = $page->site()->linkedPages();
            // Find page in navigation. Don't attempt to find prev/next else.
            if (array_key_exists($pageId, $linkedPages)) {
                // Iterate the linked pages, setting the previous and next pages, if any.
                while ($linkedPage = current($linkedPages)) {
                    if ($pageId === $linkedPage->id()) {
                        $pages[$pageId]['next'] = next($linkedPages);
                        break;
                    }
                    $pages[$pageId]['previous'] = $linkedPage;
                    next($linkedPages);
                }
            }
        }

        return $pages[$pageId][$sibling];
    }

    /**
     * Get the children pages of a page.
     *
     * @param SitePageRepresentation $page
     * @return SitePageRepresentation[]
     */
    protected function childrenPages(SitePageRepresentation $page): array
    {
        $site = $page->site();
        $pageData = $this->findPageInNavigation($page->id(), $site->navigation());
        if (empty($pageData['children'])) {
            return [];
        }
        $ids = array_values(array_filter(array_map(fn ($v) => $v && $v['type'] === 'page' ? $v['id'] : null, $pageData['children'])));
        return $ids
            ? array_intersect_key($this->sitePages($site), array_flip($ids))
            : [];
    }

    /**
     * Get the sibling pages of a page.
     *
     * The process uses the parent page.
     *
     * @param SitePageRepresentation $page
     * @return SitePageRepresentation[]
     */
    protected function siblingPages(SitePageRepresentation $page): array
    {
        $site = $page->site();
        $pageData = $this->findPageInNavigation($page->id(), $site->navigation());
        return empty($pageData['siblings'])
            ? []
            : array_intersect_key($this->sitePages($site), array_flip($pageData['siblings']));
    }

    /**
     * Find data about a page from the navigation.
     *
     * FIXME Use nav container, not the static site navigation (even if it should be the same because no page are private).
     * See \Omeka\Site\BlockLayout\TableOfContents::render()
     *
     * @param int $pageId
     * @param array $navItems
     * @param int $parentPageId
     * @return array
     */
    protected function findPageInNavigation($pageId, $navItems, $parentPageId = null, $navId = 0): array
    {
        static $pages = [];

        if (isset($pages[$pageId])) {
            return $pages[$pageId];
        }

        foreach ($navItems as $navItem) {
            ++$navId;
            $isPage = $navItem['type'] === 'page';

            $navItemId = $isPage
                ? $navItem['data']['id']
                : "_$navId";

            $siblings = [];
            foreach ($navItems as $navItemSibling) {
                if ($navItemSibling['type'] === 'page') {
                    $siblings[] = $navItemSibling['data']['id'];
                }
            }

            $childLinks = [];
            if (!empty($navItem['links'])) {
                foreach ($navItem['links'] as $link) {
                    $subNavId = $link['type'] === 'page'
                        ? $link['data']['id']
                        : ++$navId;
                    $childLinks[] = $this->findPageInNavigation($subNavId, $navItem['links'], $navItemId, $navId);
                }
            }

            $pages[$navItemId] = [
                'id' => $navItemId,
                'type' => $navItem['type'],
                'parent_id' => $parentPageId,
                'siblings' => $siblings,
                'children' => $childLinks,
                'label' => $navItem['data']['label'] ?? null,
                'is_public' => $navItem['data']['is_public'] ?? null,
            ];
        }

        return $pages[$pageId] ?? [];
    }

    protected function currentSite(): ?SiteRepresentation
    {
        return $this->view->site ?? $this->view->site = $this->view
            ->getHelperPluginManager()
            ->get('Laminas\View\Helper\ViewModel')
            ->getRoot()
            ->getVariable('site');
    }

    protected function currentPage(): ?SitePageRepresentation
    {
        $view = $this->getView();
        if (isset($view->page)) {
            return $view->page;
        }

        $pageSlug = $view->params()->fromRoute('page-slug');
        if (empty($pageSlug)) {
            return null;
        }

        $site = $this->currentSite();
        // TODO Replace by api->read().
        $page = $view->api()->searchOne('site_pages', ['site_id' => $site->id(), 'slug' => $pageSlug])->getContent();
        $this->view->page = $page;
        return $page;
    }

    protected function currentBlockMetadata(SitePageRepresentation $page): ?SitePageBlockRepresentation
    {
        foreach ($page->blocks() as $block) {
            // TODO A page can belong to multiple types?
            if ($block->layout() === 'pageMetadata') {
                return $block;
            }
        }
        return null;
    }
}
