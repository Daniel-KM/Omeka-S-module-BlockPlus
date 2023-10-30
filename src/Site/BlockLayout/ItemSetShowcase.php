<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;

class ItemSetShowcase extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/item-set-showcase';

    public function getLabel()
    {
        return 'Item set showcase'; // @translate
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['itemSetShowcase'];
        $blockFieldset = \BlockPlus\Form\ItemSetShowcaseFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->get('o:block[__blockIndex__][o:data][item_sets]')
            ->setOption('query', ['site_id' => $site->id()]);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $api = $view->plugin('api');

        $itemSets = [];
        $itemSetIds = $block->dataValue('item_sets', []);
        foreach ($itemSetIds as $id) {
            $itemSet = $api->searchOne('item_sets', ['id' => $id])->getContent();
            if ($itemSet) {
                $itemSets[] = $itemSet;
            }
        }

        if (empty($itemSets)) {
            return '';
        }

        $vars = [
            'block' => $block,
            'heading' => $block->dataValue('heading', ''),
            'itemSets' => $itemSets,
            'thumbnailType' => $block->dataValue('thumbnail_type', 'square'),
            'showTitleOption' => $block->dataValue('show_title_option', 'item_title'),
        ];
        $template = $block->dataValue('template', self::PARTIAL_NAME);
        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }
}
