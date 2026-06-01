<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\ItemSetRepresentation;

class ItemSetPosition extends AbstractHelper
{
    /**
     * Get the position of an item set in the current site, or the ordered
     * list of item set ids.
     *
     * @param ItemSetRepresentation $itemSet
     * @return int|array Returns position of the item set in the current
     * site. Position is 1-based. 0 means that the site doesn't manage the
     * item set. Returns the ordered list of item sets in the site if no
     * item set is set. Warning: the position may change when the user is
     * logged if some item sets are private.
     */
    public function __invoke(?ItemSetRepresentation $itemSet = null)
    {
        static $itemSetPositions;

        if (is_null($itemSetPositions)) {
            $view = $this->getView();
            /** @var \Omeka\Api\Representation\SiteRepresentation $site */
            $site = $view->site;
            if (empty($site)) {
                return $itemSet ? 0 : [];
            }
            $itemSetPositions = [];
            foreach ($site->siteItemSets() as $key => $siteItemSet) {
                $itemSetPositions[$siteItemSet->itemSet()->id()] = $key + 1;
            }
        }

        if (is_null($itemSet)) {
            return array_flip($itemSetPositions);
        }

        $itemSetId = $itemSet->id();
        return $itemSetPositions[$itemSetId]
            ?? 0;
    }
}
