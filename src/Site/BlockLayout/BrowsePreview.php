<?php
namespace BlockPlus\Site\BlockLayout;

use BlockPlus\Form\BrowsePreviewForm;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Zend\View\Renderer\PhpRenderer;

class BrowsePreview extends AbstractBlockLayout
{
    use FillPartialsTrait;

    public function getLabel()
    {
        return 'Browse preview'; // @translate
    }

    protected $blockForm = BrowsePreviewForm::class;

    /**
     * @var array
     */
    protected $defaultSettings = [];

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $this->defaultSettings = $services->get('Config')['blockplus']['block_settings']['browsePreview'];

        $data = $block ? $block->data() + $this->defaultSettings : $this->defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $form = $formElementManager->get($this->blockForm);
        $this->fillPartials($form, 'common/block-layout/browse-preview', $site, $services);
        $form->setData($dataForm);
        $form->prepare();

        return $view->formCollection($form);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $resourceType = $block->dataValue('resource_type', 'items');

        $query = [];
        parse_str($block->dataValue('query'), $query);
        $originalQuery = $query;

        $site = $block->page()->site();
        if ($view->siteSetting('browse_attached_items', false)) {
            $query['site_attachments_only'] = true;
        }

        $query['site_id'] = $site->id();
        $query['limit'] = $block->dataValue('limit', 12);

        if (!isset($query['sort_by'])) {
            $query['sort_by'] = 'created';
        }
        if (!isset($query['sort_order'])) {
            $query['sort_order'] = 'desc';
        }

        $response = $view->api()->search($resourceType, $query);
        $resources = $response->getContent();

        $resourceTypes = [
            'items' => 'item',
            'item_sets' => 'item-set',
            'media' => 'media',
        ];

        $partial = $block->dataValue('partial') ?: 'common/block-layout/browse-preview';

        // There is no list of media in public views.
        $linkText = $resourceType === 'media' ? '' : $block->dataValue('link-text');
        return $view->partial($partial, [
            'resourceType' => $resourceTypes[$resourceType],
            'resources' => $resources,
            'heading' => $block->dataValue('heading'),
            'linkText' => $linkText,
            'query' => $originalQuery,
        ]);
    }
}
