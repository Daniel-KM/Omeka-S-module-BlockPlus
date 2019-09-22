<?php
namespace BlockPlus\Site\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

class BrowsePreview extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Browse preview'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();
        $data['query'] = ltrim($data['query'], '? ');
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
        $resourceType = $block->dataValue('resource_type', 'items');

        // The trim is kept for compatibility with old core blocks.
        $query = [];
        parse_str(ltrim($block->dataValue('query'), '? '), $query);
        $originalQuery = $query;

        $site = $block->page()->site();
        if ($view->siteSetting('browse_attached_items', false)) {
            $query['site_attachments_only'] = true;
        }

        $query['site_id'] = $site->id();

        $limit = $block->dataValue('limit', 12);
        $pagination = $limit && $block->dataValue('pagination');
        if ($pagination) {
            $currentPage = $view->params()->fromQuery('page', 1);
            $query['page'] = $currentPage;
            $query['per_page'] = $limit;
        } elseif ($limit) {
            $query['limit'] = $limit;
        }

        if (!isset($query['sort_by'])) {
            $query['sort_by'] = 'created';
        }
        if (!isset($query['sort_order'])) {
            $query['sort_order'] = 'desc';
        }

        /** @var \Omeka\Api\Response $response */
        $response = $view->api()->search($resourceType, $query);

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

        $resources = $response->getContent();

        $resourceTypes = [
            'items' => 'item',
            'item_sets' => 'item-set',
            'media' => 'media',
        ];

        $template = $block->dataValue('template') ?: 'common/block-layout/browse-preview';

        // There is no list of media in public views.
        $linkText = $resourceType === 'media' ? '' : $block->dataValue('link-text');

        return $view->partial($template, [
            'resourceType' => $resourceTypes[$resourceType],
            'resources' => $resources,
            'heading' => $block->dataValue('heading'),
            'linkText' => $linkText,
            'query' => $originalQuery,
            'pagination' => $pagination,
        ]);
    }
}
