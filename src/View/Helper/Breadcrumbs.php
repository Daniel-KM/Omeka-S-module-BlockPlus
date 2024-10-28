<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\Navigation\Navigation;
use Laminas\Navigation\Page\AbstractPage;
use Laminas\Router\Http\RouteMatch;
use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\ItemSetRepresentation;
use Omeka\Api\Representation\SiteRepresentation;

class Breadcrumbs extends AbstractHelper
{
    /**
     * @var string
     */
    protected $defaultTemplate = 'common/breadcrumbs';

    /**
     * @var array
     */
    protected $crumbs;

    /**
     * @var \Omeka\View\Helper\Api
     */
    protected $api;

    /**
     * Prepare the breadcrumb via a partial for resources and pages.
     *
     * For pages, the output is the same than the default Omeka breadcrumbs.
     *
     * @todo Manage the case where the home page is not a page and the editor doesn't want breadcrumb on it.
     *
     * @todo Build a real navigation container then check rights automatically.
     * @link https://docs.laminas.dev/laminas-navigation/helpers/breadcrumbs/
     *
     * @params array $options Managed options:
     * - home (bool) Prepend home (true by default)
     * - prepend (array) A list of crumbs to insert after home
     * - collections (bool) Insert a link to the list of collections
     * - collections_url (string) Url to use for the link to collections
     * - itemset (bool) Insert the first item set as crumb for an item (true by
     *   default)
     * - itemsetstree (bool) Insert the item set tree as crumb for an item (true
     *   by default, require module ItemSetsTree)
     * - current (bool) Append current resource if any (true by default; always
     *   true for pages currently)
     * - property_itemset (string) Property where is set the first parent item
     *   set of an item when they are multiple.
     * - homepage (bool) Display the breadcrumbs on the home page (false by
     *   default)
     * - separator (string) Separator, escaped for html (no default: use css)
     * - template (string) The partial to use (default: "common/breadcrumbs")
     * Options are passed to the partial too.
     * @return string The html breadcrumb.
     */
    public function __invoke(array $options = [])
    {
        /**
         * @var \Laminas\View\Renderer\PhpRenderer $view
         * @var \Omeka\Api\Representation\SiteRepresentation $site
         */
        $view = $this->getView();

        // In some case, there is no vars (see ItemController for search).
        $site = $this->currentSite();
        if (!$site) {
            return '';
        }

        // To set the site slug make creation of next urls quicker internally.
        $siteSlug = $site->slug();
        $vars = $view->vars();

        $plugins = $view->getHelperPluginManager();
        $this->api = $plugins->get('api');
        $url = $plugins->get('url');
        $translate = $plugins->get('translate');
        $siteSetting = $plugins->get('siteSetting');

        $crumbsSettings = $siteSetting('blockplus_breadcrumbs_crumbs', false);
        // The multicheckbox skips keys of unset boxes, so they are added.
        if (is_array($crumbsSettings)) {
            $crumbsSettings = array_fill_keys($crumbsSettings, true) + [
                'home' => false,
                'collections' => false,
                'itemset' => false,
                'itemsetstree' => false,
                'current' => false,
            ];
        } else {
            // This param has never been set in site settings, so use default
            // values.
            $crumbsSettings = [];
        }

        $hasItemSetsTree = $plugins->has('itemSetsTree');
        if (!$hasItemSetsTree) {
            $crumbsSettings['itemsetstree'] = false;
        }

        // Avoid duplication between main item set and item sets tree.
        if (!empty($crumbsSettings['itemset']) && !empty($crumbsSettings['itemsetstree'])) {
            $crumbsSettings['itemset'] = false;
        }

        $defaults = $crumbsSettings + [
            'home' => true,
            'prepend' => [],
            'collections' => true,
            'collections_url' => $siteSetting('blockplus_breadcrumbs_collections_url'),
            'itemset' => true,
            'itemsetstree' => true,
            'current' => true,
            'property_itemset' => $siteSetting('blockplus_breadcrumbs_property_itemset'),
            'homepage' => false,
            'separator' => $siteSetting('blockplus_breadcrumbs_separator', ''),
            'template' => $this->defaultTemplate,
        ];
        $options += $defaults;

        /** @var \Laminas\Router\Http\RouteMatch $routeMatch */
        $routeMatch = $site->getServiceLocator()->get('Application')->getMvcEvent()->getRouteMatch();
        $matchedRouteName = $routeMatch->getMatchedRouteName();

        // Use a standard Zend/Laminas navigation breadcrumb.
        // The crumb is built flat and converted into a hierarchical one below.
        $this->crumbs = [];

        if ($options['home']) {
            $this->crumbs[] = [
                'label' => $translate('Home'), // @translate
                'uri' => $site->siteUrl($siteSlug),
                'resource' => $site,
            ];
        }

        $prepend = $siteSetting('blockplus_breadcrumbs_prepend', []);
        if ($prepend) {
            $this->crumbs = array_merge($this->crumbs, $prepend);
        }

        if ($options['prepend']) {
            $this->crumbs = array_merge($this->crumbs, $options['prepend']);
        }

        $label = null;

        switch ($matchedRouteName) {
            // Home page, without default site or defined home page.
            case 'top':
            case 'site':
                if (!$options['homepage']) {
                    return '';
                }
                if (!$options['home'] != $options['current']) {
                    $this->crumbHome($site);
                }
                break;

            case 'site/resource':
            case 'site/contribution':
                // Only actions "browse" and "search" are available in public.
                $action = $routeMatch->getParam('action', 'browse');
                if ($action === 'search') {
                    if ($options['collections']) {
                        $this->crumbCollections($options, $translate, $url, $siteSlug);
                    }
                    $controller = $this->extractController($routeMatch);
                    if ($controller !== 'search') {
                        $label = $this->extractLabel($controller);
                        $this->crumbs[] = [
                            'label' => $translate($label),
                            'uri' => $url(
                                $matchedRouteName,
                                ['site-slug' => $siteSlug, 'controller' => $controller, 'action' => 'browse']
                            ),
                            'resource' => null,
                        ];
                    }
                    if ($options['current']) {
                        $label = $translate('Search'); // @translate
                    }
                } elseif ($action === 'browse') {
                    $controller = $this->extractController($routeMatch);
                    if ($options['collections'] && $controller !== 'item-set') {
                        $this->crumbCollections($options, $translate, $url, $siteSlug);
                    }
                    if ($options['current']) {
                        $label = $this->extractLabel($controller);
                        $label = $translate($label);
                    }
                } elseif ($action === 'add') {
                    $controller = $this->extractController($routeMatch);
                    if ($options['collections'] && $controller !== 'item-set') {
                        $this->crumbCollections($options, $translate, $url, $siteSlug);
                    }
                    if ($options['current']) {
                        $label = $this->extractLabel($controller);
                        $label = $translate($label);
                    }
                } else {
                    if ($options['current']) {
                        $label = $translate('Unknown'); // @translate
                    }
                }
                break;

            case 'site/contribution-id':
                /** @var \Contribute\Api\Representation\ContributionRepresentation $contribution */
                $contribution = $vars->contribution;
                // no break.
            case 'site/resource-id':
                /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource */
                $resource = $vars->resource;
                // In case of an exception in a block, the resource may be null.
                if (!$resource && empty($contribution)) {
                    $this->crumbs[] = [
                        'label' => 'Error', // @translate
                        'uri' => $view->serverUrl(true),
                        'resource' => null,
                    ];
                    break;
                }
                $type = $resource ? $resource->resourceName() : 'contributions';
                switch ($type) {
                    case 'media':
                        $item = $resource->item();
                        if ($options['collections']) {
                            $this->crumbCollections($options, $translate, $url, $siteSlug);
                        }
                        if ($options['itemset']) {
                            $this->crumbPrimaryItemSet($item, $site);
                        } elseif ($options['itemsetstree']) {
                            $this->crumbItemSetsTree($item, $site);
                        }
                        $this->crumbItem($item, $site);
                        break;

                    case 'items':
                        if ($options['collections']) {
                            $this->crumbCollections($options, $translate, $url, $siteSlug);
                        }
                        if ($options['itemset']) {
                            $this->crumbPrimaryItemSet($resource, $site);
                        } elseif ($options['itemsetstree']) {
                            $this->crumbItemSetsTree($resource, $site);
                        }
                        break;

                    case 'contributions':
                        break;

                    case 'item_sets':
                    default:
                        if ($options['collections']) {
                            $this->crumbCollections($options, $translate, $url, $siteSlug);
                        }
                        break;
                }
                if ($options['current']) {
                    $label = (string) $resource->displayTitle();
                }
                break;

            case 'site/item-set':
                if ($options['collections']) {
                    $this->crumbCollections($options, $translate, $url, $siteSlug);
                }
                if ($options['current']) {
                    $action = $routeMatch->getParam('action', 'browse');
                    // In Omeka S, item set show is a redirect to item browse
                    // with a special partial, so normally, there is no "show",
                    // except with specific redirection.
                    /** @var \Omeka\Api\Representation\ItemSetRepresentation $resource */
                    $resource = $vars->itemSet ?? $vars->resource;
                    if ($resource) {
                        $label = (string) $resource->displayTitle();
                    }
                }
                break;

            case 'site/page':
                // The page should exist because the breadcrumbs use its view.
                // But in case of an exception in a block, the page may be null.
                try {
                    // Api doesn't allow to search one page by slug.
                    /** @var \Omeka\Api\Representation\SitePageRepresentation $page */
                    $page = $this->api->read('site_pages', ['site' => $site->id(), 'slug' => $view->params()->fromRoute('page-slug')])->getContent();
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                    $this->crumbs[] = [
                        'label' => 'Error', // @translate
                        'uri' => $view->serverUrl(true),
                        'resource' => null,
                    ];
                    break;
                }
                if (!$options['homepage']) {
                    $homepage = $site->homepage();
                    if (!$homepage) {
                        $linkedPages = $site->linkedPages();
                        $homepage = $linkedPages ? current($linkedPages) : null;
                    }
                    // This is the home page and home page is not wanted.
                    if ($homepage && $homepage->id() === $page->id()) {
                        return '';
                    }
                }

                // Find the page inside navigation. By construction, this is the
                // active page of the navigation. If not in navigation, it's a
                // root page.

                /**
                 * @var \Laminas\View\Helper\Navigation $nav
                 * @var \Laminas\Navigation\Navigation $container
                 * @see \Laminas\View\Helper\Navigation\Breadcrumbs::renderPartialModel()
                 * @todo Use the container directly, prepending root pages.
                 */
                $nav = $site->publicNav();
                $container = $nav->getContainer();
                $active = $nav->findActive($container);
                if ($active) {
                    // This process uses the short title in the navigation (label).
                    $active = $active['page'];
                    $parents = [];
                    if ($options['current']) {
                        $parents[] = [
                            'label' => $active->getLabel(),
                            'uri' => $active->getHref(),
                            'resource' => $page,
                        ];
                    }

                    while ($parent = $active->getParent()) {
                        if (!$parent instanceof AbstractPage) {
                            break;
                        }

                        $parents[] = [
                            'label' => $parent->getLabel(),
                            'uri' => $parent->getHref(),
                            'resource' => null,
                        ];

                        // Break if at the root of the given container.
                        if ($parent === $container) {
                            break;
                        }

                        $active = $parent;
                    }
                    $parents = array_reverse($parents);
                    $this->crumbs = array_merge($this->crumbs, $parents);
                }
                // The page is not in the navigation menu, so it's a root page.
                elseif ($options['current']) {
                    $label = $page->title();
                }
                break;

            // Module Advanced Search or Search.
            case substr($matchedRouteName, 0, 12) === 'search-page-':
                if ($options['collections']) {
                    $this->crumbCollections($options, $translate, $url, $siteSlug);
                }
                // Manage the case where the search page is used for item set,
                // like item/browse for item-set/show.
                $itemSet = null;
                if ($options['itemset']) {
                    $itemSetId = $routeMatch->getParam('item-set-id', null) ?: $view->params()->fromQuery('collection');
                    if ($itemSetId) {
                        $itemSet = $this->api->searchOne('item_sets', ['id' => $itemSetId])->getContent();
                        // Don't add the item set if it is not search in order
                        // to avoid to duplicate it when current is set.
                        if (!$options['current']) {
                            $this->crumbItemSet($itemSet, $site);
                        }
                        // Display page?
                    }
                } elseif ($options['itemsetstree']) {
                    $itemSetId = $routeMatch->getParam('item-set-id', null) ?: $view->params()->fromQuery('collection');
                    if ($itemSetId) {
                        $itemSet = $this->api->searchOne('item_sets', ['id' => $itemSetId])->getContent();
                        // Don't add the item set if it is not search in order
                        // to avoid to duplicate it when current is set.
                        if (!$options['current']) {
                            $this->crumbItemSetsTree($itemSet, $site);
                        }
                    }
                }
                if ($options['current']) {
                    $label = $itemSet
                        ? (string) $itemSet->displayTitle()
                        : $translate('Search'); // @translate
                }
                break;

            // For compatibility with old version of module Basket.
            case 'site/basket':
                if ($plugins->has('guestWidget')) {
                    $setting = $plugins->get('setting');
                    $label = $siteSetting('guest_dashboard_label') ?: $setting('guest_dashboard_label');
                    $this->crumbs[] = [
                        'label' => $label ?: $translate('Dashboard'), // @translate
                        'uri' => $url('site/guest', ['site-slug' => $siteSlug, 'action' => 'me']),
                        'resource' => null,
                    ];
                }
                // For compatibility with old module GuestUser.
                elseif ($plugins->has('guestUserWidget')) {
                    $setting = $plugins->get('setting');
                    $label = $siteSetting('guest_dashboard_label') ?: $setting('guest_dashboard_label');
                    $this->crumbs[] = [
                        'label' => $label ?: $translate('Dashboard'), // @translate
                        'uri' => $url('site/guest-user', ['site-slug' => $siteSlug, 'action' => 'me']),
                        'resource' => null,
                    ];
                }
                if ($options['current']) {
                    $label = $translate('Basket'); // @translate
                }
                break;

            case 'site/collecting':
                // TODO Add the page where the collecting form is.
                // Action can be "submit", "success" or "item-show".
                if ($options['current']) {
                    $label = $translate('Collecting'); // @translate
                }
                break;

            // Route for the contact us basket of an anonymous visitor.
            case 'site/contact-us':
                if ($options['current']) {
                    $label = $siteSetting('contactus_label_selection', $translate('Selection for contact')); // @translate
                }
                break;

            // Route for the selection of an anonymous visitor.
            case 'site/selection':
                if ($options['current']) {
                    $label = $siteSetting('selection_label', $translate('Selection')); // @translate
                }
                break;

            case 'site/guest':
            case 'site/guest/anonymous':
            // Routes "guest-user" are kept for the old module GuestUser.
            case 'site/guest-user':
            case 'site/guest-user/anonymous':
                if ($options['current']) {
                    $action = $routeMatch->getParam('action', 'me');
                    switch ($action) {
                        case 'me':
                            $setting = $plugins->get('setting');
                            $label = $translate($setting('guestuser_dashboard_label') ?: 'Dashboard'); // @translate
                            break;
                        case 'login':
                            $label = $translate('Login'); // @translate
                            break;
                        case 'register':
                            $label = $translate('Register'); // @translate
                            break;
                        case 'auth-error':
                            $label = $translate('Authentication error'); // @translate
                            break;
                        case 'forgot-password':
                            $label = $translate('Forgot password'); // @translate
                            break;
                        case 'confirm':
                            $label = $translate('Confirm'); // @translate
                            break;
                        case 'confirm-email':
                            $label = $translate('Confirm email'); // @translate
                            break;
                        default:
                            $label = $translate('User'); // @translate
                            break;
                    }
                }
                break;

            case 'site/guest/guest':
            case 'site/guest/basket':
            case 'site/guest/contact-us':
            case 'site/guest/selection':
            case 'site/guest-user/guest':
                $setting = $plugins->get('setting');
                $label = $siteSetting('guest_dashboard_label') ?: $setting('guest_dashboard_label');
                if ($matchedRouteName === 'site/guest-user/guest') {
                    $this->crumbs[] = [
                        'label' => $label ?: $translate('Dashboard'), // @translate
                        'uri' => $url('site/guest-user', ['site-slug' => $siteSlug]),
                        'resource' => null,
                    ];
                } else {
                    $this->crumbs[] = [
                        'label' => $label ?: $translate('Dashboard'), // @translate
                        'uri' => $url('site/guest', ['site-slug' => $siteSlug]),
                        'resource' => null,
                    ];
                }
                if ($options['current']) {
                    $action = $routeMatch->getParam('action', 'me');
                    switch ($action) {
                        case 'logout':
                            $label = $translate('Logout'); // @translate
                            break;
                        case 'update-account':
                            $label = $translate('Update account'); // @translate
                            break;
                        case 'update-email':
                            $label = $translate('Update email'); // @translate
                            break;
                        case 'accept-terms':
                            $label = $translate('Accept terms'); // @translate
                            break;
                        case 'basket':
                            $label = $translate('Basket'); // @translate
                            break;
                        case 'contact-us':
                            $label = $siteSetting('contactus_label_selection', $translate('Selection for contact')); // @translate
                            break;
                        case 'selection':
                            $label = $siteSetting('selection_label', $translate('Selection')); // @translate
                            break;
                        default:
                            $label = $translate('User'); // @translate
                            break;
                    }
                }
                break;

            // Module Advanced Search or Search.
            case strpos($matchedRouteName, 'search-page-') === 0:
                if ($options['current']) {
                    $label = $translate('Search'); // @translate
                }
                break;

            default:
                if ($options['current']) {
                    $label = $translate('Current page'); // @translate
                }
                break;
        }

        if ($options['current'] && isset($label)) {
            $this->crumbs[] = [
                'label' => $label,
                'uri' => $view->serverUrl(true),
                'resource' => null,
            ];
        }

        $template = $options['template'];
        unset($options['template']);

        /** @see \Omeka\Api\Representation\SiteRepresentation::publicNav() */
        $nested = $this->nestedPages($this->crumbs);

        return $view->partial(
            $template,
            [
                'site' => $site,
                'breadcrumbs' => new Navigation($nested),
                'options' => $options,
                // Keep the crumbs for compatibility with old themes.
                'crumbs' => $this->crumbs,
            ]
        );
    }

