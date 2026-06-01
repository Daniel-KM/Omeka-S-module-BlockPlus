<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Display related resources based on main metadata of the current resource.
 *
 * Related resources are found via a search query using shared metadata values
 * like subjects, types, classes, and item sets.
 */
class SeeAlso implements ResourcePageBlockLayoutInterface
{
    public function getLabel(): string
    {
        return 'See also'; // @translate
    }

    public function getCompatibleResourceNames(): array
    {
        return [
            'items',
            'item_sets',
            'media',
            'digital_objects',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource): string
    {
        $services = $resource->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $siteSettings = $services->get('Omeka\Settings\Site');
        $site = $view->currentSite();

        // Get the limit from site settings.
        $limit = (int) $siteSettings->get('blockplus_seealso_limit', 4);
        if ($limit <= 0) {
            return '';
        }

        $heading = $siteSettings->get('blockplus_seealso_heading', 'See also');
        $seeAlsoPool = $siteSettings->get('blockplus_seealso_pool', '');
        $allSites = (bool) $siteSettings->get('blockplus_seealso_all_sites', false);

        $resourceName = $resource->resourceName();
        $resourceId = $resource->id();

        // Build the search query.
        if ($seeAlsoPool) {
            // Use predefined query.
            $query = [];
            parse_str($seeAlsoPool, $query);
            $query['limit'] = $limit;
        } else {
            // Use properties to find related resources via fulltext search.
            $properties = $siteSettings->get('blockplus_seealso_properties', []);

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
                return $view->partial('common/resource-page-block-layout/see-also', [
                    'site' => $site,
                    'resource' => $resource,
                    'relatedResources' => [],
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

        // Search for related resources.
        try {
            $response = $api->search($resourceName, $query, ['countQuery' => false]);
            $relatedResources = $response->getContent();
        } catch (\Throwable $e) {
            $services->get('Omeka\Logger')->err(
                'SeeAlso block: Error searching for related resources: {message}', // @translate
                ['message' => $e->getMessage()]
            );
            $relatedResources = [];
        }

        // Remove the current resource from results.
        $relatedResources = array_filter($relatedResources, fn ($r) => $r->id() !== $resourceId);

        return $view->partial('common/resource-page-block-layout/see-also', [
            'site' => $site,
            'resource' => $resource,
            'relatedResources' => $relatedResources,
            'resourceType' => $resourceName,
            'heading' => $heading,
        ]);
    }
}
