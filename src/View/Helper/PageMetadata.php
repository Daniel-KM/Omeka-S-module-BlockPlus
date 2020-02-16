<?php
namespace BlockPlus\View\Helper;

use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Representation\SitePageRepresentation;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper to get metadata about the current page.
 */
class PageMetadata extends AbstractHelper
{
    /**
     * Get metadata of the current page.
     *
     * @param string $metadata
     * @param SitePageRepresentation $page
     * @return \Omeka\Api\Representation\SitePageBlockRepresentation|mixed|false
     * False means that the current page does not have a page block metadata.
     */
    public function __invoke($metadata = null, SitePageRepresentation $page = null)
    {
        $view = $this->getView();

        /**
         * @var \Omeka\Api\Representation\SitePageRepresentation $page
         */
        if (!$page) {
            if (empty($view->page)) {
                $pageSlug = $view->params()->fromRoute('page-slug');
                if (empty($pageSlug)) {
                    return false;
                }
                try {
                    // Api doesn't allow to search pages by slug.
                    $site = $this->currentSite();
                    $page = $view->api()->read('site_pages', ['site' => $site->id(), 'slug' => $pageSlug])->getContent();
                } catch (NotFoundException $e) {
                    return false;
                }
            } else {
                $page = $view->page;
            }
        }

        $block = null;
        foreach ($page->blocks() as $block) {
            // TODO A page can belong to multiple types?
            if ($block->layout() === 'pageMetadata') {
                break;
            }
        }
        if (!$block) {
            return false;
        }

        // TODO Get the parent page and the root page of an exhibit.

        switch ($metadata) {
            case 'page':
                return $block->page();
            case 'title':
                return $block->page()->title();
            case 'slug':
                return $block->page()->slug();

            case 'type':
            case 'credits':
            case 'summary':
            case 'tags':
                return $block->dataValue($metadata);

            case 'type_label':
                $type = $block->dataValue('type');
                $pageTypes = $view->siteSetting('blockplus_page_types', []);
                return isset($pageTypes[$type])
                    ? $pageTypes[$type]
                    : null;;

            case 'featured':
                return (bool) $block->dataValue('featured');
            case 'cover':
                $asset = $block->dataValue('cover');
                return $asset
                    ? $view->api()->searchOne('assets', ['id' => $asset])->getContent()
                    : null;

            case 'attachments':
                return $block->attachments();

            case is_null($metadata):
                return $block;

            case 'params':
            case 'params_raw':
                return $block->dataValue('params', '');
            case 'params_json':
                return @json_decode($block->dataValue('params', ''));
            case 'params_key_value':
            default:
                $params = array_filter(array_map('trim', explode("\n", $block->dataValue('params', ''))));
                $list = [];
                foreach ($params as $keyValue) {
                    list($key, $value) = array_map('trim', explode('=', $keyValue, 2));
                    if ($key !== '') {
                        $list[$key] = $value;
                    }
                }
                if ($metadata === 'params_key_value') {
                    return $list;
                }
                return isset($list[$metadata])
                    ? $list[$metadata]
                    : null;
        }
    }

    /**
     * @return \Omeka\Api\Representation\SiteRepresentation
     */
    protected function currentSite()
    {
        $view = $this->getView();
        return isset($view->site)
            ? $view->site
            : $view->getHelperPluginManager()->get('Zend\View\Helper\ViewModel')->getRoot()->getVariable('site');
    }
}
