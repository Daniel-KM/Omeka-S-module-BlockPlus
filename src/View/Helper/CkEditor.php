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

        $hasDataTypeRdf = class_exists('DataTypeRdf\Module', false);
        $isSiteAdminResource = $isAdmin
            && in_array($controller, ['Item', 'ItemSet', 'Media', 'Annotation', 'item', 'item-set', 'media', 'annotation'])
            && ($action === 'edit' || $action === 'add')
            // To avoid to prepare a factory to check if module DataTypeRdf
            // is enabled, just check the class.
            && $hasDataTypeRdf;

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
                    JS . "\n";
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

        // Check if the footnotes plugin is available (requires external
        // assets). Register it via addExternal so CKEditor knows where to
        // find it, and gracefully degrade when missing.
        $footnotesPluginPath = dirname(__DIR__, 3) . '/asset/vendor/ckeditor-footnotes/footnotes/plugin.js';
        $hasFootnotes = file_exists($footnotesPluginPath);
        if ($hasFootnotes) {
            $footnotesUrl = $escapeJs($assetUrl('vendor/ckeditor-footnotes/footnotes/', 'BlockPlus'));
            $script .= "\n" . <<<JS
                CKEDITOR.plugins.addExternal('footnotes', '$footnotesUrl', 'plugin.js');
                JS;
        } else {
            // Remove footnotes from extraPlugins to prevent CKEditor from
            // failing to load the editor when the plugin is missing.
            $script .= "\n" . <<<'JS'
                CKEDITOR.on('instanceCreated', function(event) {
                    var ep = event.editor.config.extraPlugins;
                    if (typeof ep === 'string') {
                        event.editor.config.extraPlugins = ep.replace(/,?footnotes/, '').replace(/^,/, '');
                    } else if (Array.isArray(ep)) {
                        event.editor.config.extraPlugins = ep.filter(function(p) { return p !== 'footnotes'; });
                    }
                });
                JS;
        }

        // The footnotes icon is not loaded automatically, so add css.
        // Only this css rule is needed.
        // The js for block-plus-admin is already loaded with the blocks.
        $view->headLink()
            ->appendStylesheet($assetUrl('css/block-plus-admin.css', 'BlockPlus'));

        $view->headScript()
            ->appendFile($assetUrl('vendor/ckeditor/ckeditor.js', 'Omeka'));
        if ($hasFootnotes) {
            $view->headScript()
                ->appendFile($assetUrl('vendor/ckeditor-footnotes/footnotes/plugin.js', 'BlockPlus'), 'text/javascript', ['defer' => 'defer']);
        }
        $view->headScript()
            ->appendFile($assetUrl('vendor/ckeditor/adapters/jquery.js', 'Omeka'))
            ->appendScript($script);
    }
}
