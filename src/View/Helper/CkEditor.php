<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for loading scripts necessary to use CKEditor on a page.
 *
 * Override core view helper to load a specific config.
 *
 * Used in various modules:
 * @see \Omeka\View\Helper\CkEditor
 * @see \BlockPlus\View\Helper\CkEditor
 * @see \DataTypeRdf\View\Helper\CkEditor
 */
class CkEditor extends AbstractHelper
{
    /**
     * Load the scripts necessary to use CKEditor on a page.
     */
    public function __invoke(): void
    {
        static $loaded;

        if (!is_null($loaded)) {
            return;
        }

        $loaded = true;

        $view = $this->getView();
        $plugins = $view->getHelperPluginManager();
        $assetUrl = $plugins->get('assetUrl');
        $escapeJs = $plugins->get('escapeJs');
        $params = $view->params();

        $isAdmin = $params->fromRoute('__ADMIN__');
        $isSiteAdmin = $params->fromRoute('__SITEADMIN__');
        $controller = $params->fromRoute('__CONTROLLER__');
        $action = $params->fromRoute('action');

        $isSiteAdminPage = $isSiteAdmin
            && ($controller === 'Page' || $controller === 'page')
            && $action === 'edit';

        $isSiteAdminResource = $isAdmin
            && in_array($controller, ['Item', 'ItemSet', 'Media', 'Annotation', 'item', 'item-set', 'media', 'annotation'])
            && ($action === 'edit' || $action === 'add')
            // To avoid to prepare a factory to check if module DataTypeRdf
            // is enabled, just check the class.
            && class_exists('DataTypeRdf\Module');

        $script = '';
        $customConfigJs = 'js/ckeditor_config.js';
        if ($isSiteAdminPage || $isSiteAdminResource) {
            $setting = $plugins->get('setting');
            $pageOrResource = $isSiteAdminPage ? 'page' : 'resource';
            $module = $isSiteAdminPage ? 'blockplus' : 'datatyperdf';
            $htmlMode = $setting($module . '_html_mode_' . $pageOrResource);
            if ($htmlMode && $htmlMode !== 'inline') {
                $script = <<<JS
CKEDITOR.config.customHtmlMode = '$htmlMode';

JS;
            }

            $htmlConfig = $setting($module . '_html_config_' . $pageOrResource);
            if ($htmlConfig && $htmlConfig !== 'default') {
                $customConfigJs = $htmlConfig && $htmlConfig !== 'default'
                    ? 'js/ckeditor_config_' . $htmlConfig . '.js'
                    : 'js/ckeditor_config.js';
            }
        }

        $customConfigUrl = $escapeJs($assetUrl($customConfigJs, 'BlockPlus'));
        $script .= <<<JS
CKEDITOR.config.customConfig = '$customConfigUrl';
JS;

        // The footnotes icon is not loaded automaically, so add css.
        // Only this css rule is needed.
        // The js for block-plus-admin is already loaded with the blocks.
        $view->headLink()
            ->appendStylesheet($assetUrl('css/block-plus-admin.css', 'BlockPlus'));

        $view->headScript()
            // Don't use defer for now.
            ->appendFile($assetUrl('vendor/ckeditor/ckeditor.js', 'Omeka'))
            ->appendFile($assetUrl('vendor/ckeditor-footnotes/plugin.js', 'BlockPlus'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('vendor/ckeditor/adapters/jquery.js', 'Omeka'))
            ->appendScript($script);
    }
}
