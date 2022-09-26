<?php declare(strict_types=1);

namespace BlockPlus;

use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Api $api
 */
$services = $serviceLocator;
$settings = $services->get('Omeka\Settings');
// $config = require dirname(dirname(__DIR__)) . '/config/module.config.php';
$connection = $services->get('Omeka\Connection');
// $entityManager = $services->get('Omeka\EntityManager');
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');

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
    $messenger = new Messenger();
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

    $messenger = new Messenger();
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
    $messenger = new Messenger();
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

    $messenger = new Messenger();
    $message = new Message(
        'It’s now possible to choose mode of display to edit html blocks of pages in main params.' // @translate
    );
    $messenger->addSuccess($message);
}
