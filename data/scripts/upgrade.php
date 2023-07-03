<?php declare(strict_types=1);

namespace BlockPlus;

use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

if (version_compare($oldVersion, '3.0.3', '<')) {
    $sql = <<<'SQL'
UPDATE site_page_block
SET
    layout = "resourceText",
    data = REPLACE(data, '"partial":"common\\/block-layout\\/media-text', '"partial":"common\\/block-layout\\/resource-text')
WHERE layout = "mediaText";
SQL;
    $connection->executeStatement($sql);
}

if (version_compare($oldVersion, '3.0.5', '<')) {
    $sql = <<<'SQL'
UPDATE site_page_block
SET
    data = REPLACE(data, '"partial":"', '"template":"')
WHERE
    layout IN ('block', 'browsePreview', 'column', 'itemShowCase', 'itemWithMetadata', 'listOfSites', 'pageTitle', 'searchForm', 'separator', 'tableOfContents', 'assets', 'embedText', 'html', 'resourceText', 'simplePage');
SQL;
    $connection->executeStatement($sql);
}

if (version_compare($oldVersion, '3.3.11.3', '<')) {
    $sql = <<<'SQL'
UPDATE site_page_block
SET layout = "mirrorPage"
WHERE layout = "simplePage";
SQL;
    $connection->executeStatement($sql);
    $sql = <<<'SQL'
UPDATE site_page_block
SET layout = "externalContent"
WHERE layout = "embedText";
SQL;
    $connection->executeStatement($sql);
    $sql = <<<'SQL'
UPDATE site_page_block
SET data = REPLACE(data, "/embed-text", "/external-content")
WHERE layout = "externalContent";
SQL;
    $connection->executeStatement($sql);
}

if (version_compare($oldVersion, '3.3.11.4', '<')) {
    $sql = <<<'SQL'
UPDATE site_page_block
SET layout = "division"
WHERE layout = "column";
SQL;
    $connection->executeStatement($sql);
}

if (version_compare($oldVersion, '3.3.11.7', '<')) {
    $sql = <<<'SQL'
UPDATE site_page_block
SET
    data = REPLACE(
        REPLACE(
            data,
            '"use_api_v1":"0"',
            '"api":"2.0"'
        ),
        '"use_api_v1":"1"',
        '"api":"1.1"'
    )
WHERE
    layout = "twitter";
SQL;
    $connection->executeStatement($sql);
}

if (version_compare($oldVersion, '3.3.11.8', '<')) {
    $message = new Message(
        'Change: The method "blockMetadata()" returns an array by default for key "params_json". Use key "params_json_object" to keep object output.' // @translate
    );
    $messenger->addWarning($message);

    $this->installAllResources();

    /** @var \Omeka\Api\Representation\VocabularyRepresentation $vocabulary */
    $vocabulary = $api->searchOne('vocabularies', ['prefix' => 'curation'])->getContent();
    if (!$vocabulary) {
        throw new \Omeka\Module\Exception\ModuleCannotInstallException(
            sprintf(
                'The vocabulary "%s" is not installed.', // @translate
                'curation'
            )
        );
    }

    // Check if the vocabulary was not updated.
    if ($vocabulary->propertyCount() < 3) {
        $vocabularyId = $vocabulary->id();
        $ownerId = $vocabulary->owner() ? $vocabulary->owner()->id() : 'NULL';
        // TODO Use rdf import process (see VocabularyController).
        $properties = [
            [
                'local_name' => 'reservedAccess',
                'label' => 'Is reserved Access', // @translate
                'comment' => 'Gives an ability for private resource to be previewed.', // @translate
            ],
            [
                'local_name' => 'newResource',
                'label' => 'Is new resource', // @translate
                'comment' => 'Allows to identify a resource as a new one.', // @translate
            ],
            [
                'local_name' => 'category',
                'label' => 'Category', // @translate
                'comment' => 'Non-standard topic that can be used for some purposes.', // @translate
            ],
        ];
        foreach ($properties as $property) {
            $sql = <<<SQL
INSERT INTO property
    (owner_id, vocabulary_id, local_name, label, comment)
VALUES
    ($ownerId, $vocabularyId, "{$property['local_name']}", "{$property['label']}", "{$property['comment']}")
ON DUPLICATE KEY UPDATE
   label = "{$property['label']}",
   comment = "{$property['comment']}"
;
SQL;
            $connection->executeStatement($sql);
        }
    }
}

