<?php
namespace BlockPlus\Site\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

class Assets extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Assets'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()->appendStylesheet($assetUrl('css/asset-form.css', 'Omeka'));
        $view->headScript()
            ->appendFile($assetUrl('js/asset-form.js', 'Omeka'))
            ->appendFile($assetUrl('js/assets-form.js', 'BlockPlus'));
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();

        // Trim all values, then remove empty asset arrays: array without asset
        // and caption are removed.
        $data['assets'] = array_values(array_filter(
            array_map(function ($v) {
                return array_map('trim', $v);
            }, $data['assets']),
            function ($asset) {
                return !empty($asset['asset']) || !empty($asset['caption']);
            }
        ));

        $data = $block->setData($data);
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['assets'];
        $fieldset = $formElementManager->get(\BlockPlus\Form\AssetsFieldset::class);

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            // Add fields for repeatable fieldsets with multiple fields.
            if (is_array($value)) {
                $subFieldsetName = "o:block[__blockIndex__][o:data][$key]";
                /** @var \Zend\Form\Fieldset $subFieldset */
                $subFieldset = $fieldset->get($subFieldsetName);
                $subFieldsetBaseName = $subFieldsetName . '[__' . substr($key, 0, -1) . 'Index__]';
                /** @var \Zend\Form\Fieldset $subFieldsetBase */
                $subFieldsetBase = $subFieldset->get($subFieldsetBaseName);
                foreach (array_values($value) as $subKey => $subValue) {
                    $newSubFieldsetName = $subFieldsetName . "[$subKey]";
                    /** @var \Zend\Form\Fieldset $newSubFieldset */
                    $newSubFieldset = clone $subFieldsetBase;
                    $newSubFieldset
                        ->setName($newSubFieldsetName)
                        ->setAttribute('data-index', $subKey);
                    $subFieldset->add($newSubFieldset);
                    foreach ($subValue as $subSubKey => $subSubValue) {
                        $elementBaseName = $subFieldsetBaseName . "[$subSubKey]";
                        $elementName = "o:block[__blockIndex__][o:data][$key][$subKey][$subSubKey]";
                        $newSubFieldset
                            ->get($elementBaseName)
                            ->setName($elementName)
                            ->setValue($subSubValue);
                        $dataForm[$elementName] = $subSubValue;
                    }
                    // $newSubFieldset->populateValues($dataForm);
                }
                $subFieldset
                    ->remove($subFieldsetBaseName)
                    ->setAttribute('data-next-index', count($value));
            } else {
                $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
            }
        }

        $fieldset->populateValues($dataForm);

        // The assets are currently filled manually (use default form).
        $html = $view->formCollection($fieldset);

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
                // The asset has been removed. It's kept in block, but skipped.
                unset($assets[$key]);
            }
        }

        $partial = $block->dataValue('partial') ?: 'common/block-layout/assets';

        return $view->partial($partial, [
            'heading' => $block->dataValue('heading', ''),
            'assets' => $assets,
        ]);
    }
}
