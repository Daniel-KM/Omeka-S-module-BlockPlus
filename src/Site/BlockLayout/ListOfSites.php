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
            case 'alpha':
            default:
                $data['sort_by'] = 'title';
                break;
        }

        $exclude = $block->dataValue('exclude');
        if ($exclude) {
            $sites = $this->limitSites($view, $data, $exclude);
        } else {
            $response = $view->api()->search('sites', $data);
            if ($pagination) {
                $totalCount = $response->getTotalResults();
                $view->pagination(null, $totalCount, $currentPage, $limit);
            }
            $sites = $response->getContent();
        }

        $template = $block->dataValue('template') ?: 'common/block-layout/list-of-sites';

        return $view->partial($template, [
            'heading' => $block->dataValue('heading', ''),
            'sites' => $sites,
            'summaries' => $summaries,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Get the list of sites without excluded sites.
     *
     * @param PhpRenderer $view
     * @param array $query
     * @param array $exclude
     * @return \Omeka\Api\Representation\SiteRepresentation[]
     */
    protected function limitSites(PhpRenderer $view, array $query, array $exclude)
    {
        // Because the number of sites is generally small (less than 100),
        // because when the number of sites is bigger (more than 1000) the
        // server is generally bigger, and because it may be heavy to create a
        // filter for a new query variable that will be used only here, the
        // exclusion is made via php via a fetch of all data sites.
        // It may be improved if there is a table for group of sites.

        $sitesToExclude = [];

        /** @var \Omeka\Api\Representation\SiteRepresentation $currentSite */
        $currentSite = $view->vars('site');

        // The view api() doesn't allow to return scalar or any other options,
        // so the full api is used.
        $services = $currentSite->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');

        $data = $query;
        $pagination = !empty($query['page']);
        $limit = $pagination ? (int) $query['per_page'] : (int) @$query['limit'];

        unset($data['page']);
        unset($data['per_page']);
        unset($data['limit']);
        $siteIds = $api->search('sites', $data, ['returnScalar' => 'id'])->getContent();

        $excludeMainSite = in_array('main', $exclude);
        if ($excludeMainSite) {
            $mainSiteId = (int) $view->setting('default_site');
            if ($mainSiteId) {
                $sitesToExclude[] = $mainSiteId;
            } else {
                $excludeMainSite = false;
            }
        }

        $excludeCurrentSite = in_array('current', $exclude);
        if ($excludeCurrentSite) {
            $currentSiteId = $currentSite->id();
            if ($excludeMainSite && $currentSiteId === $mainSiteId) {
                $excludeCurrentSite = false;
            } else {
                $sitesToExclude[] = $currentSiteId;
            }
        }

        // Keep only one site by group.
        $excludeTranslatedSites = in_array('translated', $exclude);
        if ($excludeTranslatedSites) {
            $siteGroups = $view->setting('internationalisation_site_groups', []);
            $slugs = $api->search('sites', $data, ['returnScalar' => 'slug'])->getContent();
            $siteIds = array_combine($slugs, $siteIds);
            if ($excludeMainSite) {
                $mainSiteSlug = array_search($mainSiteId, $siteIds);
            }
            if ($excludeCurrentSite) {
                $currentSiteSlug = array_search($currentSiteId, $siteIds);
            }

            // TODO Try to keep the site with the current locale. Is it useful as it may be managed automatically in view?
            // $currentLanguage = $view->siteSetting('locale');
            foreach ($siteGroups as $group) {
                if ($excludeMainSite && in_array($mainSiteSlug, $group)) {
                    // Nothing to do: remove all sites of the group.
                } elseif ($excludeCurrentSite && in_array($currentSiteSlug, $group)) {
                    // Nothing to do: remove all sites of the group.
                } else {
                    // Keep the first site. It shall be the one with the locale
                    // of the current site, if any.
                    array_shift($group);
                }

                // Convert the group of site slugs into a group of site ids.
                $groupIds = array_intersect_key($siteIds, array_flip($group));
                $sitesToExclude = array_merge($sitesToExclude, array_values($groupIds));
            }
        }

        $sitesToExclude = array_unique($sitesToExclude);
        $siteIds = array_diff($siteIds, $sitesToExclude);

        if ($pagination) {
            $page = $query['page'];
            $offset = ($page - 1) * $limit;
            $view->pagination(null, count($siteIds), $page, $limit);
            $siteIds = array_slice($siteIds, $offset, $limit);
        } elseif ($limit) {
            $siteIds = array_slice($siteIds, 0, $limit);
        }

        /** @var \Omeka\Api\Adapter\SiteAdapter $siteAdapter */
        $adapter = $services->get('Omeka\ApiAdapterManager')->get('sites');
        $repository = $adapter->getEntityManager()->getRepository(\Omeka\Entity\Site::class);
        $order = [$query['sort_by'] => empty($query['sort_order']) ? 'asc' : $query['sort_order']];
        $sites = $repository->findBy(['id' => $siteIds], $order);

        // Convert to an array of representations.
        $sites = array_map(function($v) use($adapter) {
            return $adapter->getRepresentation($v);
        }, $sites);

        return $sites;
    }
}