    protected function crumbHome(SiteRepresentation $site): void
    {
        $this->crumbs[] = [
            'label' => $this->getView()->translate('Home'),
            'uri' => $site->siteUrl($site->slug()),
            'resource' => $site,
        ];
    }

    protected function crumbCollections(array $options, $translate, $url, $siteSlug): void
    {
        $this->crumbs[] = [
            'label' => $translate('Collections'),
            'uri' => $options['collections_url'] ?: $url(
                'site/resource',
                ['site-slug' => $siteSlug, 'controller' => 'item-set', 'action' => 'browse']
            ),
            'resource' => null,
        ];
    }

    protected function crumbItemSet(ItemSetRepresentation $itemSet, SiteRepresentation $site): void
    {
        $this->crumbs[] = [
            'label' => (string) $itemSet->displayTitle(),
            'uri' => $itemSet->siteUrl($site->slug()),
            'resource' => $itemSet,
        ];
    }

    protected function crumbPrimaryItemSet(ItemRepresentation $item, SiteRepresentation $site): void
    {
        $itemSet = $this->getView()->primaryItemSet($item, $site);
        if ($itemSet) {
            $this->crumbs[] = [
                'label' => (string) $itemSet->displayTitle(),
                'uri' => $itemSet->siteUrl($site->slug()),
                'resource' => $itemSet,
            ];
        }
    }

