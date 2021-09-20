<?php declare(strict_types=1);

namespace BlockPlus;

use Omeka\Stdlib\Message;
use Omeka\Mvc\Controller\Plugin\Messenger;

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
// $settings = $services->get('Omeka\Settings');
// $config = require dirname(dirname(__DIR__)) . '/config/module.config.php';
$connection = $services->get('Omeka\Connection');
// $entityManager = $services->get('Omeka\EntityManager');
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
// $space = strtolower(__NAMESPACE__);

if (version_compare($oldVersion, '3.0.3', '<')) {
    $sql = <<<'SQL'
UPDATE site_page_block
SET
    layout = "resourceText",
    data = REPLACE(data, '"partial":"common\\/block-layout\\/media-text', '"partial":"common\\/block-layout\\/resource-text')
WHERE layout = "mediaText";
SQL;
    $connection->exec($sql);
}

if (version_compare($oldVersion, '3.0.5', '<')) {
    $sql = <<<'SQL'
UPDATE site_page_block
SET
    data = REPLACE(data, '"partial":"', '"template":"')
WHERE
    layout IN ('block', 'browsePreview', 'column', 'itemShowCase', 'itemWithMetadata', 'listOfSites', 'pageTitle', 'searchForm', 'separator', 'tableOfContents', 'assets', 'embedText', 'html', 'resourceText', 'simplePage');
SQL;
    $connection->exec($sql);
}

if (version_compare($oldVersion, '3.3.11.3', '<')) {
    $sql = <<<'SQL'
UPDATE site_page_block
SET layout = "mirrorPage"
WHERE layout = "simplePage";
SQL;
    $connection->exec($sql);
    $sql = <<<'SQL'
UPDATE site_page_block
SET layout = "externalContent"
WHERE layout = "embedText";
SQL;
    $connection->exec($sql);
    $sql = <<<'SQL'
UPDATE site_page_block
SET data = REPLACE(data, "/embed-text", "/external-content")
WHERE layout = "externalContent";
SQL;
    $connection->exec($sql);
}

if (version_compare($oldVersion, '3.3.11.4', '<')) {
    $sql = <<<'SQL'
UPDATE site_page_block
SET layout = "division"
WHERE layout = "column";
SQL;
    $connection->exec($sql);
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
    $connection->exec($sql);
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
            $connection->exec($sql);
        }
    }
}

if (version_compare($oldVersion, '3.3.13.0', '<')) {
    require_once __DIR__ . '/upgrade_vocabulary.php';
}
