<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for loading scripts necessary to use CKEditor on a page.
 *
 * Override core view helper to load a specific config.
 */
class CkEditor extends AbstractHelper
{
    /**
     * Load the scripts necessary to use CKEditor on a page.
     */
    public function __invoke(): void
    {
        $view = $this->getView();
        $plugins = $view->getHelperPluginManager();
        $setting = $plugins->get('setting');
        $assetUrl = $plugins->get('assetUrl');
        $escapeJs = $plugins->get('escapeJs');
        $params = $view->params();

        $isSitePageAdmin = $params->fromRoute('__SITEADMIN__')
            && $params->fromRoute('__CONTROLLER__') === 'Page'
            && $params->fromRoute('action') === 'edit';

        // The html mode is used only in site page edition for now.
        $script = '';
        if ($isSitePageAdmin) {
            $htmlMode = $setting('blockplus_html_mode');
            if ($htmlMode && $htmlMode !== 'inline') {
                $script = <<<JS
CKEDITOR.config.customHtmlMode = '$htmlMode';

JS;
            }

            $htmlConfig = $setting('blockplus_html_config');
            $customConfigUrl = $htmlConfig && $htmlConfig !== 'default'
                ? 'js/ckeditor_config_' . $htmlConfig . '.js'
                : 'js/ckeditor_config.js';
        } else {
            $customConfigUrl = 'js/ckeditor_config.js';
        }

        $customConfigUrl = $escapeJs($assetUrl($customConfigUrl, 'BlockPlus'));
        $script .= <<<JS
CKEDITOR.config.customConfig = '$customConfigUrl';
JS;

        $view->headScript()
            // Don't use defer for now.
            ->appendFile($assetUrl('vendor/ckeditor/ckeditor.js', 'Omeka'))
            ->appendFile($assetUrl('vendor/ckeditor-footnotes/plugin.js', 'BlockPlus'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('vendor/ckeditor/adapters/jquery.js', 'Omeka'))
            ->appendScript($script);
    }
}