    protected function crumbItemSetsTree($itemOrItemSet, SiteRepresentation $site): void
    {
        static $itemSetsTree;

        if (is_null($itemSetsTree)) {
            $itemSetsTree = $this->itemSetsTreeQuick();
        }

        // Get the item set that has the more ancestors: it will be the more
        // precise one.
        if ($itemOrItemSet instanceof ItemRepresentation) {
            $item = $itemOrItemSet;
            $itemSets = $item->itemSets();
            if (!$itemSets) {
                return;
            }
            $is = null;
            $total = 0;
            foreach ($itemSets as $itemSetId => $itemSet) {
                if (isset($itemSetsTree[$itemSetId])
                    && count($itemSetsTree[$itemSetId]['ancestors']) > $total
                ) {
                    $total = count($itemSetsTree[$itemSetId]['ancestors']);
                    $is = $itemSet;
                }
            }
            if (!$is) {
                $this->crumbPrimaryItemSet($item, $site);
                return;
            }
            $itemSet = $is;
            $includeCurrent = true;
        } elseif ($itemOrItemSet instanceof ItemSetRepresentation) {
            $itemSet = $itemOrItemSet;
            if (!isset($itemSetsTree[$itemSet->id()])) {
                $this->crumbItemSet($itemSet, $site);
                return;
            }
            $includeCurrent = false;
        } else {
            return;
        }

        $siteSlug = $site->slug();

        // Prepare the crumbs for the item sets.
        // Rights are already checked during building of the tree.
        foreach (array_reverse($itemSetsTree[$itemSet->id()]['ancestors']) as $ancestorItemSetId) {
            try {
                $ancestorItemSet = $this->api->read('item_sets', ['id' => $ancestorItemSetId])->getContent();
            } catch (\Exception $e) {
                continue;
            }
            $this->crumbs[] = [
                'label' => (string) $ancestorItemSet->displayTitle(),
                'uri' => $ancestorItemSet->siteUrl($siteSlug),
                'resource' => $ancestorItemSet,
            ];
        }

        if ($includeCurrent) {
            $this->crumbs[] = [
                'label' => (string) $itemSet->displayTitle(),
                'uri' => $itemSet->siteUrl($siteSlug),
                'resource' => $itemSet,
            ];
        }
    }

