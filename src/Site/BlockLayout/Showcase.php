<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;
use Omeka\Stdlib\ErrorStore;

class Showcase extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    use CommonTrait;

    /**
     * The default partial view script.
     *
     * Omeka before 4.1 used "item-showcase" and "file". Now, it is "file" only.
     */
    const PARTIAL_NAME = 'common/block-layout/showcase';

    /**
     * @var \Omeka\Api\Manager
     */
    protected $api;

    /**
     * @param \Laminas\ServiceManager\ServiceLocatorInterface
     */
    protected $services;

    public function __construct(ApiManager $api, ServiceLocatorInterface $services)
    {
        $this->api = $api;
        $this->services = $services;
    }

    public function getLabel()
    {
        return 'Showcase'; // @translate
    }

    public function prepareRender(PhpRenderer $view): void
    {
        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->appendStylesheet($assetUrl('css/block-plus.css', 'BlockPlus'));
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $blockRepresentation = new SitePageBlockRepresentation($block, $this->services);
        $data = $block->getData();
        $data['entries'] = $this->prepareEntries($blockRepresentation);
        $block->setData($data);
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['showcase'];
        $blockFieldset = \BlockPlus\Form\ShowcaseFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        foreach ($data['entries'] as &$entry) {
            $entry = $entry['entry'] ?? '';
        }
        unset($entry);

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        // TODO Include attachments.

        $entries = $this->listEntryResources($view, $block);
        if (!$entries) {
            return '';
        }

        $site = $block->page()->site();
        $layout = $block->dataValue('layout');
        $mediaDisplay = $block->dataValue('media_display');
        $thumbnailType = $block->dataValue('thumbnail_type', 'square');
        $showTitleOption = $block->dataValue('show_title_option', 'item_title');

        $linkType = $view->siteSetting('attachment_link_type', 'item');

        $classes = ['media-embed'];
        $classes[] = $layout === 'horizontal'
            ? 'layout-horizontal'
            : 'layout-vertical';
        $classes[] = $mediaDisplay === 'thumbnail'
            ? 'media-display-thumbnail'
            : 'media-display-embed';
        $classes[] = count($entries) > 3
            ? 'multiple-attachments multiple-entries'
            : 'attachment-count-' . count($entries);

        $vars = [
            'site' => $site,
            'block' => $block,
            'entries' => $entries,
            'thumbnailType' => $thumbnailType,
            'link' => $linkType,
            'linkType' => $linkType,
            'showTitleOption' => $showTitleOption,
            'classes' => $classes,
            'mediaDisplay' => $mediaDisplay,
            'layout' => $layout,
        ];

        return $view->partial($templateViewScript, $vars);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return strip_tags((string) $this->render($view, $block));
    }

    /**
     * Check and prepare entries one time to be stored.
     *
     * @see \Feed\Controller\FeedController::appendEntries()
     * @todo Better management of Clean url.
     */
    protected function prepareEntries(SitePageBlockRepresentation $block): array
    {
        $entries = $block->dataValue('entries');

        // TODO ArrayTextarea may not be filtered here yet.
        $entries = is_array($entries)
            ? $entries
            : array_map('trim', explode("\n", $this->fixEndOfLine(trim((string) $entries))));
        if (!$entries) {
            return [];
        }

        $page = $block->page();
        $site = $page->site();
        $currentSiteId = (int) $site->getId();
        $currentSiteSlug = $site->getSlug();

        $baseEntry = [
            'entry' => null,
            'resource' => null,
            'resource_name' => null,
            'site' => null,
            // "data" may be appended for external resource.
        ];

        $result = [];
        foreach ($entries as $entry) {
            $normEntry = $baseEntry;
            $normEntry['entry'] = $entry;
            // Keep empty entry as possible separator.
            if (!$entry) {
                $result[] = $normEntry;
                continue;
            }

            $cleanEntry = trim($entry, '/');

            // Resource?
            if (is_numeric($cleanEntry)) {
                try {
                    $resource = $this->api->read('resources', ['id' => (int) $cleanEntry])->getContent();
                    $normEntry['resource_name'] = $resource->resourceName();
                    $normEntry['resource'] = $resource->id();
                } catch (NotFoundException $e) {
                    // Skip.
                }
                $result[] = $normEntry;
                continue;
            }

            // External resource?
            if (mb_substr($entry, 0, 8) === 'https://' || mb_substr($entry, 0, 7) === 'http://') {
                [$url, $asset, $title, $caption, $body] = array_map('trim', explode('=', $entry, 5)) + ['', '', '', '', ''];
                $normEntry['data'] = [
                    'url' => $url,
                    'asset' => $asset,
                    'title' => $title,
                    'caption' => $caption,
                    'body' => $body,
                ];
                if (($asset . $title . $caption . $body) === '' && strpos(trim($entry), ' ')) {
                    [$normEntry['data']['url'], $normEntry['data']['title']] = explode(' ', $entry, 2);
                }
                $result[] = $normEntry;
                continue;
            }

            // Site or page of this site?
            if (!mb_strpos($cleanEntry, '/')) {
                try {
                    $resource = $this->api->read('sites', ['slug' => $cleanEntry])->getContent();
                    $normEntry['resource_name'] = 'sites';
                    $normEntry['resource'] = $resource->id();
                    $normEntry['site'] = $resource->id();
                } catch (NotFoundException $e) {
                    try {
                        $resource = $this->api->read('site_pages', ['site' => $currentSiteId, 'slug' => $cleanEntry])->getContent();
                        $normEntry['resource_name'] = 'site_pages';
                        $normEntry['resource'] = $resource->id();
                        $normEntry['site'] = $currentSiteId;
                    } catch (NotFoundException $e) {
                        // May be a browse page or a special page.
                    }
                }
                $result[] = $normEntry;
                continue;
            }

            // When the user wants to link a resource on another site: "/s/site/item/1".
            // Manage "item/1", "asset/1", etc. too.
            // TODO Manage the case where the name is not "page" with Clean url.
            $matches = [];
            // Take care of sub-path.
            $r = preg_match('~(?:/?(?:s/)?([^/]+)/)?(pages|page|assets|asset|item_sets|item-set|items|item|media|annotations|annotation)/([^;\?\#]+)~', $entry, $matches);
            if (!$r) {
                $part = mb_strpos($entry, '/') === 0 ? mb_substr($entry, 1) : $entry;
                $matches = [
                    '/s/' . $currentSiteSlug . '/page/' . $part,
                    $currentSiteSlug,
                    'page',
                    $part,
                ];
            }

            $entrySiteId = null;
            if (empty($matches[1]) || $matches[1] === $currentSiteSlug) {
                $entrySiteId = $currentSiteId;
            } else {
                try {
                    $entrySite = $this->api->read('sites', ['slug' => $matches[1]])->getContent();
                    $entrySiteId = $entrySite->id();
                } catch (NotFoundException $e) {
                    $result[] = $normEntry;
                    continue;
                }
            }
            if ($matches[2] === 'page' || $matches[2] === 'pages') {
                try {
                    $page = $this->api->read('site_pages', ['site' => $entrySiteId, 'slug' => $matches[3]])->getContent();
                    $normEntry['resource_name'] = 'site_pages';
                    $normEntry['resource'] = $page->id();
                    $normEntry['site'] = $entrySiteId;
                } catch (NotFoundException $e) {
                    // Something else.
                }
            } elseif ($matches[2] === 'asset' || $matches[2] === 'assets') {
                try {
                    $asset = $this->api->read('assets', ['id' => (int) $matches[3]])->getContent();
                    $normEntry['resource_name'] = 'assets';
                    $normEntry['resource'] = $asset->id();
                    $normEntry['site'] = $entrySiteId;
                } catch (NotFoundException $e) {
                    // Something else.
                }
            } elseif (is_numeric($matches[3])) {
                try {
                    $resource = $this->api->read('resources', ['id' => (int) $matches[3]])->getContent();
                    $normEntry['resource_name'] = $resource->resourceName();
                    $normEntry['resource'] = $resource->id();
                    $normEntry['site'] = $entrySiteId;
                } catch (NotFoundException $e) {
                    // Something else.
                }
            }
            $result[] = $normEntry;
        }

        return $result;
    }

    protected function listEntryResources(PhpRenderer $view, SitePageBlockRepresentation $block): array
    {
        $entries = $block->dataValue('entries');
        if (!$entries) {
            return [];
        }

        /**
         * @var \Common\Stdlib\EasyMeta $easyMeta
         * @var \BlockPlus\View\Helper\PageMetadata $pageMetadata
         */
        $easyMeta = $this->services->get('Common\EasyMeta');
        $plugins = $view->getHelperPluginManager();
        $siteLang = $plugins->get('lang')();
        $hyperlink = $plugins->get('hyperlink');
        $thumbnail = $plugins->get('thumbnail');
        $siteSetting = $plugins->get('siteSetting');
        $pageMetadata = $plugins->get('pageMetadata');

        $link = $siteSetting('attachment_link_type', 'item');
        $linkType = $link;

        $mediaDisplay = $block->dataValue('media_display');
        $thumbnailType = $block->dataValue('thumbnail_type', 'square');
        $showTitleOption = $block->dataValue('show_title_option', 'item_title');
        $showTitle = $showTitleOption && $showTitleOption !== 'no_title';

        $filterLocale = (bool) $siteSetting('filter_locale_values');
        $lang = $filterLocale ? $siteLang : null;

        $page = $block->page();
        $site = $page->site();
        $currentSiteSlug = $site->slug();

        $baseEntry = [
            'entry' => null,
            'resource' => null,
            'resource_name' => null,
            'resource_type' => null,
            'site' => null,
            'render' => null,
            'title' => null,
            'url' => null,
            'link_class' => null,
            'caption' => null,
            'body' => null,
        ];

        foreach ($entries as &$entry) {
            $entry = array_replace($baseEntry, $entry);
            // The site may be private or removed.
            if (!empty($entry['site']) && is_numeric($entry['site'])) {
                try {
                    $entry['site'] = $this->api->read('sites', ['id' => $entry['site']])->getContent();
                } catch (NotFoundException $e) {
                    $entry['site'] = null;
                }
            }

            // The resource may be private or removed.
            if (!empty($entry['resource']) && is_numeric($entry['resource'] && !empty($entry['resource_name']))) {
                try {
                    $entry['resource'] = $this->api->read($entry['resource_name'], ['id' => $entry['resource']])->getContent();
                } catch (NotFoundException $e) {
                    // Something else or private resource.
                    $entry['resource_name'] = null;
                    $entry['resource'] = null;
                }
            }

            // The resource may be removed.
            if (!empty($entry['data']['asset']) && is_numeric($entry['data']['asset'])) {
                try {
                    $entry['data']['asset'] = $this->api->read('assets', ['id' => $entry['data']['asset']])->getContent();
                } catch (NotFoundException $e) {
                    $entry['data']['asset'] = null;
                }
            }

            // Prefill entry data that are needed in template.

            $resource = $entry['resource'];
            if (empty($resource)) {
                // Check for external data.
                if (empty($entry['data'])) {
                    continue;
                }
                $entry['resource_type'] = 'link';
                $entry['link_class'] = 'link';
                /**
                 * Reset variables first.
                 * @var string $url
                 * @var \Omeka\Api\Representation\AssetRepresentation $asset
                 * @var string $title
                 * @var string $caption
                 * @var string $body
                 */
                $url = null;
                $asset = null;
                $title = null;
                $entry['caption'] = null;
                $body = null;
                extract($entry['data']);
                $entry['url'] = $url;
                $entry['title'] = $showTitle ? $title : null;
                $entry['caption'] = $caption;
                $entry['body'] = $body;
                $entry['render'] = is_object($asset) ? $thumbnail($asset, $thumbnailType) : null;
            } elseif (!is_object($resource)) {
                // In the case that the resource is private, or it may be an
                // unidentified relative url.
                continue;
            } elseif ($resource instanceof \Omeka\Api\Representation\SiteRepresentation) {
                $entry['resource_type'] = 'site';
                $entry['link_class'] = 'site-link';
                $entry['title'] = $showTitle ? $resource->title() : null;
                $entry['url'] = $resource->siteUrl();
                $entry['caption'] = $resource->summary();
                $entryThumbnail = $resource->thumbnail();
                if ($entryThumbnail) {
                    $entry['render'] = $thumbnail($entryThumbnail, $thumbnailType, ['class' => 'site-thumbnail-image']);
                }
            } elseif ($resource instanceof \Omeka\Api\Representation\SitePageRepresentation) {
                $entry['resource_type'] = 'site-page';
                $entry['link_class'] = 'site-page-link';
                $entry['title'] = $showTitle ? $title = $resource->title() : null;
                $entry['url'] = $resource->siteUrl();
                $entry['caption'] = $pageMetadata('summary', $resource);
                $entryThumbnail = $pageMetadata('main_image', $resource);
                if ($entryThumbnail) {
                    $entry['render'] = $thumbnail($entryThumbnail, $thumbnailType, ['class' => 'site-page-thumbnail-image']);
                }
            } elseif ($resource instanceof \Omeka\Api\Representation\AssetRepresentation) {
                $entry['resource_type'] = 'asset';
                $entry['link_class'] = 'asset-link';
                $entry['title'] = $showTitle ? $resource->altText() : null;
                $entry['url'] = $resource->assetUrl();
                $entry['render'] = $thumbnail($resource, $thumbnailType);
            } else {
                // Standard resource.
                $resourceType = $resource->getControllerName();
                $entry['resource_type'] = $resourceType;
                $media = $resource->primaryMedia();
                if (!$showTitleOption || $showTitleOption === 'no_title') {
                    $entry['link_class'] = 'resource-link';
                } elseif ($resourceType === 'media' && $showTitleOption == 'file_name') {
                    $entry['link_class'] = 'media-file';
                    $entry['title'] = $media->displayTitle(null, $lang);
                } else {
                    $entry['link_class'] = 'resource-link';
                    $entry['title'] = $resourceType === 'media'
                        ? $resource->item()->displayTitle(null, $lang)
                        : $resource->displayTitle(null, $lang);
                }
                $resourceSiteSlug = is_object($entry['site']) ? $entry['site']->slug() : $currentSiteSlug;
                if ($resourceType === 'media') {
                    if ($linkType === 'media') {
                        $entry['url'] = $media->siteUrl($resourceSiteSlug);
                    } elseif ($linkType === 'original' && $media->hasOriginal()) {
                        $entry['url'] = $media->originalUrl();
                    } else {
                        $entry['url'] = $resource->siteUrl($resourceSiteSlug);
                    }
                } else {
                    $entry['url'] = $resource->siteUrl($resourceSiteSlug);
                }
                $entry['caption'] = $resource->displayDescription(null, $lang);
                // Manage the option "media display" even for item.
                if ($mediaDisplay === 'thumbnail' || !$media) {
                    $entry['render'] = $hyperlink->raw($thumbnail($media, $thumbnailType), $entry['url'], ['class' => $entry['link_class']]);
                    // TODO Made possible to render an item.
                } else {
                    $entry['render'] = $media->render([
                        'thumbnailType' => $thumbnailType,
                        'link' => $linkType,
                    ]);
                }
            };
        }
        unset($entry);

        return $entries;
    }
}
