<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

class LinkedResourcesByItemSet implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Linked resources by item set'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'items',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        /**
         * @var \Omeka\Api\Representation\SiteRepresentation $site
         * @var \Omeka\Api\Manager $api
         * @var \Omeka\View\Helper\Params $params
         * @var \Omeka\Settings\Settings $settings
         * @var \Omeka\Settings\SiteSettings $siteSettings
         * @var \Doctrine\ORM\EntityManager $entityManager
         * @var \Doctrine\DBAL\Connection $connection
         * @var \Omeka\Api\Adapter\AbstractResourceEntityAdapter $adapter
         * @var \Omeka\Permissions\Acl $acl
         *
         * Adapted from resource displaySubjectValues().
         * @see \Omeka\Api\Representation\AbstractResourceEntityRepresentation::displaySubjectValues()
         */
        $services = $resource->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $site = $view->currentSite();
        $resourceName = $resource->resourceName();
        $params = $view->params();
        $adapter = $services->get('Omeka\ApiAdapterManager')->get($resourceName);
        $siteSettings = $services->get('Omeka\Settings\Site');

        $page = (int) $params->fromQuery('page', 1) ?: 1;
        // $perPage = (int) $siteSettings->get('per_page') ?: (int) $settings->get('per_page', 25);
        $perPage = 25;

        $currentItemSetId = $params->fromQuery('resource_item_set_id');
        $currentItemSetId = is_numeric($currentItemSetId) ? (int) $currentItemSetId : null;
        try {
            $currentItemSet = $currentItemSetId
                ? $api->read('item_sets', ['id' => $currentItemSetId ])->getContent()
                : null;
        } catch (NotFoundException $e) {
            $currentItemSet = null;
        }

        $excludeResourcesNotInSite = (bool) $siteSettings->get('exclude_resources_not_in_site');

        // Subjects may contain media and item sets, but only items are kept
        // when filtering item sets. They are already filtered for visibility.
        // There is no pagination here.
        // TODO Filter subject values directly by the item set if any.
        $resourceEntity = $api->read($resourceName, $resource->id(), [], ['responseContent' => 'resource', 'initialize' => false, 'finalize' =>false])->getContent();
        $subjectValues = $currentItemSetId && !$currentItemSet
            ? []
            : $adapter->getSubjectValuesSimple($resourceEntity, null, 'items', $excludeResourcesNotInSite ? $site->id() : null);

        if ($subjectValues) {
            // For simplicity, use two queries: one to get item set titles,
            // and one to get items by item set, that may be filtered by the
            // current item set. It may be possible to use one query, but it
            // will require more post-process here.

            // Use doctrine query builder, simpler than entity manager query
            // builder when using table item_item_set.
            // $entityManager = $services->get('Omeka\EntityManager');
            $connection = $services->get('Omeka\Connection');

            $qb = $connection->createQueryBuilder();
            $expr = $qb->expr();

            // Check item set visibility manually in all cases.
            $acl = $services->get('Omeka\Acl');
            $isAllowed = $acl->userIsAllowed(\Omeka\Entity\Resource::class, 'view-all');

            // Get the list of all item set titles for the selector, so don't
            // filter list of item sets items.
            $qb
                ->select(
                    'resource.id',
                    'resource.title'
                )
                ->from('item_set', 'item_set')
                ->innerJoin('item_set', 'resource', 'resource', 'resource.id = item_set.id')
                ->innerJoin('item_set', 'item_item_set', 'item_item_set', 'item_item_set.item_set_id = item_set.id')
                ->where($expr->in('item_item_set.item_id', ':item_ids'))
                ->setParameter('item_ids', array_column($subjectValues, 'id'), \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
                ->groupBy('resource.id')
                ->orderBy('resource.id', 'ASC');
            if (!$isAllowed) {
                $user = $services->get('Omeka\AuthenticationService')->getIdentity();
                $user
                    ? $qb
                            ->andWhere($expr->or(
                                $expr->eq('resource.is_public', 1),
                                $expr->eq('resource.owner_id', $adapter->createNamedParameter($qb, $user->getId()))
                            ))
                    : $qb
                            ->andWhere($expr->eq('resource.is_public', 1));
            }
            $itemSetsTitles = $qb->execute()->fetchAllKeyValue();

            // Get the list of all item sets by item, possibly filtered.
            $qb
                ->select(
                    'item_item_set.item_set_id',
                    'item_item_set.item_id'
                )
                ->groupBy('item_item_set.item_set_id')
                ->addGroupBy('item_item_set.item_id')
                ->orderBy('item_item_set.item_set_id', 'ASC')
                ->addOrderBy('item_item_set.item_id', 'ASC');
            if ($currentItemSet) {
                $qb
                    ->andWhere($expr->eq('resource.id', $currentItemSetId));
            }
            $itemSetsItems = $qb->execute()->fetchAllAssociative();

            // Prepend item ids without item sets.
            $itemWithoutItemSets = array_diff_key(array_column($subjectValues, 'id', 'id'), array_column($itemSetsItems, 'item_id', 'item_id'));
            $itemWithoutItemSets = array_map(fn ($v) => ['item_set_id' => 0, 'item_id' => (int) $v], $itemWithoutItemSets);

            $itemSetsItems = $currentItemSetId === 0
                ? $itemWithoutItemSets
                : array_merge($itemWithoutItemSets, $itemSetsItems);
        }

        return $view->partial('common/resource-page-block-layout/linked-resources-by-item-set', [
            'site' => $site,
            'resource' => $resource,
            'objectResource' => $resource,
            'subjectValues' => $subjectValues,
            'itemSetsTitles' => $itemSetsTitles ?? [],
            'itemSetsItems' => $itemSetsItems ?? [],
            'resourceType' => $resourceName,
            'totalCount' => count($subjectValues),
            'page' => $page,
            'perPage' => $perPage,
            'currentItemSet' => $currentItemSet,
            'currentItemSetId' => $currentItemSetId,
            // 'resourcePropertiesAll' => $resourcePropertiesAll,
        ]);
    }
}