    protected function crumbItem(ItemRepresentation $item, SiteRepresentation $site): void
    {
        $this->crumbs[] = [
            'label' => (string) $item->displayTitle(),
            'uri' => $item->siteUrl($site->slug()),
            'resource' => $item,
        ];
    }

    protected function extractController(RouteMatch $routeMatch)
    {
        $controllers = [
            'Omeka\Controller\Site\ItemSet' => 'item-set',
            'Omeka\Controller\Site\Item' => 'item',
            'Omeka\Controller\Site\Media' => 'media',
            'Contribute\Controller\Site\Contribution' => 'contribution',
            'item-set' => 'item-set',
            'item' => 'item',
            'media' => 'media',
            'contribution' => 'contribution',
        ];
        $controller = $routeMatch->getParam('controller') ?: $routeMatch->getParam('__CONTROLLER__');
        if (isset($controllers[$controller])) {
            return $controllers[$controller];
        }

        if ($routeMatch->getParam('action') === 'search'
            && ($controller === 'Omeka\Controller\Site\Index' || $controller === 'index')
        ) {
            return 'search';
        }

        return $controller;
    }

    protected function extractLabel($controller)
    {
        $labels = [
            'item-set' => 'Item sets', // @translate
            'item' => 'Items', // @translate
            'media' => 'Media', // @translate
            'contribution' => 'Contributions', // @translate
        ];
        return $labels[$controller] ?? $controller;
    }

