<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Mapping\Form\BlockLayoutMapQueryForm;
use Mapping\Site\BlockLayout\MapQuery;
use Omeka\Api\Representation\SitePageBlockRepresentation;

class MappingMapSearch extends MapQuery
{
    public function getLabel()
    {
        return 'Map by search'; // @translate
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $query = $view->params()->fromQuery() ?: [];
        $query = array_filter($query);
        unset($query['basemap_provider']);
        unset($query['mapping_basemap_provider']);

        $isOldMapping = !method_exists($this, 'setFormElementManager');
        if ($isOldMapping) {
            return $this->render20($view, $block, $query);
        }

        // Copy of the parent block, but check if user runs a query.
        // Furthermore, the partial is different and block and query are output.

        $form = $this->formElementManager->get(BlockLayoutMapQueryForm::class);
        $data = $form->prepareBlockData($block->data());

        $isTimeline = (bool) $data['timeline']['data_type_properties'];
        $timelineIsAvailable = $this->timelineIsAvailable();

        if ($query) {
            $itemsQuery = $query;
        } else {
            $itemsQuery = null;
            parse_str($data['query'], $itemsQuery);
        }

        $featuresQuery = [];

        // Get all events for the items.
        $events = [];
        if ($isTimeline && $timelineIsAvailable) {
            $itemsQuery['site_id'] = $block->page()->site()->id();
            $itemsQuery['has_features'] = true;
            $itemsQuery['limit'] = 100000;
            $itemIds = $this->apiManager->search('items', $itemsQuery, ['returnScalar' => 'id'])->getContent();
            foreach ($itemIds as $itemId) {
                // Set the timeline event for this item.
                $event = $this->getTimelineEvent($itemId, $data['timeline']['data_type_properties'], $view);
                if ($event) {
                    $events[] = $event;
                }
            }
        }

        return $view->partial('common/block-layout/mapping-block-search', [
            'block' => $block,
            'data' => $data,
            'query' => $query,
            'itemsQuery' => $itemsQuery,
            'featuresQuery' => $featuresQuery,
            'isTimeline' => $isTimeline,
            'timelineData' => $this->getTimelineData($events, $data, $view),
            'timelineOptions' => $this->getTimelineOptions($data),
        ]);
    }

    protected function render20(PhpRenderer $view, SitePageBlockRepresentation $block, array $query)
    {
        // Copy of the parent block, but check if user runs a query.
        // Furthermore, the partial is different and block and query are output.

        $data = $this->filterBlockData($block->data());
        $isTimeline = (bool) $data['timeline']['data_type_properties'];
        $timelineIsAvailable = $this->timelineIsAvailable();

        // Get features (and events, if applicable) from the attached items.
        $events = [];
        $features = [];
        if (!$query) {
            parse_str($data['query'], $query);
        }
        // Search only for items with features that are in the current site, and
        // set a reasonable item limit.
        $originalQuery = $query;
        $query = array_merge($query, [
            'site_id' => $block->page()->site()->id(),
            'has_features' => true,
            'limit' => 5000,
        ]);
        $response = $view->api()->search('items', $query);
        foreach ($response->getContent() as $item) {
            if ($isTimeline && $timelineIsAvailable) {
                // Set the timeline event for this item.
                $event = $this->getTimelineEvent($item, $data['timeline']['data_type_properties'], $view);
                if ($event) {
                    $events[] = $event;
                }
            }
            // Set the map features for this item.
            $itemFeatures = $view->api()->search('mapping_features', ['item_id' => $item->id()])->getContent();
            $features = array_merge($features, $itemFeatures);
        }

        return $view->partial('common/block-layout/mapping-block-search', [
            'block' => $block,
            'data' => $data,
            'query' => $originalQuery,
            'features' => $features,
            'isTimeline' => $isTimeline,
            'timelineData' => $this->getTimelineData($events, $data, $view),
            'timelineOptions' => $this->getTimelineOptions($data),
        ]);
    }
}
