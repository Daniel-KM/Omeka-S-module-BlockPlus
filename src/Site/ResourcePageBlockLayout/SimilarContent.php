<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Display similar content based on main metadata of the current resource.
 *
 * Similar resources are found via a search query using shared metadata values
 * like subjects, types, classes, and item sets.
 *
 * This block is a duplicate of SeeAlso with separate settings, allowing two
 * different configurations for different use cases.
 */
class SimilarContent implements ResourcePageBlockLayoutInterface
{
    public function getLabel(): string
    {
        return 'Similar content'; // @translate
    }

    public function getCompatibleResourceNames(): array
    {
        return [
            'items',
            'item_sets',
            'media',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource): string
    {
        $services = $resource->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $siteSettings = $services->get('Omeka\Settings\Site');
        $site = $view->currentSite();

        // Get the limit from site settings.
        $limit = (int) $siteSettings->get('blockplus_similarcontent_limit', 4);
        if ($limit <= 0) {
            return '';
        }

        $heading = $siteSettings->get('blockplus_similarcontent_heading', 'Similar content');
        $similarContentPool = $siteSettings->get('blockplus_similarcontent_pool', '');
        $allSites = (bool) $siteSettings->get('blockplus_similarcontent_all_sites', false);

        $resourceName = $resource->resourceName();
        $resourceId = $resource->id();

        // Build the search query.
        if ($similarContentPool) {
            // Use predefined query.
            $query = [];
            parse_str($similarContentPool, $query);
            $query['limit'] = $limit;
        } else {
            // Use properties to find similar resources via fulltext search.
            $properties = $siteSettings->get('blockplus_similarcontent_properties', []);

            // Collect values from configured properties for fulltext search.
            $searchTerms = [];
            foreach ($properties as $property) {
                $values = $resource->value($property, ['all' => true]);
                if ($values) {
                    foreach ($values as $value) {
                        // Handle linked resources and literal values.
                        if ($value->valueResource()) {
                            $searchTerms[] = $value->valueResource()->displayTitle();
                        } elseif ($value->value()) {
                            $searchTerms[] = $value->value();
                        }
                    }
                }
            }

            // If no properties configured or no metadata to search by, return partial with empty results.
            if (empty($properties) || empty($searchTerms)) {
                return $view->partial('common/resource-page-block-layout/similar-content', [
                    'site' => $site,
                    'resource' => $resource,
                    'similarResources' => [],
                    'resourceType' => $resourceName,
                    'heading' => $heading,
                ]);
            }

            // Build the search query using fulltext search.
            $fulltextSearch = implode(' ', $searchTerms);
            $query = [
                'fulltext_search' => $fulltextSearch,
                'limit' => $limit,
            ];
        }

        // Limit to current site unless all sites is enabled.
        if (!$allSites) {
            $query['site_id'] = $site->id();
        }

        // Search for similar resources.
        try {
            $response = $api->search($resourceName, $query);
            $similarResources = $response->getContent();
        } catch (\Exception $e) {
            $services->get('Omeka\Logger')->err(
                'SimilarContent block: Error searching for similar resources: {message}', // @translate
                ['message' => $e->getMessage()]
            );
            $similarResources = [];
        }

        // Remove the current resource from results.
        $similarResources = array_filter($similarResources, fn ($r) => $r->id() !== $resourceId);

        return $view->partial('common/resource-page-block-layout/similar-content', [
            'site' => $site,
            'resource' => $resource,
            'similarResources' => $similarResources,
            'resourceType' => $resourceName,
            'heading' => $heading,
        ]);
    }
}
