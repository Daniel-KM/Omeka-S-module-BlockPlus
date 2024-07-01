<?php declare(strict_types=1);

namespace BlockPlus;

use Common\Stdlib\PsrMessage;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\View\Helper\Url $url
 * @var \Omeka\Settings\Settings $settings
 * @var \Laminas\I18n\View\Helper\Translate $translate
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$translate = $plugins->get('translate');
$translator = $services->get('MvcTranslator');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.58')) {
    $message = new \Omeka\Stdlib\Message(
        $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
        'Common', '3.4.58'
    );
    throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
}

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
    $message = new PsrMessage(
        'Change: The method "blockMetadata()" returns an array by default for key "params_json". Use key "params_json_object" to keep object output.' // @translate
    );
    $messenger->addWarning($message);

    $this->installAllResources();

    /** @var \Omeka\Api\Representation\VocabularyRepresentation $vocabulary */
    $vocabulary = $api->searchOne('vocabularies', ['prefix' => 'curation'])->getContent();
    if (!$vocabulary) {
        $message = new PsrMessage(
            'The vocabulary "{vocabulary}" is not installed.', // @translate
            ['vocabulary' => 'curation']
        );
        throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message->setTranslator($translator));
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

    $message = new PsrMessage(
        'The block "Assets" was merged with the new upstream block "Asset".' // @translate
    );
    $messenger->addSuccess($message);
    $message = new PsrMessage(
        'You may have to check the pages when a specific template is used, in particular for deprecated keys "title", replaced by "alt_link_title", and "url", replaced by "page" (or hacked with caption, or alt link title, or asset title).' // @translate
    );
    $messenger->addWarning($message);
    $message = new PsrMessage(
        'Furthermore, it is recommended to rename "assets" templates as "asset-xxx" and to update pages accordingly. You may replace the default template with "asset-block" too.' // @translate
    );
    $messenger->addWarning($message);
    $message = new PsrMessage(
        'The block still supports html captions and media assets.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.3.14.0', '<')) {
    $message = new PsrMessage(
        'It’s now possible to maximize the field "Html" in page edition.' // @translate
    );
    $messenger->addSuccess($message);
    $message = new PsrMessage(
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

    $message = new PsrMessage(
        'It’s now possible to choose mode of display to edit html blocks of pages in main params.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.3.15.5', '<')) {
    $message = new PsrMessage(
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

    $message = new PsrMessage(
        'Template "Asset": The variable $assets has been replaced by $attachments; attachment key "title" by "alt_link_title"; attachment key "url" was removed. Check it if you customized template.' // @translate
    );
    $messenger->addWarning($message);

    if ($pages) {
        $message = new PsrMessage(
            'The key "url" of attachments of block "Asset" was removed. The block template should be updated if you customized it in pages: {html}', // @translate
            ['html' => '<ul><li>' . implode('</li><li>', $pages) . '</li></ul>']
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

        $message = new PsrMessage(
            'The feature "Breadcrumbs" was moved from module "Menu" into this module. Upgrade is automatic. Check your options if you use it.' // @translate
        );
        $messenger->addWarning($message);
    } else {
        $message = new PsrMessage(
            'It is now possible to define a breadcrumbs (may need to be added inside theme).' // @translate
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
    $message = new PsrMessage('Two new resource page blocks has been added, in particular to display buttons to previous and next resource (require module Easy Admin).'); // @translate
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.4.18', '<')) {
    // Reset the session for browse page, managed differently.
    $session = new \Laminas\Session\Container('EasyAdmin');
    $session->lastBrowsePage = [];
    $session->lastQuery = [];
}

if (version_compare($oldVersion, '3.4.19', '<')) {
    // Update vocabulary via sql.
    foreach ([
        'curation:dateStart' => 'curation:start',
        'curation:dateEnd' => 'curation:end',
    ] as $propertyOld => $propertyNew) {
        $propertyOld = $api->searchOne('properties', ['term' => $propertyOld])->getContent();
        $propertyNew = $api->searchOne('properties', ['term' => $propertyNew])->getContent();
        if ($propertyOld && $propertyNew) {
            // Remove the new property, it will be created below.
            $connection->executeStatement('UPDATE `value` SET `property_id` = :property_id_1 WHERE `property_id` = :property_id_2;', [
                'property_id_1' => $propertyOld->id(),
                'property_id_2' => $propertyNew->id(),
            ]);
            $connection->executeStatement('UPDATE `resource_template_property` SET `property_id` = :property_id_1 WHERE `property_id` = :property_id_2;', [
                'property_id_1' => $propertyOld->id(),
                'property_id_2' => $propertyNew->id(),
            ]);
            try {
                $connection->executeStatement('UPDATE `resource_template_property_data` SET `resource_template_property_id` = :property_id_1 WHERE `property_id` = :property_id_2;', [
                    'property_id_1' => $propertyOld->id(),
                    'property_id_2' => $propertyNew->id(),
                ]);
            } catch (\Exception $e) {
            }
            $connection->executeStatement('DELETE FROM `property` WHERE id = :property_id;', [
                'property_id' => $propertyNew->id(),
            ]);
        }
    }

    $sql = <<<SQL
UPDATE `vocabulary`
SET
    `comment` = 'Generic and common properties that are useful in Omeka for the curation of resources. The use of more common or more precise ontologies is recommended when it is possible.'
WHERE `prefix` = 'curation'
;
UPDATE `property`
JOIN `vocabulary` on `vocabulary`.`id` = `property`.`vocabulary_id`
SET
    `property`.`local_name` = 'start',
    `property`.`label` = 'Start',
    `property`.`comment` = 'A start related to the resource, for example the start of an embargo.'
WHERE
    `vocabulary`.`prefix` = 'curation'
    AND `property`.`local_name` = 'dateStart'
;
UPDATE `property`
JOIN `vocabulary` on `vocabulary`.`id` = `property`.`vocabulary_id`
SET
    `property`.`local_name` = 'end',
    `property`.`label` = 'End',
    `property`.`comment` = 'A end related to the resource, for example the end of an embargo.'
WHERE
    `vocabulary`.`prefix` = 'curation'
    AND `property`.`local_name` = 'dateEnd'
;
SQL;
    $connection->executeStatement($sql);
}

if (version_compare($oldVersion, '3.4.22-alpha.2', '<')) {
    // Migrate blocks of this module to new blocks of Omeka S v4.1.

    /** @var \Laminas\Log\Logger $logger */
    $logger = $services->get('Omeka\Logger');

    // The process can be run multiple times without issue: migrated blocks are
    // not remigrated.

    // Upgrade is done only on blocks managed by this module.
    $blockTemplates = [
        // Overridden (but initially an original block), so removed.
        'asset' => 'common/block-layout/asset',
        'block' => 'common/block-layout/block',
        'breadcrumbs' => 'common/block-layout/breadcrumbs',
        // Overridden, so removed.
        'browsePreview' => 'common/block-layout/browse-preview',
        'buttons' => 'common/block-layout/buttons',
        'd3Graph' => 'common/block-layout/d3-graph',
        'division' => null,
        'externalContent' => 'common/block-layout/external-content',
        'itemSetShowcase' => 'common/block-layout/item-set-showcase',
        // Overridden (renamed media), removed.
        'itemShowcase' => 'common/block-layout/item-showcase',
        // Overridden (original name, renamed media), removed.
        'itemShowCase' => 'common/block-layout/item-showcase',
        // Overridden, so removed.
        'itemWithMetadata' => 'common/block-layout/item-with-metadata',
        // Overridden, so removed.
        'html' => 'common/block-layout/html',
        'links' => 'common/block-layout/links',
        // Overridden, so removed.
        'listOfPages' => 'common/block-layout/list-of-pages',
        // Overridden, so will be removed when pull request will be integrated.
        'listOfSites' => 'common/block-layout/list-of-sites',
        'mirrorPage' => null,
        'pageMetadata' => 'common/block-layout/page-metadata',
        'pageDate' => 'common/block-layout/page-date',
        // Overridden, so removed.
        'pageTitle' => 'common/block-layout/page-title',
        'redirectToUrl' => null,
        'resourceText' => 'common/block-layout/resource-text',
        'searchForm' => 'common/block-layout/search-form',
        'searchResults' => 'common/block-layout/search-results',
        'separator' => 'common/block-layout/separator',
        'showcase' => 'common/block-layout/showcase',
        // Overridden, so removed.
        'tableOfContents' => 'common/block-layout/table-of-contents',
        'treeStructure' => 'common/block-layout/tree-structure',
        'twitter' => 'common/block-layout/twitter',
    ];

    // Renamed module block templates.
    $blockTemplatesRenamed = [
        'asset' => 'asset-deprecated-plus',
        // The block "browse-preview" is renamed "browse-preview-deprecated"
        // when no specificy is used, else "search-results-browse-preview-deprecated".
        'browse-preview' => 'browse-preview-deprecated',
        'item-with-metadata' => 'item-with-metadata-deprecated',
        'list-of-pages' => 'list-of-pages-deprecated',
        'list-of-sites' => 'list-of-sites-deprecated',
        'item-showcase' => 'media-item-showcase-deprecated',
        'file-item-showcase' => 'media-item-showcase-deprecated',
        // This template is not deprecated.
        'page-date-time' => 'page-date-time-plus',
        'table-of-contents' => 'table-of-contents-deprecated',
    ];

    // Replay the core migrations.

    /** @see \Omeka\Db\Migrations\MigrateBlockLayoutData */
    $pageRepository = $entityManager->getRepository(\Omeka\Entity\SitePage::class);
    $blocksRepository = $entityManager->getRepository(\Omeka\Entity\SitePageBlock::class);

    // Asset: move divclass to layout as class.
    // Asset: move alignment to layout as class.
    // Done in Omeka migration, but redone to manage various upgrade process.
    foreach ($blocksRepository->findBy(['layout' => 'asset']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        if (isset($data['className'])) {
            $layoutData['class'] = $data['className'];
            unset($data['className']);
            $block->setData($data);
            $block->setLayoutData($layoutData);
        }
        if (isset($data['alignment'])) {
            $layoutData['alignment_block'] = $data['alignment'];
            if ('center' === $data['alignment']) {
                $layoutData['alignment_text'] = 'center';
            }
            unset($data['alignment']);
            $block->setData($data);
            $block->setLayoutData($layoutData);
        }
    }

    // Do a flush regularly to avoid memory issues and to allow failed upgrade.
    $entityManager->flush();

    // Html: move divclass to layout as class.
    // Done in Omeka migration.
    foreach ($blocksRepository->findBy(['layout' => 'html']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        if (isset($data['divclass'])) {
            $layoutData['class'] = $data['divclass'];
            unset($data['divclass']);
            $block->setData($data);
            $block->setLayoutData($layoutData);
        }
    }

    $entityManager->flush();

    // Media: move alignment to layout as class.
    // Done in Omeka migration.
    foreach ($blocksRepository->findBy(['layout' => 'media']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        if (isset($data['alignment'])) {
            $layoutData['alignment_block'] = $data['alignment'];
            if ('center' === $data['alignment']) {
                $layoutData['alignment_text'] = 'center';
            }
            unset($data['alignment']);
            $block->setData($data);
            $block->setLayoutData($layoutData);
        }
    }

    $entityManager->flush();

    /** @see \Omeka\Db\Migrations\ConvertItemShowcaseToMedia */

    // Convert item showcase blocks to media embed blocks.
    foreach ($blocksRepository->findBy(['layout' => 'itemShowCase']) as $block) {
        $data = $block->getData();
        $data['layout'] = 'horizontal';
        $data['media_display'] = 'thumbnail';
        $block->setData($data);
        $block->setLayout('media');
    }

    $entityManager->flush();

    // Item Showcase: the layout migrated in \Omeka\Db\Migrations is "itemShowCase",
    // but "itemShowcase" needs to be migrated too.
    foreach ($blocksRepository->findBy(['layout' => 'itemShowcase']) as $block) {
        $data = $block->getData();
        $data['layout'] = 'horizontal';
        $data['media_display'] = 'thumbnail';
        $block->setData($data);
        $block->setLayout('media');
    }

    $entityManager->flush();

    // Division: move class to layout as class.
    foreach ($blocksRepository->findBy(['layout' => 'division']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        if (isset($data['class'])) {
            $layoutData['class'] = $data['class'];
            unset($data['class']);
            $block->setData($data);
            $block->setLayoutData($layoutData);
        }
    }

    $entityManager->flush();

    // External Content: move alignment to layout as class.
    foreach ($blocksRepository->findBy(['layout' => 'externalContent']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        if (isset($data['alignment'])) {
            $layoutData['alignment_block'] = $data['alignment'];
            if ('center' === $data['alignment']) {
                $layoutData['alignment_text'] = 'center';
            }
            unset($data['alignment']);
            $block->setData($data);
            $block->setLayoutData($layoutData);
        }
    }

    $entityManager->flush();

    // Resource Text: move alignment to layout as class.
    foreach ($blocksRepository->findBy(['layout' => 'resourceText']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        if (isset($data['alignment'])) {
            $layoutData['alignment_block'] = $data['alignment'];
            if ('center' === $data['alignment']) {
                $layoutData['alignment_text'] = 'center';
            }
            unset($data['alignment']);
            $block->setData($data);
            $block->setLayoutData($layoutData);
        }
    }

    $entityManager->flush();

    // Separator: move class to layout as class.
    // Separator: replace by block Line-break.
    foreach ($blocksRepository->findBy(['layout' => 'separator']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        $layoutData['class'] = empty($data['class']) ? 'separator' : trim($data['class'] . ' separator');
        $data = ['break_type' => 'transparent'];
        $block->setData($data);
        $block->setLayoutData($layoutData);
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    $message = new PsrMessage(
        'The block "Separator" was replaced by the Omeka block "Line Break" with block class "transparent" and layout class "separator".' // @translate
    );
    $messenger->addWarning($message);

    $entityManager->flush();

    // Showcase: move divclass to layout as class.
    foreach ($blocksRepository->findBy(['layout' => 'showcase']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        if (isset($data['divclass'])) {
            $layoutData['class'] = $data['divclass'];
            unset($data['divclass']);
            $block->setData($data);
            $block->setLayoutData($layoutData);
        }
    }

    $entityManager->flush();

    $message = new PsrMessage('The options "alignment" and "divclass" of some blocks were moved to block layout.'); // @translate
    $messenger->addWarning($message);

    // Division: replace by a group of blocks.
    $divisions = $blocksRepository->findBy(['layout' => 'division']);
    if (count($divisions)) {
        // Method adapted from old block Division.
        $checkBlockData = function (\Omeka\Entity\SitePageBlock $block) use ($logger, $messenger): ?array {
            // Store data about page on the first pass because they are updated.
            static $pageDivisions = [];
            static $blockDivisions = [];

            $page = $block->getPage();
            $pageId = $page->getId();
            $blockId = $block->getId();

            if (isset($pageDivisions[$pageId])) {
                return empty($pageDivisions[$pageId]) || empty($blockDivisions[$blockId])
                    ? null
                    : ($pageDivisions[$pageId][$blockDivisions[$blockId]] ?? null);
            }

            $pageSlug = $page->getSlug();
            $siteSlug = $page->getSite()->getSlug();
            $blockPosition = 0;

            // Check and save the previous tag to close elements quickly.
            $blocks = $page->getBlocks();
            $divisions = [];
            $tagStack = [];
            // Block representation doesn't know its position.
            $position = 0;
            foreach ($blocks as $blk) {
                if ($blk->getLayout() !== 'division') {
                    continue;
                }
                $dta = $blk->getData();
                $division = [
                    'type' => $dta['type'],
                    'tag' => $dta['tag'],
                    'class' => $dta['class'] ?? null,
                    'close' => null,
                ];
                switch ($dta['type']) {
                    case 'end':
                    case 'inter':
                        if (empty($tagStack)) {
                            $pageDivisions[$pageId] = [];
                            $message = new PsrMessage(
                                'Site {site_slug} / Page {page_slug}: Type "intermediate" and "end" divisions must be after a block "start" or "intermediate".', // @translate
                                ['site_slug' => $siteSlug, 'page_slug' => $pageSlug]
                            );
                            $messenger->addWarning($message);
                            $logger->warn($message->getMessage(), $message->getContext());
                            return null;
                        }
                        $division['close'] = array_pop($tagStack);
                        if ($dta['type'] === 'end') {
                            break;
                        }
                        // no break.
                    case 'start':
                        $tagStack[] = $dta['tag'];
                        break;
                    default:
                        $pageDivisions[$pageId] = [];
                        $message = new PsrMessage(
                            'Site {site_slug} / Page {page_slug}: Unauthorized type "{type}" for block division.', // @translate
                            ['site_slug' => $siteSlug, 'page_slug' => $pageSlug, 'type' => $dta['type']]
                        );
                        $messenger->addWarning($message);
                        $logger->warn($message->getMessage(), $message->getContext());
                        return null;
                }
                $blkId = $blk->getId();
                $divisions[++$position] = $division;
                $blockDivisions[$blkId] = $position;
                if ($blockId === $blkId) {
                    $blockPosition = $position;
                }
            }

            if (count($divisions) < 2) {
                $pageDivisions[$pageId] = [];
                $message = new PsrMessage(
                    'Site {site_slug} / Page {page_slug}: A block "division" cannot be single.', // @translate
                    ['site_slug' => $siteSlug, 'page_slug' => $pageSlug]
                );
                $messenger->addWarning($message);
                $logger->warn($message->getMessage(), $message->getContext());
                return null;
            }

            ksort($divisions);
            $first = reset($divisions);
            if ($first['type'] !== 'start') {
                $pageDivisions[$pageId] = [];
                $message = new PsrMessage(
                    'Site {site_slug} / Page {page_slug}: The first division block must be of type "start".', // @translate
                    ['site_slug' => $siteSlug, 'page_slug' => $pageSlug]
                );
                $messenger->addWarning($message);
                $logger->warn($message->getMessage(), $message->getContext());
                return null;
            }

            $last = end($divisions);
            if ($last['type'] !== 'end') {
                $pageDivisions[$pageId] = [];
                $message = new PsrMessage(
                    'Site {site_slug} / Page {page_slug}: The last division block must be of type "end".', // @translate
                    ['site_slug' => $siteSlug, 'page_slug' => $pageSlug]
                );
                $messenger->addWarning($message);
                $logger->warn($message->getMessage(), $message->getContext());
                return null;
            }

            if (!empty($tagStack)) {
                $pageDivisions[$pageId] = [];
                $message = new PsrMessage(
                    'Site {site_slug} / Page {page_slug}: Some divisions have no end.', // @translate
                    ['site_slug' => $siteSlug, 'page_slug' => $pageSlug]
                );
                $messenger->addWarning($message);
                $logger->warn($message->getMessage(), $message->getContext());
                return null;
            }

            $pageDivisions[$pageId] = $divisions;

            return $divisions[$blockPosition];
        };

        // For each page with more than one division, replace the start block with block
        // "blockGroup" and a data with a span for the number of sub-blocks.

        $dql = 'SELECT DISTINCT p FROM Omeka\Entity\SitePage p JOIN Omeka\Entity\SitePageBlock b WHERE b.layout = :layout';
        $qb = $entityManager->createQuery($dql);
        $qb->setParameter('layout', 'division');
        $pages = $qb->getResult();

        /**
         * @var \Omeka\Entity\SitePage $page
         * @var \Omeka\Entity\SitePageBlock $block
         */
        foreach ($pages as $page) {
            $siteSlug = $page->getSite()->getSlug();
            $pageSlug = $page->getSlug();

            $blocks = $page->getBlocks();

            // First: check all blocks: only unnested pages can be processed.
            $prevStartBlock = null;
            $prevDivisionType = null;
            foreach ($blocks as $block) {
                if ($block->getLayout() !== 'division') {
                    continue;
                }

                $data = $checkBlockData($block);

                // An invalid block is kept and will be marked as unknown in page.
                if (!$data || empty($data['type']) || !in_array($data['type'], ['start', 'inter', 'end'])) {
                    // A message of issue is already logged.
                    continue 2;
                }

                $isStart = $data['type'] === 'start';
                $isInter = $data['type'] === 'inter';
                $isEnd = $data['type'] === 'end';

                // Check for nested divisions, that are not managed.
                if (($isStart && in_array($prevDivisionType, ['start', 'inter']))
                    || ($isInter && in_array($prevDivisionType, [null, 'end']))
                    || ($isEnd && in_array($prevDivisionType, [null, 'end']))
                ) {
                    $message = new PsrMessage(
                        'Site {site_slug} / Page {page_slug}: The migration does not manage nested divisions. You should finalize migration of the page manually, for example with grid and block groups.', // @translate
                        ['site_slug' => $siteSlug, 'page_slug' => $pageSlug]
                    );
                    $messenger->addWarning($message);
                    $logger->warn($message->getMessage(), $message->getContext());
                    continue 2;
                }

                if ($isStart || $isInter) {
                    $prevStartBlock = $block;
                    $prevDivisionType = 'start';
                } else {
                    $prevStartBlock = null;
                    $prevDivisionType = null;
                }
            }

            // Second: the divisions are valid and flat, so convert them.
            // And count the number of blocks between two divisions.
            $prevStartBlock = null;
            $prevDivisionType = null;
            $spans = 0;
            foreach ($blocks as $block) {
                // Count span between two divisions.
                if ($block->getLayout() !== 'division') {
                    $spans = $prevStartBlock ? $spans + 1 : 0;
                    continue;
                }

                $data = $checkBlockData($block);

                $isStart = $data['type'] === 'start';
                $isInter = $data['type'] === 'inter';
                $isEnd = $data['type'] === 'end';

                // Finalize any previous block with the count of spans.
                if ($prevStartBlock) {
                    $prevStartBlock->setData(['span' => $spans]);
                }

                // Reset count of spans in all cases.
                $spans = 0;

                if ($isStart || $isInter) {
                    // Start a new block.
                    $block->setLayout('blockGroup');
                    $block->setData(['span' => 0]);
                    $prevStartBlock = $block;
                    $prevDivisionType = 'start';
                } else {
                    // Remove block end.
                    $entityManager->remove($block);
                    $prevStartBlock = null;
                    $prevDivisionType = null;
                }
            }
        }

        $message = new PsrMessage('The blocks Division were replaced by a group of blocks when possible (not nested). See messages above or logs for issues.'); // @translate
        $messenger->addWarning($message);
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    /**
     * Prepend a specific block "Html" before Browse preview when filled.
     */
    $pagesWithHtml = [];
    $processedBlocksId = [];
    foreach ($pageRepository->findAll() as $page) {
        $pageId = $page->getId();
        $pageSlug = $page->getSlug();
        $siteSlug = $page->getSite()->getSlug();
        $position = 0;
        foreach ($page->getBlocks() as $block) {
            $block->setPosition(++$position);
            $layout = $block->getLayout();
            if ($layout !== 'browsePreview') {
                continue;
            }
            $blockId = $block->getId();
            $data = $block->getData() ?: [];
            $html = $data['html'] ?? '';
            $hasHtml = !in_array(str_replace([' ', "\n", "\r", "\t"], '', $html), ['', '<div></div>', '<p></p>']);
            if ($hasHtml && !isset($processedBlocksId[$blockId])) {
                $b = new \Omeka\Entity\SitePageBlock();
                $b->setLayout('html');
                $b->setPage($page);
                $b->setPosition(++$position);
                $b->setData([
                    'html' => $html,
                ]);
                $entityManager->persist($b);
                $block->setPosition(++$position);
                $pagesWithHtml[$siteSlug][$pageSlug] = $pageSlug;
                $processedBlocksId[$blockId] = $blockId;
            }
            unset($data['html']);
            $block->setData($data);
        }
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    if (!empty($pagesWithHtml)) {
        $pagesWithHtml = array_map('array_values', $pagesWithHtml);
        $message = new PsrMessage(
            'The setting "html" was removed from block Browse Preview. A new block "Html" was prepended to all blocks that had a filled html. You may check pages for styles: {json}', // @translate
            ['json' => json_encode($pagesWithHtml, 448)]
        );
        $messenger->addWarning($message);
        $logger->warn($message->getMessage(), $message->getContext());
    }

    // Rename template as "browse-preview-deprecated" if no specific params are
    // used, else convert it into a block Search results with template "search-results-browse-preview-deprecated".
    // Blocks converted as Search results with a heading are prepended with this
    // heading below.
    // This process is copied below in checks for version 3.4.22-beta.
    $pagesConverted = [];
    foreach ($blocksRepository->findBy(['layout' => 'browsePreview']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        $query = $data['query'] ?? '';
        $isQuerySiteOverridden = $query && preg_match('~site_page_id=\d+~', $query);
        $isPageOverridden = !empty($data['pagination']) || !empty($data['sort_headings']) || !empty($data['resource_template']);
        // Convert to block Search Results only when needed.
        if ($isQuerySiteOverridden || $isPageOverridden) {
            $page = $block->getPage();
            $pageSlug = $page->getSlug();
            $siteSlug = $page->getSite()->getSlug();
            $pagesConverted[$siteSlug][$pageSlug] = $pageSlug;
            $block->setLayout('searchResults');
            if (empty($layoutData['template_name'])
                || $layoutData['template_name'] === 'browse-preview'
                || $layoutData['template_name'] === 'browse-preview-plus'
                || $layoutData['template_name'] === 'browse-preview-deprecated'
            ) {
                $layoutData['template_name'] = 'search-results-browse-preview-deprecated';
            }
            // In search results, query is converted early.
            if (empty($data['query'])) {
                $data['query'] = [];
            } elseif (!is_array($data['query'])) {
                $query = [];
                parse_str(ltrim($data['query'], "? \t\n\r\0\x0B"), $query);
                $data['query'] = $query;
            }
            $resourceTypes = [
                'item' => 'items',
                'item-set' => 'item_sets',
                'media' => 'media',
            ];
            $data['resource_type'] = $resourceTypes[$data['resource_type'] ?? 'item'] ?? 'items';
        } elseif (empty($layoutData['template_name'])
            || $layoutData['template_name'] === 'browse-preview'
            || $layoutData['template_name'] === 'browse-preview-plus'
        ) {
            $layoutData['template_name'] = 'browse-preview-deprecated';
        }
        // Clean all existing useless browse preview block settings.
        unset($data['pagination'], $data['sort_headings'], $data['resource_template']);
        $block->setData($data);
        $block->setLayoutData($layoutData);
    }

    $entityManager->flush();

    $message = new PsrMessage(
        'The block Browse Preview is no more managed by this module. If you used specific settings, use the block Search Results.' // @translate
    );
    $messenger->addWarning($message);

    if ($pagesConverted) {
        $pagesConverted = array_map('array_values', $pagesConverted);
        $message = new PsrMessage(
            'The block "Browse Preview" filled with specific features (query with specific site, pagination and sort headings) were replaced by block Search results and template "search-results-browse-preview-deprecated": {json}', // @translate
            ['json' => json_encode($pagesConverted, 448)]
        );
        $messenger->addWarning($message);
        $logger->warn($message->getMessage(), $message->getContext());
    }

    /**
     * Replace filled setttings "heading" by a specific block "Heading".
     *
     * Because "itemShowcase" was renamed "media", append it to keep heading.
     * @see \Omeka\Db\Migrations\ConvertItemShowcaseToMedia
     */
    $blockTemplatesHeading = $blockTemplates;
    unset($blockTemplatesHeading['browsePreview']);
    $blockTemplatesHeading['media'] = null;

    $pagesWithHeading = [];
    $processedBlocksId = [];
    foreach ($pageRepository->findAll() as $page) {
        $pageId = $page->getId();
        $pageSlug = $page->getSlug();
        $siteSlug = $page->getSite()->getSlug();
        $position = 0;
        foreach ($page->getBlocks() as $block) {
            $block->setPosition(++$position);
            $layout = $block->getLayout();
            if (!isset($blockTemplatesHeading[$layout])) {
                continue;
            }
            $blockId = $block->getId();
            $data = $block->getData() ?: [];
            $heading = $data['heading'] ?? '';
            if (strlen($heading) && !isset($processedBlocksId[$blockId])) {
                $b = new \Omeka\Entity\SitePageBlock();
                $b->setLayout('heading');
                $b->setPage($page);
                $b->setPosition(++$position);
                $b->setData([
                    'text' => $heading,
                    'level' => 2,
                ]);
                $entityManager->persist($b);
                $block->setPosition(++$position);
                $pagesWithHeading[$siteSlug][$pageSlug] = $pageSlug;
                $processedBlocksId[$blockId] = $blockId;
            }
            unset($data['heading']);
            $block->setData($data);
        }
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    // In all cases, remove empty headings from all module blocks with heading.
    $sql = <<<'SQL'
UPDATE site_page_block
SET
    data = REPLACE(REPLACE(REPLACE(data,
        ',"heading":""', ''),
        '"heading":"",', ''),
        '"heading":""', '')
WHERE layout IN (:layouts)
SQL;
    $connection->executeStatement(
        $sql,
        ['layouts' => array_keys($blockTemplatesHeading)],
        ['layouts' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
    );

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    if (!empty($pagesWithHeading)) {
        $pagesWithHeading = array_map('array_values', $pagesWithHeading);
        $message = new PsrMessage(
            'The setting "heading" was removed from blocks. A new block "Heading" was prepended to all blocks that had a filled heading. You may check pages for styles: {json}', // @translate
            ['json' => json_encode($pagesWithHeading, 448)]
        );
        $messenger->addWarning($message);
        $logger->warn($message->getMessage(), $message->getContext());
    } else {
        $message = new PsrMessage(
            'A new block "Heading" allows to separate blocks with a html heading and replaces previous blocks with setting "heading".' // @translate
        );
        $messenger->addWarning($message);
    }

    // PageDate: replaced by PageDateTime (Omeka S).
    foreach ($blocksRepository->findBy(['layout' => 'pageDate']) as $block) {
        $block->setLayout('pageDateTime');
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        $data['display'] = in_array($data['dates'] ?? '', ['created', 'modified']) ? $data['dates'] : 'created_modified';
        $data['date_format'] = in_array($data['format_date'] ?? '', ['none', 'short', 'medium', 'long', 'full']) ? $data['format_date'] : 'medium';
        $data['time_format'] = in_array($data['format_time'] ?? '', ['none', 'short', 'medium', 'long', 'full']) ? $data['format_time'] : 'none';
        $template = $data['template'] ?? null;
        $layoutData['template_name'] = $template && $template !== $blockTemplates['pageDate']
            ? pathinfo($template, PATHINFO_FILENAME)
            : 'page-date-time-plus';
        unset($data['dates'], $data['format_date'], $data['format_time'], $data['template']);
        $block->setData($data);
        $block->setLayoutData($layoutData);
    }

    $entityManager->flush();

    $message = new PsrMessage(
        'The old block "Page Date" was replaced by the core one "Page Date Time". The variable names were changed, so check your theme if needed.' // @translate
    );
    $messenger->addWarning($message);

    /**
     * Replace filled settings "template" by the new layout data.
     * Some blocks were overridden only to add heading and template, so they are
     * now useless and removed too:
     * - asset (warning for subkeys of assets).
     * - browsePreview
     * - html
     * - itemShowCase (renamed media in core)
     * - itemShowcase (renamed media above)
     * - itemWithMetadata
     * - listOfPages
     * - pageDate (migrated below to pageDateTime)
     * - pageTitle
     * Migrated template, but not removed:
     * - listOfSites (to be ported in core)
     * - tableOfContents (to be ported in core)
     */

    // Warn only when the template is not the default and a message for moved
    // files in themes.
    // Nevertheless, use deprecated block templates when possible to simplify
    // migration of themes.
    // Other templates are kept, in particular for other modules.
    foreach ($blocksRepository->findAll() as $block) {
        $layout = $block->getLayout();
        if (!isset($blockTemplates[$layout])) {
            continue;
        }
        $data = $block->getData();
        $template = $data['template'] ?? null;
        if ($template) {
            $templateName = pathinfo($template, PATHINFO_FILENAME);
            $layoutData = $block->getLayoutData() ?? [];
            $existingTemplateName = $layoutData['template_name'] ?? '';
            if (!$existingTemplateName) {
                $layoutData['template_name'] = $blockTemplatesRenamed[$templateName] ?? $templateName;
            }
            $block->setLayoutData($layoutData);
        }
        unset($data['template']);
        $block->setData($data);
    }

    $entityManager->flush();

    $message = new PsrMessage(
        'For block Asset, the keys "class" and "url" of assets were moved to the start of the caption and available only with specific block templates.' // @translate
    );
    $messenger->addWarning($message);

    $message = new PsrMessage(
        'The block Item Showcase was renamed Media in Omeka S v4.1. The option "linkType" was renamed "link". Check your themes to update them.' // @translate
    );
    $messenger->addWarning($message);

    $message = new PsrMessage(
        'The templates item-showcase-xxx were renamed media-item-showcase-xxx. Check your themes to update them or to move them to common/block-template and to add them in theme.ini as templates for block "Media".' // @translate
    );
    $messenger->addWarning($message);

    $message = new PsrMessage(
        'The old template mechanism of the module BlockPlus was replaced by the new mechanism of Omeka S v4.1.' // @translate
    );
    $messenger->addWarning($message);

    $message = new PsrMessage(
        'It is recommended to check your themes and to move specific templates from "view/common/block-layout" to "view/common/block-template" and to declare them in the file theme.ini. This is not needed for the default template of a block. After moving, the option "template" of each block should be updated in each page.' // @translate
    );
    $messenger->addWarning($message);

    $message = new PsrMessage(
        'Because old features of the module were integrated inside Omeka S since v4.1 ({link}user doc{link_end}, {link_2}dev doc{link_end}), the blocks Asset, Browse Preview, Html, Item Showcase, Item With Metadata, List of Pages, Page Date, and Page Title are no more overridden.', // @translate
        [
            'link' => '<a href="https://omeka.org/s/docs/user-manual/sites/site_pages/#edit-a-page" target="_blank" rel="noopener">',
            'link_2' => '<a href="https://omeka.org/s/docs/developer/themes/theme_templates/" target="_blank" rel="noopener">',
            'link_end' => '</a>',
        ]
    );
    $message->setEscapeHtml(false);
    $messenger->addSuccess($message);

    $message = new PsrMessage(
        'Warning: It is important to check all site pages because the integration of the module features in Omeka S is a complex process and some features are not upgradable.' // @translate
    );
    $messenger->addError($message);
    $message = new PsrMessage(
        'In particular, check deprecated block templates and blocks Asset, Browse Preview and nested Division. Check styles too, because some html <div> and classes were added or removed by Omeka S and the module. Warning: as long as you do not re-save a page, old page settings will work. Once saved a new time, some old settings will be removed.' // @translate
    );
    $messenger->addError($message);
}

// This migration is integrated above in order to simplify messages to end user.
if (version_compare($oldVersion, '3.4.22-beta', '<')
    && $oldVersion === '3.4.22-alpha.2'
) {
    // Migrate blocks of this module to new blocks of Omeka S v4.1.

    $logger = $services->get('Omeka\Logger');

    $pageRepository = $entityManager->getRepository(\Omeka\Entity\SitePage::class);
    $blocksRepository = $entityManager->getRepository(\Omeka\Entity\SitePageBlock::class);

    // Separator: replace by block Line-break.
    foreach ($blocksRepository->findBy(['layout' => 'separator']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        $data = ['break_type' => 'transparent'];
        $layoutData['class'] = empty($layoutData['class']) ? 'separator' : trim($layoutData['class'] . ' separator');
        $block->setData($data);
        $block->setLayoutData($layoutData);
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    $message = new PsrMessage(
        'The block "Separator" was replaced by the Omeka block "Line Break" with block class "transparent" and layout class "separator".' // @translate
    );
    $messenger->addWarning($message);

    // Remigrate deprecated templates with new names.
    $blockLayoutTemplatesUpgraded = [
        'asset' => 'asset-deprecated-plus',
        'browsePreview' => 'browse-preview-plus',
        'itemWithMetadata' => 'item-with-metadata-plus',
        'listOfPages' => 'list-of-pages-plus',
        'listOfSites' => 'list-of-sites-plus',
        'itemShowcase' => 'media-item-showcase-plus',
        'itemShowCase' => 'media-item-showcase-plus',
        'fileItemShowcase' => 'media-item-showcase-plus',
        'tableOfContents' => 'table-of-contents-plus',
    ];
    $blockLayoutTemplatesRenamed = [
        'asset' => 'asset-deprecated-plus',
        'browsePreview' => 'browse-preview-deprecated',
        'itemWithMetadata' => 'item-with-metadata-deprecated',
        'listOfPages' => 'list-of-pages-deprecated',
        'listOfSites' => 'list-of-sites-deprecated',
        'itemShowcase' => 'media-item-showcase-deprecated',
        'itemShowCase' => 'media-item-showcase-deprecated',
        'fileItemShowcase' => 'media-item-showcase-deprecated',
        'tableOfContents' => 'table-of-contents-deprecated',
    ];

    $result = [];
    $blockNames = [];
    foreach ($blockLayoutTemplatesUpgraded as $layout => $templateName) {
        foreach ($blocksRepository->findBy(['layout' => $layout]) as $block) {
            $layoutData = $block->getLayoutData() ?? [];
            $existingTemplateName = $layoutData['template_name'] ?? null;
            if ($existingTemplateName === $templateName) {
                $page = $block->getPage();
                $pageSlug = $page->getSlug();
                $result[$page->getSite()->getSlug()][$pageSlug] = $pageSlug;
                $blockNames[$layout] = $layout;
                $layoutData['template_name'] = $blockLayoutTemplatesRenamed[$layout];
                $block->setLayoutData($layoutData);
            }
        }
        $entityManager->flush();
    }

    if ($result) {
        $result = array_map('array_values', $result);
        $message = new PsrMessage(
            'The template name of some of the blocks {list} where renamed for deprecation. Check your theme if you use them. Matching pages: {json}', // @translate
            ['list' => implode(', ', $blockNames), 'json' => json_encode($result, 448)]
        );
        $messenger->addWarning($message);
        $logger->warn($message->getMessage(), $message->getContext());
    }

    // Rename template as "browse-preview-deprecated" if no specific params are
    // used, else convert it into a block Search results with template "search-results-browse-preview-deprecated".
    // Blocks converted as Search results with a heading are prepended with this
    // heading below.
    // This process is copied above.
    $pagesConverted = [];
    $blockSearchResultsWithHeading = [];
    foreach ($blocksRepository->findBy(['layout' => 'browsePreview']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        $query = $data['query'] ?? '';
        $isQuerySiteOverridden = $query && preg_match('~site_page_id=\d+~', $query);
        $isPageOverridden = !empty($data['pagination']) || !empty($data['sort_headings']) || !empty($data['resource_template']);
        // Convert to block Search Results only when needed.
        if ($isQuerySiteOverridden || $isPageOverridden) {
            $page = $block->getPage();
            $pageSlug = $page->getSlug();
            $siteSlug = $page->getSite()->getSlug();
            $pagesConverted[$siteSlug][$pageSlug] = $pageSlug;
            $block->setLayout('searchResults');
            if (empty($layoutData['template_name'])
                || $layoutData['template_name'] === 'browse-preview'
                || $layoutData['template_name'] === 'browse-preview-plus'
                || $layoutData['template_name'] === 'browse-preview-deprecated'
            ) {
                $layoutData['template_name'] = 'search-results-browse-preview-deprecated';
            }
            // In search results, query is converted early.
            if (empty($data['query'])) {
                $data['query'] = [];
            } elseif (!is_array($data['query'])) {
                $query = [];
                parse_str(ltrim($data['query'], "? \t\n\r\0\x0B"), $query);
                $data['query'] = $query;
            }
            $resourceTypes = [
                'item' => 'items',
                'item-set' => 'item_sets',
                'media' => 'media',
            ];
            $data['resource_type'] = $resourceTypes[$data['resource_type'] ?? 'item'] ?? 'items';
            if (empty($data['heading'])) {
                unset($data['heading']);
            } else {
                $blockSearchResultsWithHeading[$block->getId()] = $page->getId();
            }
        } elseif (empty($layoutData['template_name'])
            || $layoutData['template_name'] === 'browse-preview'
            || $layoutData['template_name'] === 'browse-preview-plus'
        ) {
            $layoutData['template_name'] = 'browse-preview-deprecated';
        }
        // Clean all existing useless browse preview block settings.
        unset($data['pagination'], $data['sort_headings'], $data['resource_template']);
        $block->setData($data);
        $block->setLayoutData($layoutData);
    }

    $entityManager->flush();

    if ($pagesConverted) {
        $pagesConverted = array_map('array_values', $pagesConverted);
        $message = new PsrMessage(
            'The block "Browse Preview" filled with specific features (query with specific site, pagination and sort headings) were replaced by block Search results and template "search-results-browse-preview-deprecated": {json}', // @translate
            ['json' => json_encode($pagesConverted, 448)]
        );
        $messenger->addWarning($message);
        $logger->warn($message->getMessage(), $message->getContext());
    }

    // Prepend block Heading to block Search results migrated from block
    // Browse preview.
    if ($blockSearchResultsWithHeading) {
        $pagesWithHeading = [];
        $processedBlocksId = [];
        foreach (array_unique($blockSearchResultsWithHeading) as $pageId) {
            $page = $pageRepository->find($pageId);
            $pageSlug = $page->getSlug();
            $siteSlug = $page->getSite()->getSlug();
            $position = 0;
            foreach ($page->getBlocks() as $block) {
                $block->setPosition(++$position);
                $layout = $block->getLayout();
                if (!isset($blockSearchResultsWithHeading[$blockId])) {
                    continue;
                }
                $blockId = $block->getId();
                $data = $block->getData() ?: [];
                $heading = $data['heading'] ?? '';
                if (strlen($heading) && !isset($processedBlocksId[$blockId])) {
                    $b = new \Omeka\Entity\SitePageBlock();
                    $b->setLayout('heading');
                    $b->setPage($page);
                    $b->setPosition(++$position);
                    $b->setData([
                        'text' => $heading,
                        'level' => 2,
                    ]);
                    $entityManager->persist($b);
                    $block->setPosition(++$position);
                    $pagesWithHeading[$siteSlug][$pageSlug] = $pageSlug;
                    $processedBlocksId[$blockId] = $blockId;
                }
                unset($data['heading']);
                $block->setData($data);
            }
        }

        // Do a clear to fix issues with new blocks created during migration.
        $entityManager->flush();
        $entityManager->clear();

        if (!empty($pagesWithHeading)) {
            $pagesWithHeading = array_map('array_values', $pagesWithHeading);
            $message = new PsrMessage(
                'The setting "heading" was removed from blocks Search Results. A new block "Heading" was prepended to all blocks that had a filled heading. You may check pages for styles: {json}', // @translate
                ['json' => json_encode($pagesWithHeading, 448)]
            );
            $messenger->addWarning($message);
            $logger->warn($message->getMessage(), $message->getContext());
        }
    }
}

// The method to search files without a file is available only in Common 3.4.59
// that is not yet available.

/** @see \Common\ManageModuleAndResources::checkStringsInFiles() */
$checkStringsInFiles = function ($stringsOrRegex, string $globPath = '', bool $invert = false): ?array {
    if (!$stringsOrRegex) {
        return [];
    }

    // Forbid fake paths.
    if (strpos($globPath, '..') !== false || strpos($globPath, './') !== false) {
        return null;
    }

    $start = mb_strlen(OMEKA_PATH . '/');
    if (mb_substr($globPath, 0, 1) === '/') {
        if (strpos($globPath, $start) !== 0) {
            return null;
        }
    } else {
        $globPath = OMEKA_PATH . '/' . $globPath;
    }

    $result = [];

    $isStrings = is_array($stringsOrRegex);

    $paths = glob($globPath);
    foreach ($paths as $filepath) {
        if (!is_file($filepath) || !is_readable($filepath) || !filesize($filepath)) {
            continue;
        }
        $phtml = file_get_contents($filepath);
        if ($isStrings) {
            foreach ($stringsOrRegex as $check) {
                $pos = mb_strpos($phtml, $check);
                if ((!$invert && $pos) || ($invert && !$pos)) {
                    $result[] = mb_substr($filepath, $start);
                }
            }
        } else {
            $has = preg_match($phtml, $stringsOrRegex);
            if ((!$invert && $has) || ($invert && !$has)) {
                $result[] = mb_substr($filepath, $start);
            }
        }
    }

    return $result;
};

if (version_compare($oldVersion, '3.4.22-beta', '<')) {
    // Add warnings about files in themes to migrate.

    $logger = $services->get('Omeka\Logger');
    $pageRepository = $entityManager->getRepository(\Omeka\Entity\SitePage::class);
    $blocksRepository = $entityManager->getRepository(\Omeka\Entity\SitePageBlock::class);

    // Check all html layout/template without "$block->dataValue('html')"
    // because $html is no more a variable of the block Html.

    /** @see \Common\ManageModuleAndResources::checkStringsInFiles() */
    $checks = [
        '$block->dataValue(\'html\'',
        '$block->dataValue("html"',
    ];
    $result = $checkStringsInFiles($checks, 'themes/*/block-layout/html*', true) ?? [];
    $result = array_merge($result, $checkStringsInFiles($checks, 'themes/*/block-template/html*', true) ?? []);

    if ($result) {
        $message = new PsrMessage(
            'These templates for block "Html" should contain `$block->dataValue(\'html\', \'\')` because `$html` is no more available by default. Matching files: {json}', // @translate
            ['json' => json_encode($result, 448)]
        );
        $messenger->addError($message);
        $logger->warn($message->getMessage(), $message->getContext());
    }

    // Warn about block Browse preview with variable $site (without file check).
    $result = [];
    foreach ($blocksRepository->findBy(['layout' => 'browsePreview']) as $block) {
        $layoutData = $block->getLayoutData() ?? [];
        $templateName = $layoutData['template_name'] ?? null;
        if ($templateName && $templateName !== 'browse-preview') {
            $page = $block->getPage();
            $pageSlug = $page->getSlug();
            $result[$page->getSite()->getSlug()][$pageSlug] = $pageSlug;
        }
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    if ($result) {
        $result = array_map('array_values', $result);
        $message = new PsrMessage(
            'The block "Browse Preview" do not have the variable `$site` anymore. Check your theme if you customized it. Matching pages: {json}.', // @translate
            ['json' => json_encode($result, 448)]
        );
        $messenger->addWarning($message);
        $logger->warn($message->getMessage(), $message->getContext());
    }
}

if (version_compare($oldVersion, '3.4.22', '<')) {
    $logger = $services->get('Omeka\Logger');
    $pageRepository = $entityManager->getRepository(\Omeka\Entity\SitePage::class);
    $blocksRepository = $entityManager->getRepository(\Omeka\Entity\SitePageBlock::class);

    // Migrate custom block Asset to specific templates with view helper `captionClassAndUrl()`:
    // Some template names changed.
    // Variables $class and $url are still available via caption.
    $result = [];
    $blockLayoutTemplatesRenamed = [
        'asset-plus' => 'asset-deprecated-plus',
        'asset-hero-bootstrap' => 'asset-bootstrap-hero',
        'asset-deprecated-class-url' => 'asset-class-url',
        'asset-deprecated-left-right' => 'asset-left-right',
        'browser-preview-plus' => 'browser-preview-deprecated',
        'media-item-showcase-deprecated',
        'item-with-metadata-plus' => 'item-with-metadata-deprecated',
        'list-of-pages-plus' => 'list-of-pages-deprecated',
        'list-of-sites-plus' => 'list-of-sites-deprecated',
        'item-showcase-plus' => 'media-item-showcase-deprecated',
        'table-of-contents' => 'table-of-contents-deprecated',
    ];
    foreach ($blocksRepository->findAll() as $block) {
        $layoutData = $block->getLayoutData() ?? [];
        $templateName = $layoutData['template_name'] ?? null;
        if (!isset($blockLayoutTemplatesRenamed[$templateName])) {
            continue;
        }
        $layoutData['template_name'] = $blockLayoutTemplatesRenamed[$templateName];
        $block->setLayoutData($layoutData);
        $page = $block->getPage();
        $pageSlug = $page->getSlug();
        $result[$page->getSite()->getSlug()][$pageSlug] = $pageSlug;
    }

    if ($result) {
        $result = array_map('array_values', $result);
        $message = new PsrMessage(
            'The template layout of some blocks was renamed. Check your theme if you used them. Matching pages: {json}.', // @translate
            ['json' => json_encode($result, 448)]
        );
        $messenger->addWarning($message);
        $logger->warn($message->getMessage(), $message->getContext());
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    $result = [];
    foreach ($blocksRepository->findBy(['layout' => 'asset']) as $block) {
        $data = $block->getData() ?: [];
        $layoutData = $block->getLayoutData() ?? [];
        $templateName = $layoutData['template_name'] ?? null;
        if ($templateName && $templateName !== 'asset') {
            $page = $block->getPage();
            $pageSlug = $page->getSlug();

            // The upstream block doesn't contain key "attachments".
            $hasAttachments = array_key_exists('attachments', $data) && $data['attachments'];
            if ($hasAttachments) {
                // If there are attachments, all data are duplicated, so merge
                // attachments and data keys for security.
                $keys = [];
                foreach ($data as $key => $dataValue) {
                    if (is_numeric($key) && is_array($dataValue) && !empty($dataValue['id'])) {
                        $keys[$dataValue['id']] = $key;
                    }
                }
                foreach ($data['attachments'] as &$attachment) {
                    if (!empty($attachment['id']) && isset($keys[$attachment['id']])) {
                        $attachment = array_replace($data[$keys[$attachment['id']]], $attachment);
                    }
                }
                unset($attachment);
                $attachments = $data['attachments'];
            } else {
                $attachments = $data;
            }

            // Check if "class" and "url" exist for each asset.
            foreach ($attachments as $key => $dataValue) {
                if (!is_numeric($key)
                    || !is_array($dataValue)
                    || (!array_key_exists('class', $dataValue) && !array_key_exists('url', $dataValue))
                ) {
                    continue;
                }
                // Fix Windows and Apple copy/paste from a textarea input.
                $caption = $dataValue['caption'] ?? '';
                $caption = str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $caption);
                $isHtmlCaption = mb_substr($caption, 0, 1) === '<'
                    && mb_substr($caption, -1) === '>'
                    && $caption !== strip_tags($caption);
                $prepend = '';
                $hasUrl = !empty($dataValue['url']) && trim($dataValue['url']) !== '';
                $hasClass = isset($dataValue['class']) && trim($dataValue['class']) !== '';
                if ($hasUrl) {
                    $url = trim($dataValue['url']);
                    if (strlen($url)) {
                        $prepend = $isHtmlCaption
                            ? '<p>url = ' . $url . '</p>'
                            : 'url = ' . $url;
                    } else {
                        $hasUrl = false;
                    }
                }
                if ($hasClass) {
                    $class = trim($dataValue['class']);
                    if (strlen($class)) {
                        // Check if the class is an url (old storage or
                        // specific template).
                        if (!$hasUrl && mb_substr($class, 0, 1) === '/' || mb_substr($class, 0, 8) === 'https://' || mb_substr($class, 0, 7) === 'http://') {
                            $hasUrl = true;
                            $hasClass = false;
                            $prepend = $isHtmlCaption
                                ? '<p>url = ' . $class . '</p>'
                                : 'url = ' . $class;
                        } else {
                            $prepend = $prepend ? $prepend . "\n" : '';
                            $prepend .= $isHtmlCaption
                                ? '<p>class = ' . $class . '</p>'
                                : 'class = ' . $class;
                        }
                    } else {
                        $hasClass = false;
                    }
                }
                if ($prepend) {
                    $caption = strlen($caption) ? $prepend . "\n" . $caption : $prepend;
                    $result[$page->getSite()->getSlug()][$pageSlug] = $pageSlug;
                }
                unset($dataValue['attachments'], $dataValue['class'], $dataValue['url']);
                $dataValue['caption'] = $caption;
                $attachments[$key] = $dataValue;
            }
            $block->setData($attachments);
        }
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    if ($result) {
        $result = array_map('array_values', $result);
        $message = new PsrMessage(
            'The data of blocks "Asset" were migrated to manage asset class and url as the first two lines of the caption. Update your theme like asset templates. Matching pages: {json}.', // @translate
            ['json' => json_encode($result, 448)]
        );
        $messenger->addWarning($message);
        $logger->warn($message->getMessage(), $message->getContext());
    }
}

if (version_compare($oldVersion, '3.4.23', '<')) {
    /** @var \Laminas\Log\Logger $logger */
    $logger = $services->get('Omeka\Logger');

    /** @see \Omeka\Db\Migrations\MigrateBlockLayoutData */
    $pageRepository = $entityManager->getRepository(\Omeka\Entity\SitePage::class);
    $blocksRepository = $entityManager->getRepository(\Omeka\Entity\SitePageBlock::class);

    // Migrate templates for block Media.
    $result = [];
    $blockLayoutTemplatesRenamed = [
        'item-showcase' => 'media-item-showcase-deprecated',
        'item-showcase-plus' => 'media-item-showcase-deprecated',
        'resource-text' => 'media-resource-text-deprecated',
    ];
    foreach ($blocksRepository->findBy(['layout' => 'media']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        $template = $data['template'] ?? null;
        if ($template) {
            $templateName = pathinfo($template, PATHINFO_FILENAME);
        }
        $existingTemplateName = $layoutData['template_name'] ?? '';
        if (!$existingTemplateName && $templateName !== 'media') {
            $layoutData['template_name'] = isset($blockLayoutTemplatesRenamed[$templateName])
                ? $blockLayoutTemplatesRenamed[$templateName]
                : $templateName;
        }
        $block->setLayoutData($layoutData);
        unset($data['template']);
        $block->setData($data);
        $page = $block->getPage();
        $pageSlug = $page->getSlug();
        $result[$page->getSite()->getSlug()][$pageSlug] = $pageSlug;
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    /**
     * Replace filled setttings "heading" by a specific block "Heading" for Media.
     */
    $pagesWithHeading = [];
    $processedBlocksId = [];
    foreach ($pageRepository->findAll() as $page) {
        $pageId = $page->getId();
        $pageSlug = $page->getSlug();
        $siteSlug = $page->getSite()->getSlug();
        $position = 0;
        foreach ($page->getBlocks() as $block) {
            $block->setPosition(++$position);
            $layout = $block->getLayout();
            if ($layout !== 'media') {
                continue;
            }
            $blockId = $block->getId();
            $data = $block->getData() ?: [];
            $heading = $data['heading'] ?? '';
            $heading = trim($heading);
            if (strlen($heading) && !isset($processedBlocksId[$blockId])) {
                $b = new \Omeka\Entity\SitePageBlock();
                $b->setLayout('heading');
                $b->setPage($page);
                $b->setPosition(++$position);
                $b->setData([
                    'text' => $heading,
                    'level' => 2,
                ]);
                $entityManager->persist($b);
                $block->setPosition(++$position);
                $pagesWithHeading[$siteSlug][$pageSlug] = $pageSlug;
                $processedBlocksId[$blockId] = $blockId;
            }
            unset($data['heading']);
            $block->setData($data);
        }
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    if ($pagesWithHeading) {
        $pagesWithHeading = array_map('array_values', $pagesWithHeading);
        $message = new PsrMessage(
            'The setting "heading" was removed from block Media. A new block "Heading" was prepended to all blocks that had a filled heading. You may check pages for styles: {json}', // @translate
            ['json' => json_encode($pagesWithHeading, 448)]
        );
        $messenger->addWarning($message);
        $logger->warn($message->getMessage(), $message->getContext());
    }

    // Migrate block "ResourceText" to a group of block "Html" + "Media" (require Omeka S v4.1).
    // Migrate template for block Resource Text.
    $result = [];
    foreach ($blocksRepository->findBy(['layout' => 'resourceText']) as $block) {
        $data = $block->getData();
        $layoutData = $block->getLayoutData() ?? [];
        $template = $data['template'] ?? null;
        if ($template) {
            $templateName = pathinfo($template, PATHINFO_FILENAME);
        }
        $existingTemplateName = $layoutData['template_name'] ?? '';
        if (!$existingTemplateName) {
            $layoutData['template_name'] = $templateName === 'resource-text'
                ? 'media-resource-text-deprecated'
                : $templateName;
        }
        $block->setLayoutData($layoutData);
        unset($data['template']);
        $block->setData($data);
        $page = $block->getPage();
        $pageSlug = $page->getSlug();
        $result[$page->getSite()->getSlug()][$pageSlug] = $pageSlug;
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    /**
     * Prepend a specific block group, and append a block "Html" after Resource Text when filled.
     * Combine messages/logs.
     */
    $pagesWithHtml = $result;
    $processedBlocksId = [];
    foreach ($pageRepository->findAll() as $page) {
        $pageId = $page->getId();
        $pageSlug = $page->getSlug();
        $siteSlug = $page->getSite()->getSlug();
        $position = 0;
        $prevBlockGroup = null;
        foreach ($page->getBlocks() as $block) {
            $block->setPosition(++$position);
            $layout = $block->getLayout();
            if ($layout !== 'resourceText') {
                $prevBlockGroup = $layout === 'blockGroup' ? $block : null;
                continue;
            }
            $blockId = $block->getId();
            $data = $block->getData() ?: [];
            $html = $data['html'] ?? '';
            $hasHtml = !in_array(str_replace([' ', "\n", "\r", "\t"], '', $html), ['', '<div></div>', '<p></p>']);
            if ($hasHtml && !isset($processedBlocksId[$blockId])) {
                // Prepend a block group for two blocks.
                // Fix basic issue with block group, but not all. Anyway not a frequent block.
                // TODO Added a check for nested groups of blocks.
                if ($prevBlockGroup) {
                    $prevData = $prevBlockGroup->getData();
                    $prevSpan = $prevData['span'] ?? 0;
                    $prevBlockGroup->setData(['span' => ++$prevSpan] + $prevData);
                } else {
                    $bg = new \Omeka\Entity\SitePageBlock();
                    $bg->setLayout('blockGroup');
                    $bg->setPage($page);
                    $bg->setPosition(++$position);
                    $bg->setData([
                        'span' => 2,
                    ]);
                    $entityManager->persist($bg);
                }
            }
            if (!isset($processedBlocksId[$blockId])) {
                // Convert current block as media.
                // The options are the same than Media, except "caption_position"
                // (center, left or right), that is converted into a class
                // prepended with "caption-".
                $captionPosition = $data['caption_position'] ?? 'center';
                $currentLayoutDataClass = empty($layoutData['class']) ? '' : trim($layoutData['class']);
                $layoutData['class'] = $currentLayoutDataClass
                    ? "caption-$captionPosition"
                    : "$currentLayoutDataClass caption-$captionPosition";
                $block->setLayout('media');
                $block->setPosition(++$position);
            }
            if ($hasHtml && !isset($processedBlocksId[$blockId])) {
                $pagesWithHtml[$siteSlug][$pageSlug] = $pageSlug;
                $processedBlocksId[$blockId] = $blockId;
                // Append a block for html.
                $b = new \Omeka\Entity\SitePageBlock();
                $b->setLayout('html');
                $b->setPage($page);
                $b->setPosition(++$position);
                $b->setData([
                    'html' => $html,
                ]);
                $entityManager->persist($b);
            }
            unset($data['html']);
            $block->setData($data);
            $prevBlock = null;
            $prevBlockLayout = null;
        }
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    if (!empty($pagesWithHtml)) {
        $result = array_map('array_values', $pagesWithHtml);
        $message = new PsrMessage(
            'The block Resource Text has been converted to a group of blocks Media and Html. The option "caption position" was moved to layout class. You may check pages for styles: {json}', // @translate
            ['json' => json_encode($result, 448)]
        );
        $messenger->addWarning($message);
        $logger->warn($message->getMessage(), $message->getContext());
    }

    // Check themes that use "$html" and "$captionPosition" in block Media.
    $strings = [
        '$html',
        '$captionPosition',
    ];
    $manageModuleAndResources = $this->getManageModuleAndResources();
    $result = $manageModuleAndResources->checkStringsInFiles($strings, 'themes/*/view/common/block-layout/media-*');
    if ($result) {
        $message = new PsrMessage(
            'The variables "$html" and "$captionPosition" were removed from block Resource Text (converted into block Media). You should fix them: {json}', // @translate
            ['json' => json_encode($result, 448)]
        );
        $messenger->addWarning($message);
        $logger->warn($message->getMessage(), $message->getContext());
    }
}
