<?php declare(strict_types=1);
namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;

class BrowsePreview extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/browse-preview';

    public function getLabel()
    {
        return 'Browse preview'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = $block->getData();
        $data['query'] = ltrim($data['query'], "? \t\n\r\0\x0B");
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['browsePreview'];
        $blockFieldset = \BlockPlus\Form\BrowsePreviewFieldset::class;

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        // Similar to SearchResults::render().

        $resourceType = $block->dataValue('resource_type', 'items');

        // The trim is kept for compatibility with old core blocks.
        $query = [];
        parse_str(ltrim($block->dataValue('query'), "? \t\n\r\0\x0B"), $query);
        $originalQuery = $query;

        $site = $block->page()->site();
        if ($view->siteSetting('browse_attached_items', false)) {
            $query['site_attachments_only'] = true;
        }

        // Allow to force to display resources from another site.
        if (empty($query['site_id'])) {
            $query['site_id'] = $site->id();
        }

        $limit = $block->dataValue('limit', 12);
        $pagination = $limit && $block->dataValue('pagination');
        if ($pagination) {
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

        /** @var \Omeka\Api\Response $response */
        $api = $view->api();
        $response = $api->search($resourceType, $query);

        // TODO Currently, there can be only one pagination by page.
        if ($pagination) {
            $totalCount = $response->getTotalResults();
            $pagination = [
                'total_count' => $totalCount,
                'current_page' => $currentPage,
                'limit' => $limit,
            ];
            $view->pagination(null, $totalCount, $currentPage, $limit);
        }

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

        // There is no list of media in public views.
        $linkText = $resourceType === 'media' ? '' : $block->dataValue('link-text');

        $vars = [
            'resourceType' => $resourceTypes[$resourceType],
            'resources' => $resources,
            'heading' => $block->dataValue('heading'),
            'linkText' => $linkText,
            'query' => $originalQuery,
            'pagination' => $pagination,
            'sortHeadings' => $sortHeadings,
        ];
        $template = $block->dataValue('template', self::PARTIAL_NAME);
        return $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }
}
