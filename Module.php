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
        // The template name is managed only by a post because the form data are
        // not passed to the api event.
        // Anyway, for an api request, the template name can be set directly.

        // $post = $services->get('Application')->getMvcEvent()->getRequest()->getPost();
        if (empty($_POST)
            || (
                // TODO Use "o:layout_date[template_name] only, waiting for the fix committed in \Omeka\Controller\SiteAdmin\IndexController::addPageAction().
                empty($_POST['o:layout_data']['template_name'])
                && empty($_POST['template_name'])
            )
        ) {
            return;
        }

        /**
         * @var \Omeka\Api\Request $request
         * @var array $data Posted form data or page api data.
         */
        $request = $event->getParam('request');
        $data = $request->getContent();

        $data['o:layout_data']['template_name'] ??= $_POST['o:layout_data']['template_name']
            ?? $_POST['template_name'] ?? null;

        $request->setContent($data);
    }

    public function handleSitePageEditPre(Event $event): void
    {
        $view = $event->getTarget();
        $assetUrl = $view->plugin('assetUrl');

        $blockGroups = $this->getBlockGroupLayouts();
        $script = sprintf('const blockGroups = %s;', json_encode($blockGroups, 320));

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

    protected function getBlockGroupLayouts(): array
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
        $themeSettings = $siteSettings->get($theme->getSettingsKey());

        $result = array_merge(
            $config['block_groups'],
            $settings->get('blockplus_block_groups', []),
            $siteSettings->get('blockplus_block_groups', []),
            $themeConfig['block_groups'] ?? [],
            $themeSettings['block_groups'] ?? []
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
