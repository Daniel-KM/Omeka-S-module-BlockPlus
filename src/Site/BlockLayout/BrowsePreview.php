<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\HtmlPurifier;
use Omeka\Site\BlockLayout\BrowsePreview as OmekaBrowsePreview;

/**
 * This is a laminas delegator to be able to inject HtmlPurifier.
 * Indeed, a constructor is needed to prepare HtmlPurifier, but it cannot be
 * loaded,because browsePreview is already an invokable.
 */
class BrowsePreview extends OmekaBrowsePreview
{
    use CommonTrait;

    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/browse-preview';

    /**
     * @var \Omeka\Site\BlockLayout\BrowsePreview
     */
    protected $browsePreview;

    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    public function __construct(OmekaBrowsePreview $browsePreview, HtmlPurifier $htmlPurifier)
    {
        $this->browsePreview = $browsePreview;
        $this->htmlPurifier = $htmlPurifier;
    }

    public function getLabel()
    {
        return 'Browse preview'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = $block->getData();

        $data['query'] = ltrim($data['query'], "? \t\n\r\0\x0B");

        $data['html'] = isset($data['html'])
            ? $this->fixEndOfLine($this->htmlPurifier->purify($data['html']))
            : '';

        $block->setData($data);
    }

    public function prepareForm(PhpRenderer $view): void
    {
        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->prependStylesheet($assetUrl('css/advanced-search.css', 'Omeka'));
        $view->headScript()
            ->appendFile($assetUrl('js/advanced-search.js', 'Omeka'))
            ->appendFile($assetUrl('js/query-form.js', 'Omeka'))
            ->appendFile($assetUrl('js/browse-preview-block-layout.js', 'Omeka'));
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['browsePreview'];
        $blockFieldset = \BlockPlus\Form\BrowsePreviewFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->get('o:block[__blockIndex__][o:data][query]')
            ->setOption('query_resource_type', $data['resource_type'] ?? 'items');
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        // Similar to SearchResults::render().

        $data = $block->data();
        $resourceType = $data['resource_type'] ?? 'items';

        $query = [];
        parse_str($data['query'] ?? '', $query);
        $originalQuery = $query;

        $site = $block->page()->site();
        if ($view->siteSetting('browse_attached_items', false)) {
            $query['site_attachments_only'] = true;
        }

        // Allow to force to display resources from another site.
        if (empty($query['site_id'])) {
            $query['site_id'] = $site->id();
        }

        $pagination = null;
        $limit = $data['limit'] ?? 12;
        $usePagination = $limit && !empty($data['pagination']) ;
        if ($usePagination) {
            $currentPage = $view->params()->fromQuery('page', 1);
            $query['page'] = $currentPage;
            $query['per_page'] = $limit;
        } elseif ($limit) {
            $query['limit'] = $limit;
        }

        $sortBy = $view->params()->fromQuery('sort_by');
        if ($sortBy) {
            $query['sort_by'] = $sortBy;
        } elseif (!isset($query['sort_by'])) {
            $query['sort_by'] = 'created';
        }

        $sortOrder = $view->params()->fromQuery('sort_order');
        if ($sortOrder) {
            $query['sort_order'] = $sortOrder;
        } elseif (!isset($query['sort_order'])) {
            $query['sort_order'] = 'desc';
        }

        //Show all resource components if none set
        $components = empty($data['components'])
            ? ['resource-heading', 'resource-body', 'thumbnail']
            : $data['components'];

        /** @var \Omeka\Api\Response $response */
        $api = $view->api();
        $response = $api->search($resourceType, $query);

        // TODO Currently, there can be only one pagination by page.
        if ($usePagination) {
            $totalCount = $response->getTotalResults();
            $pagination = [
                'total_count' => $totalCount,
                'current_page' => $currentPage,
                'limit' => $limit,
            ];
            $view->pagination(null, $totalCount, $currentPage, $limit);
        }

        /** @var \Omeka\Api\Representation\ResourceTemplateRepresentation $resourceTemplate */
        $resourceTemplate = $data['resource_template'] ?? null;
        if ($resourceTemplate) {
            try {
                $resourceTemplate = $api->read('resource_templates', $resourceTemplate)->getContent();
            } catch (\Exception $e) {
            }
        }

        $sortHeadings = $data['sort_headings'] ?? [];
        if ($sortHeadings) {
            $translate = $view->plugin('translate');
            foreach ($sortHeadings as $key => $sortHeading) {
                switch ($sortHeading) {
                    case 'created':
                        $label = $translate('Created'); // @translate
                        break;
                    case 'resource_class_label':
                        $label = $translate('Class'); // @translate
                        break;
                    default:
                        $property = $api->searchOne('properties', ['term' => $sortHeading])->getContent();
                        if ($property) {
                            if ($resourceTemplate) {
                                $templateProperty = $resourceTemplate->resourceTemplateProperty($property->id());
                                if ($templateProperty) {
                                    $label = $translate($templateProperty->alternateLabel() ?: $property->label());
                                    break;
                                }
                            }
                            $label = $translate($property->label());
                        } else {
                            unset($sortHeadings[$key]);
                            continue 2;
                        }
                        break;
                }
                $sortHeadings[$key] = [
                    'label' => $label,
                    'value' => $sortHeading,
                ];
            }
            $sortHeadings = array_filter($sortHeadings);
        }

        $resources = $response->getContent();

        $resourceTypes = [
            'items' => 'item',
            'item_sets' => 'item-set',
            'media' => 'media',
        ];

        // There is no list of media in public views.
        $linkText = $resourceType === 'media' ? '' : ($data['link-text'] ?? '');

        $vars = [
            'block' => $block,
            'site' => $site,
            'resourceType' => $resourceTypes[$resourceType],
            'resources' => $resources,
            'heading' => $data['heading'] ?? '',
            'html' => $data['html'] ?? '',
            'linkText' => $linkText,
            'components' => $components,
            'query' => $originalQuery,
            'pagination' => $pagination,
            'sortHeadings' => $sortHeadings,
        ];

        $template = $data['template'] ?? self::PARTIAL_NAME;
        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }
}
