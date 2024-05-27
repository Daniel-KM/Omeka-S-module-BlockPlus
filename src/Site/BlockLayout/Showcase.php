<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

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
use Omeka\Stdlib\HtmlPurifier;

class Showcase extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    use CommonTrait;

    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/showcase';

    /**
     * @var ApiManager
     */
    protected $api;

    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    public function __construct(ApiManager $api, HtmlPurifier $htmlPurifier)
    {
        $this->api = $api;
        $this->htmlPurifier = $htmlPurifier;
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
        $data = $block->getData();
        if (empty($data['entries'])) {
            $data['entries'] = [];
        } else {
            $data['entries'] = $this->listEntries(
                // TODO ArrayTextarea is not yet filtered here.
                is_array($data['entries']) ? $data['entries'] : array_map('trim', explode("\n", $this->fixEndOfLine(trim($data['entries'])))),
                $block->getPage()->getSite()
            );
        }
        $data['html'] = $this->fixEndOfLine($this->htmlPurifier->purify(trim($data['html'])));
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
        $site = $block->page()->site();
        $vars = [
            'site' => $site,
            'block' => $block,
            'heading' => $block->dataValue('heading', ''),
            'html' => $block->dataValue('html', ''),
            'entries' => $this->listEntryResources($block->dataValue('entries', []) ?? [], $site),
            'thumbnailType' => $block->dataValue('thumbnail_type', 'square'),
            'showTitleOption' => $block->dataValue('show_title_option', 'item_title'),
        ];
        $template = $block->dataValue('template', self::PARTIAL_NAME);
        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }

    /**
     * @see \Feed\Controller\FeedController::appendEntries()
     * @todo Better management of Clean url.
     */
    protected function listEntries(array $entries, \Omeka\Entity\Site $site): array
    {
        $result = [];

        $currentSiteId = (int) $site->getId();
        $currentSiteSlug = $site->getSlug();

        $baseEntry = [
            'entry' => null,
            'resource_name' => null,
            'resource' => null,
            'site' => null,
        ];

        foreach ($entries as $entry) {
            $normEntry = $baseEntry;
            $normEntry['entry'] = $entry;
            // Keep empty entries as possible separator.
            if (!$entry) {
                $result[] = $normEntry;
                continue;
            }

            $cleanEntry = trim($entry, '/');
            // Resource?
            if (is_numeric($cleanEntry)) {
                try {
                    $resource = $this->api->read('resources', ['id' => $cleanEntry])->getContent();
                    $normEntry['resource_name'] = $resource->resourceName();
                    $normEntry['resource'] = $resource->id();
                } catch (NotFoundException $e) {
                }
                $result[] = $normEntry;
                continue;
            }

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

    protected function listEntryResources(array $entries): array
    {
        foreach ($entries as &$entry) {
            // The site may be private for the user.
            if (!empty($entry['site'])) {
                try {
                    $entry['site'] = $this->api->read('sites', ['id' => $entry['site']])->getContent();
                } catch (NotFoundException $e) {
                    $entry['site'] = null;
                }
            }

            if (!empty($entry['resource_name']) && !empty($entry['resource'])) {
                try {
                    $entry['resource'] = $this->api->read($entry['resource_name'], ['id' => $entry['resource']])->getContent();
                } catch (NotFoundException $e) {
                    // Something else or private resource.
                    $entry['resource_name'] = null;
                    $entry['resource'] = null;
                }
            }

            if (!empty($entry['data']['asset']) && is_numeric($entry['data']['asset'])) {
                try {
                    $entry['data']['asset'] = $this->api->read('assets', ['id' => $entry['data']['asset']])->getContent();
                } catch (NotFoundException $e) {
                    $entry['data']['asset'] = null;
                }
            }
        }
        unset($entry);

        return $entries;
    }
}
