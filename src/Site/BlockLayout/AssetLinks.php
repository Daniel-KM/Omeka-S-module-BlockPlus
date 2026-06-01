<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Exception as ApiException;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\Asset;
use Omeka\Stdlib\ErrorStore;

class AssetLinks extends Asset
{
    public function getLabel()
    {
        return 'Asset links'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
        parent::prepareForm($view);
        $view->headScript()->appendFile($view->assetUrl('js/asset-links-form.js', 'BlockPlus'));
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();
        $block->setData($data);
    }

    public function prepareAssetAttachments(PhpRenderer $view, $blockData, SiteRepresentation $site)
    {
        if (!$blockData) {
            return [];
        }

        $attachments = [];

        $sitePages = $site->pages();
        $sitePageArray = [];
        foreach ($sitePages as $sitePage) {
            $sitePageArray[$sitePage->id()] = $sitePage;
        }

        foreach ($blockData as $key => $value) {
            if (isset($value['id'])) {
                if ($value['id'] !== '') {
                    $assetId = $value['id'];
                    try {
                        $asset = $view->api()->read('assets', $assetId)->getContent();
                        $attachments[$key]['asset'] = $asset;
                    } catch (ApiException\NotFoundException $e) {
                        $attachments[$key]['asset'] = null;
                    }
                } else {
                    $attachments[$key]['asset'] = null;
                }
                if ($value['page'] !== '') {
                    $linkPageId = $value['page'];
                    $attachments[$key]['page'] = (isset($sitePageArray[$linkPageId])) ? $sitePageArray[$linkPageId] : null;
                }
                if (isset($value['resource']) && $value['resource'] !== '') {
                    try {
                        $attachments[$key]['resource'] = $view->api()->read('resources', ['id' => $value['resource']])->getContent();
                    } catch (ApiException\NotFoundException $e) {
                        $attachments[$key]['resource'] = null;
                    }
                } else {
                    $attachments[$key]['resource'] = null;
                }
                $attachments[$key]['alt_link_title'] = $value['alt_link_title'];
                $attachments[$key]['caption'] = $value['caption'];
            }
        }

        return $attachments;
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, ?SitePageRepresentation $page = null, ?SitePageBlockRepresentation $block = null
    ) {
        $siteId = $site->id();
        $apiUrl = $site->apiUrl();
        $blockData = ($block) ? $block->data() : '';
        $attachments = $this->prepareAssetAttachments($view, $blockData, $site);
        return $view->partial('common/asset-links-block-form', [
            'block' => $blockData,
            'siteId' => $siteId,
            'apiUrl' => $apiUrl,
            'attachments' => $attachments,
        ]);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = 'common/block-layout/asset-links')
    {
        $blockData = ($block) ? $block->data() : '';
        $site = $view->site;
        $attachments = $this->prepareAssetAttachments($view, $blockData, $site);
        return $view->partial($templateViewScript, [
            'block' => $block,
            'attachments' => $attachments,
        ]);
    }
}
