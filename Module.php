<?php declare(strict_types=1);

namespace BlockPlus;

if (!class_exists(\Common\TraitModule::class)) {
    require_once dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\Stdlib\PsrMessage;
use Common\TraitModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Session\Container;
use Omeka\Module\AbstractModule;

/**
 * BlockPlus
 *
 * @copyright Daniel Berthereau, 2018-2024
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    use TraitModule;

    const NAMESPACE = __NAMESPACE__;

    protected function preInstall(): void
    {
        $services = $this->getServiceLocator();
        $plugins = $services->get('ControllerPluginManager');
        $translate = $plugins->get('translate');
        $translator = $services->get('MvcTranslator');

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.61')) {
            $message = new \Omeka\Stdlib\Message(
                $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.61'
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }

        $js = __DIR__ . '/asset/vendor/ThumbnailGridExpandingPreview/js/grid.js';
        if (!file_exists($js)) {
            $message = new PsrMessage(
                'The javascript library should be installed. See module’s installation documentation.' // @translate
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message->setTranslator($translator));
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        // Manage previous/next resource. Require module EasyAdmin.
        // TODO Manage item sets and media for search?
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.browse.before',
            [$this, 'handleViewBrowse']
        );
        $sharedEventManager->attach(
            \AdvancedSearch\Controller\SearchController::class,
            'view.layout',
            [$this, 'handleViewBrowse']
        );

        // Manage page models and blocks groups.
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\SitePageAdapter::class,
            'api.create.pre',
            [$this, 'handleSitePageCreatePre']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\SiteAdmin\Page',
            'view.edit.before',
            [$this, 'handleSitePageEditPre']
        );
        $sharedEventManager->attach(
            \Omeka\Stdlib\HtmlPurifier::class,
            'htmlpurifier_config',
            [$this, 'handleHtmlPurifier']
        );

        $sharedEventManager->attach(
            \Omeka\Form\SettingForm::class,
            'form.add_elements',
            [$this, 'handleMainSettings']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'handleSiteSettings']
        );
        // TODO Remove handleSiteSettingsFilters.
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_input_filters',
            [$this, 'handleSiteSettingsFilters']
        );
    }

    /**
     * Copy in:
     * @see \BlockPlus\Module::handleViewBrowse()
     * @see \EasyAdmin\Module::handleViewBrowse()
     */
    public function handleViewBrowse(Event $event): void
    {
        $session = new Container('EasyAdmin');
        if (!isset($session->lastBrowsePage)) {
            $session->lastBrowsePage = [];
            $session->lastQuery = [];
        }
        $params = $event->getTarget()->params();
        // $ui = $params->fromRoute('__SITE__') ? 'public' : 'admin';
        $ui = 'public';
        // Why not use $this->getServiceLocator()->get('Request')->getServer()->get('REQUEST_URI')?
        $session->lastBrowsePage[$ui]['items'] = $_SERVER['REQUEST_URI'];
        // Store the processed query too for quicker process later and because
        // the controller may modify it (default sort order).
        $session->lastQuery[$ui]['items'] = $params->fromQuery();
    }

    public function handleSitePageCreatePre(Event $event): void
    {
        // The page model is managed only by a post because the form data are
        // not passed to the api event.
        // Anyway, for an api request, the template name and any other data can
        // be set directly.

        // $post is the same as $_POST.
        $post = $this->getServiceLocator()->get('Application')->getMvcEvent()->getRequest()->getPost();
        if (empty($post) || empty($post['page_model'])) {
            return;
        }

        /**
         * @var \Omeka\Api\Request $request
         * @var array $sitePage Posted form data or page api data.
         * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
         * @var \Laminas\Log\Logger $logger
         */
        $services = $this->getServiceLocator();
        $request = $event->getParam('request');

        $pageModels = $this->getPageModels();
        $pageModel = $pageModels[$post['page_model']] ?? null;
        if ($pageModel === null) {
            $messenger = $services->get('ControllerPluginManager')->get('messenger');
            $logger = $services->get('Omeka\Logger');
            $message = new PsrMessage(
                'The page model "{page_model}" does not exist.', // @translate
                ['page_model' => $post['page_model']]
            );
            $messenger->addWarning($message);
            $logger->err($message->getMessage(), $message->getContext());
        }
        if (!$pageModel) {
            return;
        }

        // Normalize the page model.
        if (isset($pageModel['is_public']) && !isset($pageModel['o:is_public'])) {
            $pageModel['o:is_public'] = (bool) $pageModel['is_public'];
        }
        if (isset($pageModel['layout']) && !isset($pageModel['o:layout'])) {
            $pageModel['o:layout'] = $pageModel['layout'];
        }
        if (isset($pageModel['layout_data']) && !isset($pageModel['o:layout_data'])) {
            $pageModel['o:layout_data'] = $pageModel['layout_data'];
        }
        if (isset($pageModel['block']) && !isset($pageModel['o:block'])) {
            $pageModel['o:block'] = $pageModel['block'];
        }
        unset(
            $pageModel['o:label'],
            $pageModel['o:caption'],
            $pageModel['label'],
            $pageModel['caption'],
            $pageModel['is_public'],
            $pageModel['layout'],
            $pageModel['layout_data'],
            $pageModel['block']
        );

        // Normalize the blocks if any.
        foreach ($pageModel['o:block'] ?? [] as $key => $block) {
            if (isset($block['layout']) && !isset($block['o:layout'])) {
                $block['o:layout'] = $block['layout'];
            }
            if (isset($block['data']) && !isset($block['o:data'])) {
                $block['o:data'] = $block['data'];
            }
            if (isset($block['layout_data']) && !isset($block['o:layout_data'])) {
                $block['o:layout_data'] = $block['layout_data'];
            }
            unset(
                $block['o:label'],
                $block['o:caption'],
                $block['label'],
                $block['caption'],
                $block['layout'],
                $block['data'],
                $block['layout_data']
            );
            if (empty($block['o:layout'])) {
                unset($pageModel['o:block'][$key]);
            } else {
                $pageModel['o:block'][$key] = $block;
            }
        }

        // Complete submitted new page. Normally, the page contains only
        // "o:title", "o:slug" and "o:is_public", but other modules can set more
        // data.
        $sitePage = $request->getContent();

        if (isset($pageModel['o:is_public']) && !isset($sitePage['o:is_public'])) {
            $sitePage['o:is_public'] = (bool) $pageModel['o:is_public'];
        }
        if (isset($pageModel['o:layout']) && !isset($sitePage['o:layout'])) {
            $sitePage['o:layout'] = $pageModel['o:layout'];
        }
        if (!empty($pageModel['o:layout_data'])) {
            if (isset($sitePage['o:layout_data'])) {
                $sitePage['o:layout_data'] = array_merge($sitePage['o:layout_data'], $pageModel['o:layout_data']);
            } else {
                $sitePage['o:layout_data'] = $pageModel['o:layout_data'];
            }
        }
        if (!empty($pageModel['o:block'])) {
            if (isset($sitePage['o:block'])) {
                $sitePage['o:block'] = array_merge($sitePage['o:block'], $pageModel['o:block']);
            } else {
                $sitePage['o:block'] = $pageModel['o:block'];
            }
        }

        $request->setContent($sitePage);
    }

    public function handleSitePageEditPre(Event $event): void
    {
        $view = $event->getTarget();
        $assetUrl = $view->plugin('assetUrl');

        // Remove page models, that are available only during page creation.
        $pageModels = $this->getPageModels();
        $blocksGroups = array_filter($pageModels, fn($v): bool => !isset($v['o:layout_data']) && !isset($v['layout_data']));

        $script = sprintf('const blocksGroups = %s;', json_encode($blocksGroups, 320));

        $view->headLink()
            ->appendStylesheet($assetUrl('css/block-plus-admin.css', 'BlockPlus'));
        $view->headScript()
            ->appendFile($assetUrl('js/block-plus-admin.js', 'BlockPlus'), 'text/javascript', ['defer' => 'defer'])
            ->appendScript($script);
    }

    public function handleHtmlPurifier(Event $event): void
    {
        // CKEditor footnotes uses `<section class="footnotes">` and some other
        // elements and attributes, but they are not in the default config, designed for html 4.
        // The same for HTML Purifier, that is based on html 4, and won't be
        // updated to support html 5.
        // @see https://github.com/ezyang/htmlpurifier/issues/160

        /** @var \HTMLPurifier_Config $config */
        $config = $event->getParam('config');

        $config->set('Attr.EnableID', true);
        $config->set('HTML.AllowedAttributes', [
            'a.id',
            'a.rel',
            'a.href',
            'a.target',
            'li.id',
            'li.data-footnote-id',
            'section.class',
            'sup.data-footnote-id',
            'img.src',
            'img.alt',
            // 'img.loading',
        ]);

        $config->set('HTML.TargetBlank', true);

        /** @var \HTMLPurifier_HTMLDefinition $def */
        $def = $config->getHTMLDefinition(true);

        $def->addElement('article', 'Block', 'Flow', 'Common');
        $def->addElement('section', 'Block', 'Flow', 'Common');
        $def->addElement('header', 'Block', 'Flow', 'Common');
        $def->addElement('footer', 'Block', 'Flow', 'Common');

        $def->addAttribute('sup', 'data-footnote-id', 'ID');
        // This is the same id than sup, but Html Purifier ID should be unique
        // among all the submitted html ids, so use Class.
        $def->addAttribute('li', 'data-footnote-id', 'Class');

        $def->addAttribute('a', 'target', new \HTMLPurifier_AttrDef_Enum(['_blank', '_self', '_target', '_top']));

        $event->setParam('config', $config);
    }

    public function handleSiteSettings(Event $event): void
    {
        $this->handleAnySettings($event, 'site_settings');

        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings\Site');

        $orders = $settings->get('blockplus_items_order_for_itemsets') ?: [];
        $ordersString = '';
        foreach ($orders as $ids => $order) {
            $ordersString .= $ids . ' ' . $order['sort_by'];
            if (isset($order['sort_order'])) {
                $ordersString .= ' ' . $order['sort_order'];
            }
            $ordersString .= "\n";
        }

        /**
         * @see \Omeka\Form\Element\RestoreTextarea $siteGroupsElement
         * @see \Internationalisation\Form\SettingsFieldset $fieldset
         */
        $event->getTarget()
            ->get('blockplus_items_order_for_itemsets')
            ->setValue($ordersString);
    }

    public function handleSiteSettingsFilters(Event $event): void
    {
        $inputFilter = $event->getParam('inputFilter');
        $inputFilter
            // TODO Use DataTextarea.
            ->add([
                'name' => 'blockplus_items_order_for_itemsets',
                'required' => false,
                'filters' => [
                    [
                        'name' => \Laminas\Filter\Callback::class,
                        'options' => [
                            'callback' => [$this, 'filterResourceOrder'],
                        ],
                    ],
                ],
            ])
        ;
    }

    public function filterResourceOrder($string)
    {
        $list = $this->stringToList($string);

        // The setting is ordered by item set id for quicker check.
        // "0" is the default order, so it is always single.
        $result = [];
        foreach ($list as $row) {
            [$ids, $sortBy, $sortOrder] = array_map('trim', explode(' ', str_replace("\t", ' ', $row) . '  ', 3));
            $ids = trim((string) $ids, ', ');
            if (!strlen($ids) || empty($sortBy)) {
                continue;
            }
            $ids = explode(',', $ids);
            sort($ids);
            $ids = in_array('0', $ids)
                ? 0
                : implode(',', $ids);
            $result[$ids] = [
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder && strtolower($sortOrder) === 'desc' ? 'desc' : 'asc',
            ];
        }
        ksort($result);

        return $result;
    }

    /**
     * Copied:
     * @see \BlockPlus\Module::getPageModels()
     * @see \BlockPlus\Service\Form\SitePageFormFactory::getPageModels()
     */
    protected function getPageModels(): array
    {
        /**
         * @var array $config
         * @var \Omeka\Settings\Settings $settings
         * @var \Omeka\Settings\SiteSettings $siteSettings
         * @var \Omeka\Site\Theme\Manager $themeManager
         */
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');
        $siteSettings = $services->get('Omeka\Settings\Site');
        $themeManager = $services->get('Omeka\Site\ThemeManager');

        $theme = $themeManager->getCurrentTheme();
        $themeConfig = $theme->getConfigSpec();
        $themeSettings = $siteSettings->get($theme->getSettingsKey(), []);

        $result = array_merge(
            $config['page_models'] ?? [],
            $settings->get('blockplus_page_models', []),
            $siteSettings->get('blockplus_page_models', []),
            $themeConfig['page_models'] ?? [],
            $themeSettings['page_models'] ?? []
        );

        // TODO Keep main/site/theme order? Use nested select? Add an icon in the list?
        uasort($result, fn ($a, $b) => strcasecmp($a['o:label'] ?? '', $b['o:label'] ?? ''));

        return $result;
    }

    /**
     * Get each line of a string separately.
     */
    protected function stringToList($string): array
    {
        return array_filter(array_map('trim', explode("\n", $this->fixEndOfLine($string))), 'strlen');
    }

    /**
     * Clean the text area from end of lines.
     *
     * This method fixes Windows and Apple copy/paste from a textarea input.
     */
    protected function fixEndOfLine($string): string
    {
        return str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], (string) $string);
    }
}
