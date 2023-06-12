<?php declare(strict_types=1);

namespace Menu\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\ItemSetRepresentation;
use Omeka\Api\Representation\SiteRepresentation;

class PrimaryItemSet extends AbstractHelper
{
    /**
     * Get the primary item set of an item.
     *
     * The item sets of an item are not ordered: they are not collections. The
     * primary item set depends on the site if it is set.
     * When the primary item set cannot be determined, the property set in the
     * site settings is used to check if an item set is set as a value. In all
     * cases, the item sets of the item and the item sets of the site, if set,
     * are checked for consistency.
     *
     * @param ItemRepresentation $item
     * @param SiteRepresentation $site Limit the item sets of the item to the
     * ones of the site.
     * @param bool $returnNullIfNoOrder In the case when the primary item set
     * cannot be determined among many, don't return any of them.
     * @return ItemSetRepresentation|null
     */
    public function __invoke(
        ItemRepresentation $item,
        ?SiteRepresentation $site = null,
        bool $returnNullIfNoOrder = false
    ): ?ItemSetRepresentation {
        // Check only with the item sets of the site.
        if ($site) {
            // Use item set id as key to simplify checks.
            $itemSets = [];
            foreach ($item->itemSets() as $itemSet) {
                $itemSets[$itemSet->id()] = $itemSet;
            }
            $siteItemSets = [];
            foreach ($site->siteItemSets() as $siteItemSet) {
                $itemSet = $siteItemSet->itemSet();
                $siteItemSets[$itemSet->id()] = $itemSet;
            }
            $itemSets = array_intersect_key($siteItemSets, $itemSets);
        } else {
            $itemSets = $item->itemSets();
        }

        $count = count($itemSets);
        if (!$count) {
            return null;
        }

        if ($count === 1) {
            return reset($itemSets);
        }

        $propertyItemSet = $this->getView()->setting('menu_property_itemset');
        if ($propertyItemSet) {
            // Omeka manages differently the datatype when there is a resource
            // template, so a check should be done for "resource:itemset" and
            // "resource".

            $values = $item->value($propertyItemSet, ['type' => 'resource:itemset', 'all' => true]);
            foreach ($values as $value) {
                $itemSet = $value->valueResource();
                if (isset($itemSets[$itemSet->id()])) {
                    return $itemSet;
                }
            }

            $values = $item->value($propertyItemSet, ['type' => 'resource', 'all' => true]);
            foreach ($values as $value) {
                $valueResource = $value->valueResource();
                if ($valueResource->resourceName() !== 'item_sets') {
                    continue;
                }
                if (isset($itemSets[$valueResource->id()])) {
                    return $valueResource;
                }
            }
        }

        return $returnNullIfNoOrder
            ? null
            : reset($itemSets);
    }
}
