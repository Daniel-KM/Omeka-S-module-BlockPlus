<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper to get metadata for all pages of the specified type.
 */
class PagesMetadata extends AbstractHelper
{
    /**
     * Get data for all pages of the specified type in the current site.
     *
     * @param string|array|null $pageType Limit result to these page types. When
     *   empty, return all pages metadata blocks.
     * @return \Omeka\Api\Representation\SitePageBlockRepresentation[]
     */
    public function __invoke($pageType = null): array
    {
        if (empty($pageType)) {
            $pageTypes = false;
        } else {
            $pageTypes = is_array($pageType) ? $pageType : [(string) $pageType];
        }

        $pageBlocks = [];

        // Check if the site page has the specified block.
        $site = $this->currentSite();
        $pages = $site->pages();
        foreach ($pages as $page) {
            foreach ($page->blocks() as $block) {
                // A page can belong to multiple types…
                if ($block->layout() === 'pageMetadata'
                    && (!$pageTypes || in_array($block->dataValue('type'), $pageTypes))
                ) {
                    $pageBlocks[$page->slug()] = $block;
                    break;
                }
            }
        }

        return $pageBlocks;
    }

    protected function currentSite(): ?\Omeka\Api\Representation\SiteRepresentation
    {
        return $this->view->site ?? $this->view->site = $this->view
            ->getHelperPluginManager()
            ->get('Laminas\View\Helper\ViewModel')
            ->getRoot()
            ->getVariable('site');
    }
}
