<?php declare(strict_types=1);
namespace BlockPlus;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Api\Manager $api
 */
$services = $serviceLocator;
// $settings = $services->get('Omeka\Settings');
// $config = require dirname(dirname(__DIR__)) . '/config/module.config.php';
$connection = $services->get('Omeka\Connection');
// $entityManager = $services->get('Omeka\EntityManager');
// $plugins = $services->get('ControllerPluginManager');
// $api = $plugins->get('api');
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
