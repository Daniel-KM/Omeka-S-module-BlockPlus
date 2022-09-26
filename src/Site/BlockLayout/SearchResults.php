<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;

class SearchResults extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/search-results';

    public function getLabel()
    {
        return 'Search form and results'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = $block->getData() + ['query' => []];
        if (empty($data['query'])) {
            $data['query'] = [];
        } elseif (!is_array($data['query'])) {
            $query = [];
            parse_str(ltrim($data['query'], "? \t\n\r\0\x0B"), $query);
            $data['query'] = $query;
        }
        $block->setData($data);
    }

    public function prepareForm(PhpRenderer $view): void
    {
        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->prependStylesheet($assetUrl('css/advanced-search.css', 'Omeka'))
            ->appendStylesheet($assetUrl('css/query-form.css', 'Omeka'));
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['searchResults'];
        $blockFieldset = \BlockPlus\Form\SearchResultsFieldset::class;

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $data['query'] = http_build_query($data['query'], '', '&', PHP_QUERY_RFC3986);

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

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        // Similar to BrowsePreview::render(), but with a different query.

        $resourceType = $block->dataValue('resource_type', 'items');

        $defaultQuery = $block->dataValue('query', []) + ['search' => ''];
        $query = $view->params()->fromQuery() + $defaultQuery;

        $site = $block->page()->site();
        if ($view->siteSetting('browse_attached_items', false)) {
            $query['site_attachments_only'] = true;
        }

        // Allow to force to display resources from another site.
        if (empty($query['site_id'])) {
            $query['site_id'] = $site->id();
        }

        $limit = $block->dataValue('limit', 12) ?: 12;

        // Unlike browse preview, the pagination is always prepared, even if it
        // not displayed in the view.
        $showPagination = $limit && $block->dataValue('pagination');

        $currentPage = $view->params()->fromQuery('page', 1);
        $query['page'] = $currentPage;
        $query['per_page'] = $limit;

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

        /** @var \Omeka\Api\Response $response */
        $api = $view->api();
        $response = $api->search($resourceType, $query);

        // TODO Currently, there can be only one pagination by page.
        $totalCount = $response->getTotalResults();
        $view->pagination(null, $totalCount, $currentPage, $limit);

        /** @var \Omeka\Api\Representation\ResourceTemplateRepresentation $resourceTemplate */
        $resourceTemplate = $block->dataValue('resource_template');
        if ($resourceTemplate) {
            try {
                $resourceTemplate = $api->read('resource_templates', $resourceTemplate)->getContent();
            } catch (\Exception $e) {
            }
        }

        $sortHeadings = $block->dataValue('sort_headings', []);
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

        $vars = [
            'heading' => $block->dataValue('heading'),
            'resourceType' => $resourceTypes[$resourceType],
            'resources' => $resources,
            'query' => $query,
            'pagination' => $showPagination,
            'sortHeadings' => $sortHeadings,
        ];
        $template = $block->dataValue('template', self::PARTIAL_NAME);
        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }
}
