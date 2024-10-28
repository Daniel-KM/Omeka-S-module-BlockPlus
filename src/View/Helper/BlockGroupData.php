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
     * Get block and data of other blocks or a group of blocks.
     *
     * @return SitePageBlockRepresentation|mixed If the data key is not set,
     * return the block. Else return the value matching the specified key in
     * block data.
     */
    public function __invoke(
        SitePageBlockRepresentation $block,
        string $dataBlockLayout,
        ?string $dataKey = null,
        $default = null
    ) {
        if (!$dataBlockLayout || !$dataKey) {
            return null;
        }

        $blockLayout = $block->layout();
        if ($blockLayout === 'blockGroup') {
            return null;
        }

        // It is not possible to get the position of the block in the page from
        // the representation, so get the entity.
        // Normally, the block is already cached by doctrine.

        /** @var \Omeka\Entity\SitePageBlock $blockEntity */
        $blockEntity = $this->entityManager->find(\Omeka\Entity\SitePageBlock::class, $block->id());
        $position = $blockEntity->getPosition();
        if ($position < 2) {
            return null;
        }

        $page = $block->page();

        $remainingSpan = 0;
        $isInsideCurrentBlockGroup = false;
        foreach ($page->blocks() as $pageBlockPosition => $pageBlock) {
            // The position is 1-based, but the array is 0-based..
            ++$pageBlockPosition;
            if ($pageBlockPosition >= $position) {
                return null;
            }
            $pageBlockLayout = $pageBlock->layout();
            if ($pageBlockLayout === 'blockGroup') {
                $pageBlockSpan = (int) ($pageBlock->data()['span'] ?? 0);
                $remainingSpan = $pageBlockSpan;
                $isInsideCurrentBlockGroup = $position <= ($pageBlockPosition + $remainingSpan);
                continue;
            }
            if (!$isInsideCurrentBlockGroup) {
                continue;
            }
            if ($dataBlockLayout !== $pageBlockLayout) {
                --$remainingSpan;
                if (!$remainingSpan) {
                    $isInsideCurrentBlockGroup = false;
                }
                continue;
            }
            return $dataKey
                ? $pageBlock->dataValue($dataKey, $default)
                : $pageBlock;
        }
        return null;
    }
}