if (version_compare($oldVersion, '3.3.13.0', '<')) {
    require_once __DIR__ . '/upgrade_vocabulary.php';
}

if (version_compare($oldVersion, '3.3.13.1', '<')) {
    // Convert assets into "assets" to merge with new upstream feature.
    $qb = $connection->createQueryBuilder();
    $qb
        ->select(
            'id',
            'data'
        )
        ->from('site_page_block', 'site_page_block')
        ->orderBy('site_page_block.id', 'asc')
        ->where('site_page_block.layout = "assets"')
    ;
    $blockDatas = $connection->executeQuery($qb)->fetchAllKeyValue();
    foreach ($blockDatas as $id => $blockData) {
        $blockData = json_decode($blockData, true);
        $attachments = $blockData['assets'] ?? [];
        $blockData['attachments'] = [];
        foreach ($attachments as $attachment) {
            $newAttachment = [];
            $newAttachment['id'] = $attachment['asset'] ?? '';
            $newAttachment['page'] = '';
            $newAttachment['alt_link_title'] = $attachment['title'] ?? '';
            $newAttachment['caption'] = $attachment['caption'] ?? '';
            $newAttachment['class'] = $attachment['class'] ?? '';
            $newAttachment['url'] = $attachment['url'] ?? '';
            $blockData['attachments'][] = $newAttachment;
        }
        // The upstream version stores attachment in root.
        foreach ($blockData['attachments'] as $attachment) {
            $blockData[] = $attachment;
        }
        $blockData['template'] = empty($blockData['template'])
            ? 'common/block-layout/asset-block'
            : str_replace('/assets-', '/asset-', $blockData['template']);
        $blockData['className'] = '';
        $blockData['alignment'] = 'default';
        unset($blockData['assets']);

        $quotedBlock = $connection->quote(json_encode($blockData));
        $sql = <<<SQL
UPDATE `site_page_block`
SET
    `layout` = "asset",
    `data` = $quotedBlock
WHERE `id` = $id;
SQL;
        $connection->executeStatement($sql);
    }

    $message = new Message(
        'The block "Assets" was merged with the new upstream block "Asset".' // @translate
    );
    $messenger->addSuccess($message);
    $message = new Message(
        'You may have to check the pages when a specific template is used, in particular for deprecated keys "title", replaced by "alt_link_title", and "url", replaced by "page" (or hacked with caption, or alt link title, or asset title).' // @translate
    );
    $messenger->addWarning($message);
    $message = new Message(
        'Furthermore, it is recommended to rename "assets" templates as "asset-xxx" and to update pages accordingly. You may replace the default template with "asset-block" too.' // @translate
    );
    $messenger->addWarning($message);
    $message = new Message(
        'The block still supports html captions and media assets.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.3.14.0', '<')) {
    $message = new Message(
        'It’s now possible to maximize the field "Html" in page edition.' // @translate
    );
    $messenger->addSuccess($message);
    $message = new Message(
        'It’s now possible to add footnotes in fields "Html" in page edition.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.3.14.1', '<')) {
    require_once __DIR__ . '/upgrade_vocabulary.php';
}

if (version_compare($oldVersion, '3.3.14.2', '<')) {
    require_once __DIR__ . '/upgrade_vocabulary.php';
}

if (version_compare($oldVersion, '3.3.15.1', '<')) {
    require_once __DIR__ . '/upgrade_vocabulary.php';
}

if (version_compare($oldVersion, '3.3.15.2', '<')) {
    $settings->set('blockplus_html_mode_page', $settings->get('blockplus_html_mode') ?: 'inline');
    $settings->set('blockplus_html_config_page', $settings->get('blockplus_html_config') ?: 'default');
    $settings->set('datatyperdf_html_mode_resource', $settings->get('datatyperdf_html_mode_resource', $settings->get('blockplus_html_mode')) ?: 'inline');
    $settings->set('datatyperdf_html_config_resource', $settings->get('datatyperdf_html_config_resource', $settings->get('blockplus_html_config')) ?: 'default');
    $settings->delete('blockplus_html_mode');
    $settings->delete('blockplus_html_config');

    $message = new Message(
        'It’s now possible to choose mode of display to edit html blocks of pages in main params.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.3.15.5', '<')) {
    $message = new Message(
        'The output for block D3 Graph was modified. Check it if you modified the template in your theme.' // @translate
    );
    $messenger->addWarning($message);
}

