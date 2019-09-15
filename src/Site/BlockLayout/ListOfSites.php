<?php
namespace BlockPlus\Site\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Zend\View\Renderer\PhpRenderer;

class ListOfSites extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'List of sites'; // @translate
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['listOfSites'];
        $blockFieldset = \BlockPlus\Form\ListOfSitesFieldset::class;

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $sort = $block->dataValue('sort', 'alpha');
        $limit = $block->dataValue('limit');
        $pagination = $limit && $block->dataValue('pagination', false);
        $summaries = $block->dataValue('summaries', true);

        $data = [];
        if ($pagination) {
            $currentPage = $view->params()->fromQuery('page', 1);
            $data['page'] = $currentPage;
            $data['per_page'] = $limit;
        } elseif ($limit) {
            $data['limit'] = $limit;
        }

        switch ($sort) {
            case 'oldest':
                $data['sort_by'] = 'created';
                break;
            case 'newest':
                $data['sort_by'] = 'created';
                $data['sort_order'] = 'desc';
                break;
            default:
            case 'alpha':
                $data['sort_by'] = 'title';
                break;
        }

        $response = $view->api()->search('sites', $data);

        if ($pagination) {
            $totalCount = $response->getTotalResults();
            $view->pagination(null, $totalCount, $currentPage, $limit);
        }

        $sites = $response->getContent();

        $template = $block->dataValue('template') ?: 'common/block-layout/list-of-sites';

        return $view->partial($template, [
            'heading' => $block->dataValue('heading', ''),
            'sites' => $sites,
            'summaries' => $summaries,
            'pagination' => $pagination,
        ]);
    }
}
