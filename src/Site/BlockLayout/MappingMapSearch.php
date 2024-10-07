<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Mapping\Site\BlockLayout\MapQuery;

class MappingMapSearch extends MapQuery
{
    public function getLabel()
    {
        return 'Map by search'; // @translate
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        // Copy of the parent block, but check if user runs a query.
        // Furthermore, the partial is different and block and query are output.

        $query = $view->params()->fromQuery() ?: [];
        $query = array_filter($query);
        unset($query['basemap_provider']);
        unset($query['mapping_basemap_provider']);

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
