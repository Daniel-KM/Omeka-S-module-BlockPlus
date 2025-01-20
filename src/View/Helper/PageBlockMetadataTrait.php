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
        ],
        'block_metadata' => [
            'block',
            null,
            'type',
            'credits',
            'summary',
            'tags',
            'type_label',
            'featured',
            'cover',
            'cover_url',
            'attachments',
            'first_image',
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

        switch ($metadata) {
            case 'block':
            case is_null($metadata):
                return $block;

            case 'page':
                return $page;
            case 'title':
                return $page->title();
            case 'slug':
                return $page->slug();

            case 'theme_dir':
                return OMEKA_PATH . '/themes/' . $this->currentSite()->theme();

            case 'template':
            case 'template_name':
                return $page->layoutDataValue('template_name') ?: null;

            case 'type':
                $view->logger()->warn('Since Omeka S 4.1, the metadata "type" is replaced by the page template name (key "template"). Check your theme.'); // @translate
                return $page->layoutDataValue('template_name') ?: null;

            case 'credits':
            case 'summary':
            case 'tags':
                return $block
                    ? $block->dataValue($metadata)
                    : null;

            case 'type_label':
                if (!$block) {
                    return null;
                }
                $type = $block->dataValue('type');
                $pageTypes = $view->siteSetting('blockplus_page_types', []);
                return $pageTypes[$type] ?? null;

            case 'featured':
                return $block
                    ? (bool) $block->dataValue('featured')
                    : false;
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
                // @deprecated Use "main_image", not "first_image".
            case 'main_image':
                if (!$block) {
                    return null;
                }
                $api = $view->api();
                // Check if acover is defined in the current block.
                $asset = $block->dataValue('cover');
                if ($asset) {
                    try {
                        return $api->read('assets', ['id' => $asset])->getContent();
                    } catch (NotFoundException $e) {
                    }
                }
                // Search for a block pageMetadata or asset/
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
                if (!$block) {
                    return null;
                }
                return $block->dataValue('params', '');
            case 'params_json':
            case 'params_json_array':
                if (!$block) {
                    return [];
                }
                return @json_decode($block->dataValue('params', ''), true) ?: [];
            case 'params_json_object':
                if (!$block) {
                    return (object) [];
                }
                return @json_decode($block->dataValue('params', '')) ?: (object) [];
            case 'params_ini':
                $reader = new \Laminas\Config\Reader\Ini();
                return $reader->fromString($block->dataValue('params', ''));
            case 'params_key_value_array':
                if (!$block) {
                    return [];
                }
                $params = array_map('trim', explode("\n", trim($block->dataValue('params', ''))));
                $list = [];
                foreach ($params as $keyValue) {
                    $list[] = array_map('trim', explode('=', $keyValue, 2)) + ['', ''];
                }
                return $list;
            case 'params_key_value':
                if (!$block) {
                    return [];
                }
                // no break
            default:
                if (!$block) {
                    return null;
                }
                $params = array_filter(array_map('trim', explode("\n", trim($block->dataValue('params', '')))), 'strlen');
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
