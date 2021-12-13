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
        $assetUrl = $view->plugin('assetUrl');
        $customConfigUrl = $view->escapeJs($assetUrl('js/ckeditor_config.js', 'BlockPlus'));
        $view->headScript()
            // Don't use defer for now.
            ->appendFile($assetUrl('vendor/ckeditor/ckeditor.js', 'Omeka'))
            ->appendFile($assetUrl('vendor/ckeditor-footnotes/plugin.js', 'BlockPlus'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('vendor/ckeditor/adapters/jquery.js', 'Omeka'))
            ->appendScript("CKEDITOR.config.customConfig = '$customConfigUrl';");
    }
}
