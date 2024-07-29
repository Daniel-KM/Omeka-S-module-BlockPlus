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

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.62')) {
            $message = new \Omeka\Stdlib\Message(
                $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.62'
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

        // Manage saving page model and blocks group.
        $sharedEventManager->attach(
            'Omeka\Controller\SiteAdmin\Page',
            'view.edit.page_actions',
            [$this, 'handleViewPageEdit']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\SitePageAdapter::class,
            'api.update.post',
            [$this, 'handleSitePageUpdatePost']
        );

        // Manage main and site settings.
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
        $pageBlocksNames = array_combine(array_keys($pageModels), array_map(fn($k, $v): string => $v['o:label'] ?? $v['label'] ?? $k, array_keys($pageModels), $pageModels));

        $script = sprintf('const blocksGroups = %s;', json_encode($blocksGroups, 320));
        $script .= "\n" . sprintf('const pageBlocksNames = %s;', json_encode($pageBlocksNames, 320));

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

    public function handleViewPageEdit(Event $event): void
    {
        // TODO Limit rights? Anyway, new page models or blocks group are appended, not overridden.

        $fieldset = new \Laminas\Form\Fieldset;
        $fieldset
            ->setAttribute('id', 'fieldset-page-model')
            ->add([
                'name' => 'page_model[type]',
                'type' => \Laminas\Form\Element\Radio::class,
                'options' => [
                    'label' => 'Type', // @translate
                    'value_options' => [
                        'blocks_group' => 'Blocks only', // @translate
                        'page_model' => 'Full page', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'page-model-type',
                    'value' => 'blocks_group',
                ],
            ])
            ->add([
                'name' => 'page_model[label]',
                'type' => \Laminas\Form\Element\Text::class,
                'options' => [
                    'label' => 'Label', // @translate
                ],
                'attributes' => [
                    'id' => 'page-model-label',
                    // The label can't be required: it's optional for the page.
                    // 'required' => true,
                ],
            ])
            ->add([
                'name' => 'page_model[name]',
                'type' => \Laminas\Form\Element\Text::class,
                'options' => [
                    'label' => 'Name', // @translate
                    // 'info' => 'Unique name with letters, numbers and "_".', // @translate
                ],
                'attributes' => [
                    'id' => 'page-model-name',
                ],
            ])
            ->add([
                'name' => 'page_model[caption]',
                'type' => \Laminas\Form\Element\Text::class,
                'options' => [
                    'label' => 'Caption', // @translate
                ],
                'attributes' => [
                    'id' => 'page-model-caption',
                ],
            ])
            ->add([
                'name' => 'page_model[store]',
                'type' => \Laminas\Form\Element\Select::class,
                'options' => [
                    'label' => 'Settings', // @translate
                    'value_options' => [
                        'main' => 'Main settings', // @translate
                        'site' => 'Site settings', // @translate
                        'theme' => 'Theme settings (if supported)', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'page-model-store',
                    'value' => 'main',
                ],
            ])
        ;

        $view = $event->getTarget();
        $translate = $view->plugin('translate');

        $textCreate = $translate('Create page model'); // @translate
        $button = <<<HTML
        <button type="button" id="button-page-model" class="button expand" title="$textCreate" aria-label="$textCreate" data-text-collapse="$textCreate" data-text-expand="$textCreate" href=""><span class="o-icon-settings"></span></button>
        HTML;
        echo $button
            . '<dialog class="fields-page-model collapsible">'
            . '<legend>'
            . $translate('Save as page model or blocks groups') // @translate
            . '</legend>'
            . '<p>'
            . $translate('If a label is set, the model will be stored when the current page will be saved.') // @translate
            . '</p>'
            . $view->formCollection($fieldset, true)
            . '</dialog>'
        ;
    }

    public function handleSitePageUpdatePost(Event $event): void
    {
        /**
         * @var \Omeka\Api\Request $request
         * @var \Omeka\Api\Response $response
         * @var array $sitePage Posted form data or page api data.
         *
         * @var array $config
         * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
         * @var \Omeka\Entity\SitePage $sitePage
         * @var \Omeka\Settings\Settings $settings
         * @var \Omeka\Settings\SiteSettings $siteSettings
         * @var \Omeka\Site\Theme\Theme $theme
         * @var \Omeka\Site\Theme\Manager $themeManager
         *
         */
        $services = $this->getServiceLocator();
        $request = $event->getParam('request');
        $messenger = $services->get('ControllerPluginManager')->get('messenger');

        $sitePageData = $request->getContent();
        if (empty($sitePageData['page_model']['label'])) {
            return;
        }

        $randomString = fn() => '_' . substr(str_replace(["+", "/", "="], "", base64_encode(random_bytes(48))), 0, 4);

        $toCreate = $sitePageData['page_model'];

        $label = trim($toCreate['label'] ?? '') === '' ? $randomString() : trim($toCreate['label']);
        $name = trim($toCreate['name'] ?? '');
        $caption = trim($toCreate['caption'] ?? '') === '' ? null : trim($toCreate['caption']);
        $type = ($toCreate['type'] ?? 'blocks_group') === 'page_model' ? 'page_model' : 'blocks_group';
        $store = in_array($toCreate['store'] ?? '', ['main', 'site', 'theme']) ? $toCreate['store'] : 'main';

        // Check if the page model name is unique.
        $pageModels = $this->getPageModels();

        // Slugify the name or use a random name.
        $cleanName = $this->slugify($name === '' ? $label : $name);
        if (!$cleanName) {
            $cleanName = $randomString();
        }

        if (isset($pageModels[$cleanName])) {
            $cleanNamePrev = $cleanName;
            $cleanName = $cleanName . $randomString();
            $message = new PsrMessage(
                '"{name}" is already used as page model and was renamed "{name_2}".', // @translate
                ['name' => $cleanNamePrev, 'name_2' => $cleanName]
            );
            $messenger->addWarning($message);
        }

        // Prepare new page model or blocks group.
        $response = $event->getParam('response');
        $sitePage = $response->getContent();
        $siteId = $sitePage->getSite()->getId();

        // Do not store empty values (but "0" is allowed).
        // Do not store grid settings when layout is not grid.
        $isNotEmpty = fn ($v) => $v !== '' && $v !== [] && $v !== null;
        $isNotGrid = fn ($k) => mb_substr($k, 0, 5) !== 'grid_';

        $pageModel = ['o:label' => $label];

        if (isset($caption)) {
            $pageModel['o:caption'] = $caption;
        }

        $pageLayout = $sitePage->getLayout();

        // Prepare page settings.
        if ($type === 'page_model') {
            if ($pageLayout) {
                $pageModel['o:layout'] = $pageLayout;
            }
            $layoutData = $sitePage->getLayoutData();
            $layoutData = array_filter($layoutData, $isNotEmpty);
            if ($pageLayout !== 'grid') {
                $layoutData = array_filter($layoutData, $isNotGrid, ARRAY_FILTER_USE_KEY);
            }
            // This key is required to built a page model, so set it even empty.
            $pageModel['o:layout_data'] = $layoutData;
        }

        // Append blocks group.
        // Attachements are not stored in models.
        // Start blocks number at 1 for end user.
        $i = 0;
        /** @var \Omeka\Entity\SitePageBlock $pageBlock */
        foreach ($sitePage->getBlocks() as $pageBlock) {
            $layout = $pageBlock->getLayout();
            if (!$layout) {
                continue;
            }
            $block = ['o:layout' => $layout];
            $blockData = $pageBlock->getData();
            $blockData = array_filter($blockData, $isNotEmpty);
            if ($blockData) {
                $block['o:data'] = $blockData;
            }
            $blockLayoutData = $pageBlock->getLayoutData();
            $blockLayoutData = array_filter($blockLayoutData, $isNotEmpty);
            if ($pageLayout !== 'grid') {
                $blockLayoutData = array_filter($blockLayoutData, $isNotGrid, ARRAY_FILTER_USE_KEY);
            }
            if ($blockLayoutData) {
                $block['o:layout_data'] = $blockLayoutData;
            }
            $pageModel['o:block'][++$i] = $block;
        }

        // Get the specific page models from main, site, or theme settings to
        // avoid to mix them.
        if ($store === 'theme') {
            $siteSettings = $services->get('Omeka\Settings\Site');
            $themeManager = $services->get('Omeka\Site\ThemeManager');
            $theme = $themeManager->getCurrentTheme();
            $themeSettings = $siteSettings->get($theme->getSettingsKey(), [], $siteId);
            $pageModels = $themeSettings['page_models'] ?? [];
        } elseif ($store === 'site') {
            $siteSettings = $services->get('Omeka\Settings\Site');
            $pageModels = $siteSettings->get('blockplus_page_models', [], $siteId);
        } else {
            $settings = $services->get('Omeka\Settings');
            $pageModels = $settings->get('blockplus_page_models', []);
        }

        $pageModels[$cleanName] = $pageModel;

        if ($store === 'theme') {
            $themeSettings['page_models'] = $pageModels;
            $siteSettings->set($theme->getSettingsKey(), $themeSettings, $siteId);
            $message = $type === 'page_model'
                ? 'The page model "{label}" ({name}) was saved in theme settings.' // @translate
                : 'The blocks group "{label}" ({name}) was saved in theme settings.'; // @translate
        } elseif ($store === 'site') {
            $siteSettings->set('blockplus_page_models', $pageModels, $siteId);
            $message = $type === 'page_model'
                ? 'The page model "{label}" ({name}) was saved in site settings.' // @translate
                : 'The blocks group "{label}" ({name}) was saved in site settings.'; // @translate
        } else {
            $settings->set('blockplus_page_models', $pageModels);
            $message = $type === 'page_model'
                ? 'The page model "{label}" ({name}) was saved in theme settings.' // @translate
                : 'The blocks group "{label}" ({name}) was saved in theme settings.'; // @translate
        }
        $messenger->addSuccess(new PsrMessage($message, ['label' => $label, 'name' => $cleanName]));
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

    /**
     * Transform the given string into a valid URL slug.
     *
     * Unlike site slug slugify, replace with "_" and don't start with a number.
     *
     * @see \Omeka\Api\Adapter\SiteSlugTrait::slugify()
     */
    protected function slugify($input): string
    {
        if (extension_loaded('intl')) {
            $transliterator = \Transliterator::createFromRules(':: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;');
            $slug = $transliterator->transliterate((string) $input);
        } elseif (extension_loaded('iconv')) {
            $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $input);
        } else {
            $slug = (string) $input;
        }
        $slug = mb_strtolower((string) $slug, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9_]+/u', '_', $slug);
        $slug = preg_replace('/^\d+$/', '_', $slug);
        $slug = preg_replace('/_{2,}/', '_', $slug);
        $slug = preg_replace('/_*$/', '', $slug);
        return $slug;
    }
}
