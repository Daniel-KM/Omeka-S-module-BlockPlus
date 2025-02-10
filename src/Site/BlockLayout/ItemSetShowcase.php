<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;

class ItemSetShowcase extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
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

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        $api = $view->plugin('api');

        $itemSets = [];
        $itemSetIds = $block->dataValue('item_sets', []);
        if ($itemSetIds) {
            $itemSets = $api->search('item_sets', ['id' => $itemSetIds, 'sort_by' => 'id', 'sort_order' => 'desc'])->getContent();
        }

        if (empty($itemSets)) {
            return '';
        }

        // TODO Order by selected ids when chosen query will support order.

        $vars = [
            'block' => $block,
            'itemSets' => $itemSets,
            'thumbnailType' => $block->dataValue('thumbnail_type', 'square'),
            'showTitleOption' => $block->dataValue('show_title_option', 'item_title'),
        ];
        return $view->partial($templateViewScript, $vars);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return strip_tags((string) $this->render($view, $block));
    }
}