if (version_compare($oldVersion, '3.4.15.7', '<')) {
    // Remove "url" from old block plus version of asset, previously replaced by upstream version.
    /** @var \Omeka\View\Helper\Hyperlink $hyperlink */
    $hyperlink = $services->get('ViewHelperManager')->get('hyperlink');
    $qb = $connection->createQueryBuilder();
    $qb
        ->select(
            'id',
            'data',
            'page_id',
        )
        ->from('site_page_block', 'site_page_block')
        ->orderBy('site_page_block.id', 'asc')
        ->where('site_page_block.layout = "asset"')
    ;
    $pages = [];
    $blocks = $connection->executeQuery($qb)->fetchAllAssociativeIndexed();
    foreach ($blocks as $id => $block) {
        $blockData = json_decode($block['data'], true);
        $matches = [];
        $attachments = $blockData['attachments'] ?? [];
        // Don't update block if attachments are standard ones.
        if (!$attachments) {
            continue;
        }
        $blockData['attachments'] = [];
        foreach ($attachments as $attachment) {
            $newAttachment = [];
            $newAttachment['id'] = $attachment['id'] ?? $attachment['asset'] ?? null;
            $newAttachment['page'] = $attachments['page'] ?? null;
            $newAttachment['caption'] = $attachment['caption'] ?? '';
            $newAttachment['alt_link_title'] = isset($newAttachment['alt_link_title']) && $newAttachment['alt_link_title'] !== ''
                ? $newAttachment['title'] ?? ''
                : '';
            $newAttachment['class'] = $attachment['class'] ?? '';
            // Keep the url as page if possible, else as class, managed by
            // special block.
            $hasClassUrl = false;
            if (!empty($attachment['url'])) {
                // Absolute url = external url in most of cases.
                // Require manual check.
                if (filter_var($attachment['url'], FILTER_VALIDATE_URL) || $newAttachment['page']) {
                    $newAttachment['class'] = trim($newAttachment['class'] . ' ' . $attachment['url']);
                    $hasClassUrl = true;
                }
                // Relative url: check the page if none.
                else {
                    preg_match('~/s/(?<site>[\w_-]+)/page/(?<page>[\w_-]+)~', $attachment['url'], $matches);
                    if ($matches['page']) {
                        try {
                            /** @var \Omeka\Api\Representation\SitePageRepresentation $page */
                            $site = $api->read('sites', ['slug' => $matches['site']])->getContent();
                            $page = $api->read('site_pages', ['site' => $site->id(), 'slug' => $matches['page']])->getContent();
                            $newAttachment['page'] = $page->id();
                        } catch (\Omeka\Api\Exception\NotFoundException $e) {
                            $newAttachment['class'] = trim($newAttachment['class'] . ' ' . $attachment['url']);
                            $hasClassUrl = true;
                        }
                    }
                }
            }
            if ($hasClassUrl) {
                $blockData['template'] = empty($blockData['template']) || $blockData['template'] === 'common/block-layout/asset'
                    ? 'common/block-layout/asset-class-url'
                    : $blockData['template'];
            }
            $blockData['attachments'][] = $newAttachment;
            $pageId = (int) $block['page_id'];
            if (!isset($pages[$pageId])) {
                try {
                    /** @var \Omeka\Api\Representation\SitePageRepresentation $page */
                    $page = $api->read('site_pages', ['id' => $pageId])->getContent();
                    $pages[$pageId] = $hyperlink->raw($page->title(), $page->siteUrl());
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                }
            }
        }
        // The upstream version stores attachment in root.
        foreach ($blockData['attachments'] as $attachment) {
            $blockData[] = $attachment;
        }
        $blockData['heading'] ??= '';
        $blockData['template'] = empty($blockData['template'])
            ? 'common/block-layout/asset-block'
            : $blockData['template'];
        $blockData['className'] = '';
        $blockData['alignment'] ??= 'default';
        unset($blockData['assets']);
        // Keep standard format for compatibility.
        foreach ($blockData as $key => $value) {
            if (is_array($value) && array_key_exists('id', $value)) {
                unset($blockData[$key]);
            }
        }
        foreach ($blockData['attachments'] as $attachment) {
            $blockData[] = $attachment;
        }
        $quotedBlock = $connection->quote(json_encode($blockData));
        $sql = <<<SQL
UPDATE `site_page_block`
SET
    `data` = $quotedBlock
WHERE `id` = $id;
SQL;
        $connection->executeStatement($sql);
    }

    // Fix query with "?" for old core blocks.
    $qb
        ->select(
            'id',
            'data',
            'page_id',
        )
        ->from('site_page_block', 'site_page_block')
        ->orderBy('site_page_block.id', 'asc')
        ->where('site_page_block.layout = "browsePreview"')
    ;
    $pages = [];
    $blocks = $connection->executeQuery($qb)->fetchAllAssociativeIndexed();
    foreach ($blocks as $id => $block) {
        $blockData = json_decode($block['data'], true);
        $query = [];
        parse_str(ltrim($blockData['query'] ?? '', "? \t\n\r\0\x0B"), $query);
        $blockData['query'] = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $quotedBlock = $connection->quote(json_encode($blockData));
        $sql = <<<SQL
UPDATE `site_page_block`
SET
    `data` = $quotedBlock
WHERE `id` = $id;
SQL;
        $connection->executeStatement($sql);
        $pageId = (int) $block['page_id'];
        if (!isset($pages[$pageId])) {
            try {
                /** @var \Omeka\Api\Representation\SitePageRepresentation $page */
                $page = $api->read('site_pages', ['id' => $pageId])->getContent();
                $pages[$pageId] = $hyperlink->raw($page->title(), $page->siteUrl());
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
            }
        }
    }

    $message = new Message(
        'Template "Asset": The variable $assets has been replaced by $attachments; attachment key "title" by "alt_link_title"; attachment key "url" was removed. Check it if you customized template.' // @translate
    );
    $messenger->addWarning($message);

    if ($pages) {
        $message = new Message(
            'The key "url" of attachments of block "Asset" was removed. The block template should be updated if you customized it in pages: %s', // @translate
            '<ul><li>' . implode('</li><li>', $pages) . '</li></ul>'
        );
        $message->setEscapeHtml(false);
        $messenger->addWarning($message);
    }
}

