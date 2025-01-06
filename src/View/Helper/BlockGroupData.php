<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Doctrine\ORM\EntityManager;
use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\SitePageBlockRepresentation;

/**
 * View helper to get blocks and data of other blocks or a group of blocks.
 */
class BlockGroupData extends AbstractHelper
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get block or data of a specified type of block inside a group of blocks.
     *
     * @param SitePageBlockRepresentation $block Block that requires data.
     * @param string $dataBlockLayout The layout of the block to get.
     * @param string|null $dataBlockKey The key of value to get inside block.
     * @param int|null $dataBlockRank One-based order of the block to find.
     * @param mixed $default The default value to return when a data key is set.
     * @return SitePageBlockRepresentation|mixed If the data key is not set,
     *   return the found block or null if not found or not inside a group of
     *   blocks. Else return the value matching the specified key or defaut.
     */
    public function __invoke(
        SitePageBlockRepresentation $block,
        string $dataBlockLayout,
        ?string $dataBlockKey = null,
        ?int $dataBlockRank = null,
        $default = null
    ) {
        if (!$dataBlockLayout
            || $block->layout() === 'blockGroup'
        ) {
            return null;
        }

        // It is not possible to get the position of the block in the page from
        // the representation, so get the entity.
        // Normally, the page and the block are already cached by doctrine.
        // The code is long, but most of the times, there isn't a lot of blocks.

        /**
         * @var \Omeka\Entity\SitePageBlock $blockEntity
         * @var \Omeka\Entity\SitePage $pageEntity
         * @var \Omeka\Entity\SitePageBlock $pageBlockEntity
         */
        $blockEntity = $this->entityManager->find(\Omeka\Entity\SitePageBlock::class, $block->id());
        $blockPosition = $blockEntity->getPosition();

        // Get the block group where the block is.
        // The list of position is ordered, but may be a series with gap, so
        // keep a table of position/index.
        $pageEntity = $blockEntity->getPage();
        $pageBlockEntities = $pageEntity->getBlocks();
        $pageBlockLayouts = [];
        $pageBlockIndexes = [];
        $pageBlockGroupSpans = [];
        foreach ($pageBlockEntities as $index => $pageBlockEntity) {
            $layout = $pageBlockEntity->getLayout();
            $position = (int) $pageBlockEntity->getPosition();
            $pageBlockLayouts[$index] = $layout;
            $pageBlockIndexes[$position] = $index;
            if ($layout === 'blockGroup') {
                $pageBlockGroupSpans[$index] = (int) ($pageBlockEntity->getData()['span'] ?? 0);
            }
        }

        // Quick check if there is at least one group of block and the block
        // layout to found.
        if (!count($pageBlockGroupSpans)
            || !in_array($dataBlockLayout, $pageBlockLayouts)
            || !isset($pageBlockIndexes[$blockPosition])
        ) {
            return null;
        }

        // Get the block group of the main block.
        $blockIndex = $pageBlockIndexes[$blockPosition];
        $dataBlockRank = $dataBlockRank ?: 1;
        $blockGroupIndex = null;
        foreach ($pageBlockGroupSpans as $index => $span) {
            if ($blockIndex <= $index) {
                return null;
            }
            $blockGroupIndexMax = $index + $span;
            if ($blockIndex <= $blockGroupIndexMax) {
                $blockGroupIndex = $index;
                break;
            }
        }
        if ($blockGroupIndex === null) {
            return null;
        }

        // Get the block with the rank and the layout inside the block group.
        $currentRank = 0;
        $dataBlockIndex = null;
        for ($index = $blockGroupIndex + 1; $index <= $blockGroupIndexMax; $index++) {
            if ($pageBlockLayouts[$index] === $dataBlockLayout
                && ++$currentRank === $dataBlockRank
            ) {
                $dataBlockIndex = $index;
                break;
            }
        }
        if ($dataBlockIndex === null) {
            return null;
        }

        $dataBlockEntity = $pageBlockEntities[$dataBlockIndex];
        $dataBlock = new SitePageBlockRepresentation($dataBlockEntity, $block->getServiceLocator());
        return $dataBlockKey
            ? $dataBlock->dataValue($dataBlockKey, $default)
            : $dataBlock;
    }
}
