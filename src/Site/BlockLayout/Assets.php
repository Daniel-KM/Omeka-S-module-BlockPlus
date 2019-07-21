<?php
namespace BlockPlus\Site\BlockLayout;

use BlockPlus\Form\AssetsForm;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

class Assets extends AbstractBlockLayout
{
    use FillPartialsTrait;

    public function getLabel()
    {
        return 'Assets'; // @translate
    }

    protected $blockForm = AssetsForm::class;

    /**
     * @var array
     */
    protected $defaultSettings = [];

    public function prepareForm(PhpRenderer $view)
    {
        $view->headScript()->appendFile($view->assetUrl('js/assets-form.js', 'BlockPlus'));
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();

        // Merge assets, urls and labels here for quicker rendering and for
        // future evolution of the UI.

        $assets = array_map('intval', $data['assets']);

        $result = [];
        // Don't use array filter, since an empty line is possible.
        $linksLabels = str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $data['links_labels']);
        $linksLabels = array_map('trim', explode("\n", $linksLabels));

        foreach ($assets as $key => $assetId) {
            if (empty($assetId)) {
                continue;
            }
            if (isset($linksLabels[$key])) {
                list($url, $label) = array_map('trim', explode('|', $linksLabels[$key] . '|'));
            } else {
                $url = '';
                $label = '';
            }

            $result[] = [
                'asset' => $assetId,
                'url' => $url,
                'label' => $label,
            ];
        }

        $data = [
            'heading' => $data['heading'],
            'assets' => $result,
            'misc' => $data['misc'],
            'partial' => $data['partial'],
        ];

        $block->setData($data);
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
        $this->defaultSettings = $services->get('Config')['blockplus']['block_settings']['assets'];

        $data = $block ? $block->data() + $this->defaultSettings : $this->defaultSettings;

        // Adaptation for the form.
        $assets = [];
        $linksLabels = '';
        foreach ($data['assets'] as $asset) {
            $assets[] = $asset['asset'];
            $linksLabels .= $asset['url'] . ($asset['label'] ? ' | ' . $asset['label'] : '') . "\n";
        }
        $data = [
            'assets' => $assets,
            'links_labels' => $linksLabels,
        ];

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $form = $formElementManager->get($this->blockForm);
        $this->fillPartials($form, 'common/block-layout/assets', $site, $services);
        $form->setData($dataForm);
        $form->prepare();

        // The assets are currently filled manually (use default form).
        $html = $view->formCollection($form);
        $html = $this->fillMultipleAssets($dataForm, $html, $view);

        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $api = $view->api();
        $assets = $block->dataValue('assets', []);
        foreach ($assets as $key => &$assetData) {
            try {
                $assetData['asset'] = $api->read('assets', $assetData['asset'])->getContent();
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
                // The asset has been removed.
                unset($assets[$key]);
            }
        }

        $partial = $block->dataValue('partial') ?: 'common/block-layout/assets';

        return $view->partial($partial, [
            'heading' => $block->dataValue('heading'),
            'assets' => $assets,
            'misc' => $block->dataValue('misc'),
        ]);
    }

    /**
     * Hacky way to fill multiple assets with the standard form and js.
     *
     * @todo Manage a form for multiple assets.
     *
     * @param array $data
     * @param string $html
     * @param PhpRenderer $view
     * @return string
     */
    protected function fillMultipleAssets(array $data, $html, PhpRenderer $view)
    {
        if (empty($data['o:block[__blockIndex__][o:data][assets]'])) {
            return $html;
        }

        $api = $view->plugin('api');
        $translate = $view->plugin('translate');
        $url = $view->plugin('url');
        $escape = $view->plugin('escapeHtml');

        $translations = [
            '{no_selected_asset}' => $translate('[No asset selected]'),
            '{sidebar_content_url}' => $escape($url('admin/default', ['controller' => 'asset', 'action' => 'sidebar-select'])),
            '{select}' => $translate('Select'),
            '{clear}' => $translate('Clear'),
            '{add_another}' => $translate('Add another'),
        ];

        $fill = <<<'HTML'
<div class="asset-form-element">
    <span class="selected-asset" style="">
        <img class="selected-asset-image" src="{asset_url}"><div class="selected-asset-name">{asset_name}</div>
    </span>
    <span class="no-selected-asset">{no_selected_asset}</span>
    <button type="button" class="asset-form-select" data-sidebar-content-url="{sidebar_content_url}">{select}</button>
    <button type="button" class="asset-form-clear red button">{clear}</button>
    <button type="button" class="asset-form-add button" "="">{add_another}</button>
    <input name="o:block[__blockIndex__][o:data][assets][]" type="hidden" value="{asset_id}">
</div>

HTML;

        $empty = <<<'HTML'
<div class="asset-form-element empty">
    <span class="selected-asset" style="display: none;">
        <img class="selected-asset-image"><div class="selected-asset-name"></div>
    </span>
    <span class="no-selected-asset">{no_selected_asset}</span>
    <button type="button" class="asset-form-select" data-sidebar-content-url="{sidebar_content_url}">{select}</button>
    <button type="button" class="asset-form-clear red button">{clear}</button>
    <button type="button" class="asset-form-add button" "="">{add_another}</button>
    <input name="o:block[__blockIndex__][o:data][assets][]" type="hidden" value="">
</div>

HTML;

        $fill = str_replace(array_keys($translations), array_values($translations), $fill);
        $empty = str_replace(array_keys($translations), array_values($translations), $empty);

        $insert = '';
        foreach ($data['o:block[__blockIndex__][o:data][assets]'] as $assetId) {
            try {
                /** @var \Omeka\Api\Representation\AssetRepresentation $asset */
                $asset = $api->read('assets', $assetId)->getContent();
                $filling = [
                    '{asset_url}' => $asset->assetUrl(),
                    '{asset_name}' => $asset->name(),
                    '{asset_id}' => $asset->id(),
                ];
                $insert .= str_replace(array_keys($filling), array_values($filling), $fill);
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
                $insert .= $empty;
            }
        }

        return preg_replace(
            '~<div class="asset-form-element.*?<button type="button" class="asset-form-add button">.*?</div>~s',
            $insert,
            $html
        );
    }
}