if (version_compare($oldVersion, '3.4.16', '<')) {
    if ($this->isModuleActive('Menu')) {
        $settings->set('blockplus_property_itemset', $settings->get('menu_property_itemset', null));

        $siteSettings = $services->get('Omeka\Settings\Site');
        $siteIds = $api->search('sites', [], ['returnScalar' => 'id'])->getContent();
        foreach ($siteIds as $siteId) {
            $siteSettings->setTargetId($siteId);
            $siteSettings->set('blockplus_breadcrumbs_crumbs', $siteSettings->get('menu_breadcrumbs_crumbs', ['home','collections', 'itemset', 'itemsetstree', 'current']));
            $siteSettings->set('blockplus_breadcrumbs_prepend', $siteSettings->get('menu_breadcrumbs_prepend', []));
            $siteSettings->set('blockplus_breadcrumbs_collections_url', $siteSettings->get('menu_breadcrumbs_collections_url', ''));
            $siteSettings->set('blockplus_breadcrumbs_separator', $siteSettings->get('menu_breadcrumbs_separator', ''));
            $siteSettings->set('blockplus_breadcrumbs_homepage', $siteSettings->get('menu_breadcrumbs_homepage', false));
        }

        $message = new Message(
            'The feature "Breadcrumbs" was moved from module "Menu" into this module. Upgrade is automatic. Check your options if you use it.' // @translate
        );
        $messenger->addWarning($message);
    } else {
        $message = new Message(
            'it is now possible to define a breadcrumbs (may need to be added inside theme).' // @translate
        );
        $messenger->addWarning($message);
    }

    $sql = <<<SQL
UPDATE `site_page_block`
SET
    `data` = CONCAT(SUBSTRING(`data`, 1, LENGTH(`data`) - 1), ',"searchConfig":null}')
WHERE `layout` = 'searchForm'
    AND `data` IS NOT NULL
    AND `data` != ''
;
SQL;
    $connection->executeStatement($sql);
}

if (version_compare($oldVersion, '3.4.17', '<')) {
    $message = new Message('Two new resource page blocks has been added, in particular to display buttons to previous and next resource (require module Easy Admin).'); // @translate
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.4.18', '<')) {
    // Reset the session for browse page, managed differently.
    $session = new \Laminas\Session\Container('EasyAdmin');
    $session->lastBrowsePage = [];
    $session->lastQuery = [];
}