    protected function nestedPages($flat)
    {
        $nested = [];
        $last = count($flat) - 1;
        foreach (array_values($flat) as $level => $sub) {
            if ($level === 0) {
                $nested[] = $sub;
                $current = &$nested[0];
            } else {
                $current = $sub;
            }
            $current['pages'] = [];
            // Resource should be an instance of \Laminas\Permissions\Acl\Resource\ResourceInterface.
            unset($current['resource']);
            if ($level !== $last) {
                $current['pages'][] = null;
                $current = &$current['pages'][0];
            } else {
                // Active is required at least for the last page, else the
                // container won't render anything.
                $current['active'] = true;
            }
        }
        return $nested;
    }

    /**
     * Get the current site from the view.
     */
    protected function currentSite(): ?\Omeka\Api\Representation\SiteRepresentation
    {
        return $this->view->site ?? $this->view->site = $this->view
            ->getHelperPluginManager()
            ->get('Laminas\View\Helper\ViewModel')
            ->getRoot()
            ->getVariable('site');
    }

    /**
     * Get flat tree of item sets quickly.
     *
     * Use a quick connection request instead of a long procedure.
     *
     * @see \AdvancedSearch\View\Helper\AbstractFacet::itemsSetsTreeQuick()
     * @see \BlockPlus\View\Helper\Breadcrumbs::itemsSetsTreeQuick()
     * @see \SearchSolr\ValueExtractor\AbstractResourceEntityValueExtractor::itemSetsTreeQuick()
     *
     * @todo Simplify ordering: by sql (for children too) or store.
     *
     * @return array
     */
    protected function itemSetsTreeQuick(): array
    {
        // Run an api request to check rights.
        $site = $this->currentSite();
        $itemSetTitles = $this->api->search('item_sets', ['site_id' => $site->id(), 'return_scalar' => 'title'])->getContent();
        if (!count($itemSetTitles)) {
            return [];
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $site->getServiceLocator()->get('Omeka\Connection');

        $sortingMethod = $this->getView()->setting('itemsetstree_sorting_method', 'title') === 'rank' ? 'rank' : 'title';
        $sortingMethodSql = $sortingMethod === 'rank'
            ? 'item_sets_tree_edge.rank'
            : 'resource.title';

        // TODO Use query builder.
        $sql = <<<SQL
SELECT
    item_sets_tree_edge.item_set_id,
    item_sets_tree_edge.item_set_id AS "id",
    item_sets_tree_edge.parent_item_set_id AS "parent",
    item_sets_tree_edge.rank AS "rank",
    resource.title as "title"
FROM item_sets_tree_edge
JOIN resource ON resource.id = item_sets_tree_edge.item_set_id
WHERE item_sets_tree_edge.item_set_id IN (:ids)
GROUP BY resource.id
ORDER BY $sortingMethodSql ASC;
SQL;
        $flatTree = $connection->executeQuery($sql, ['ids' => array_keys($itemSetTitles)], ['ids' => $connection::PARAM_INT_ARRAY])->fetchAllAssociativeIndexed();

        // Use integers or string to simplify comparaisons.
        foreach ($flatTree as &$node) {
            $node['id'] = (int) $node['id'];
            $node['parent'] = (int) $node['parent'] ?: null;
            $node['rank'] = (int) $node['rank'];
            $node['title'] = (string) $node['title'];
        }
        unset($node);

        $structure = [];
        foreach ($flatTree as $id => $node) {
            $children = [];
            foreach ($flatTree as $subId => $subNode) {
                if ($subNode['parent'] === $id) {
                    $children[$subId] = $subId;
                }
            }
            $ancestors = [];
            $nodeWhile = $node;
            while ($parentId = $nodeWhile['parent']) {
                $ancestors[$parentId] = $parentId;
                $nodeWhile = $flatTree[$parentId] ?? null;
                if (!$nodeWhile) {
                    break;
                }
            }
            $structure[$id] = $node;
            $structure[$id]['children'] = $children;
            $structure[$id]['ancestors'] = $ancestors;
            $structure[$id]['level'] = count($ancestors);
        }

        // Order by sorting method.
        if ($sortingMethod === 'rank') {
            $sortingFunction = fn ($a, $b) => $structure[$a]['rank'] - $structure[$b]['rank'];
        } else {
            $sortingFunction = fn ($a, $b) => strcmp($structure[$a]['title'], $structure[$b]['title']);
        }

        foreach ($structure as &$node) {
            usort($node['children'], $sortingFunction);
        }
        unset($node);

        // Get and order root nodes.
        $roots = [];
        foreach ($structure as $id => $node) {
            if (!$node['level']) {
                $roots[$id] = $node;
            }
        }

        // Root is already ordered via sql.

        // TODO The children are useless here.

        // Reorder whole structure.
        // TODO Use a while loop.
        $result = [];
        foreach ($roots as $id => $root) {
            $result[$id] = $root;
            foreach ($root['children'] ?? [] as $child1) {
                $child1 = $structure[$child1];
                $result[$child1['id']] = $child1;
                foreach ($child1['children'] ?? [] as $child2) {
                    $child2 = $structure[$child2];
                    $result[$child2['id']] = $child2;
                    foreach ($child2['children'] ?? [] as $child3) {
                        $child3 = $structure[$child3];
                        $result[$child3['id']] = $child3;
                        foreach ($child3['children'] ?? [] as $child4) {
                            $child4 = $structure[$child4];
                            $result[$child4['id']] = $child4;
                            foreach ($child4['children'] ?? [] as $child5) {
                                $child5 = $structure[$child5];
                                $result[$child5['id']] = $child5;
                                foreach ($child5['children'] ?? [] as $child6) {
                                    $child6 = $structure[$child6];
                                    $result[$child6['id']] = $child6;
                                    foreach ($child6['children'] ?? [] as $child7) {
                                        $child7 = $structure[$child7];
                                        $result[$child7['id']] = $child7;
                                        foreach ($child7['children'] ?? [] as $child8) {
                                            $child8 = $structure[$child8];
                                            $result[$child8['id']] = $child8;
                                            foreach ($child8['children'] ?? [] as $child9) {
                                                $child9 = $structure[$child9];
                                                $result[$child9['id']] = $child9;
                                                foreach ($child9['children'] ?? [] as $child10) {
                                                    $child10 = $structure[$child10];
                                                    $result[$child10['id']] = $child10;
                                                    foreach ($child10['children'] ?? [] as $child11) {
                                                        $child11 = $structure[$child11];
                                                        $result[$child11['id']] = $child11;
                                                        foreach ($child11['children'] ?? [] as $child12) {
                                                            $child12 = $structure[$child12];
                                                            $result[$child12['id']] = $child12;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $structure = $result;

        // Append missing item sets.
        foreach (array_diff_key($itemSetTitles, $flatTree) as $id => $title) {
            if (isset($structure[$id])) {
                continue;
            }
            $structure[$id] = [
                'id' => $id,
                'parent' => null,
                'rank' => 0,
                'title' => $title,
                'children' => [],
                'ancestors' => [],
                'level' => 0,
            ];
        }

        return $structure;
    }
}
