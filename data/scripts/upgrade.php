<?php
namespace BlockPlus;

/**
 * @var Module $this
 * @var \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
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

if (version_compare($oldVersion, '3.0.10', '<')) {
    $sql = <<<'SQL'
UPDATE site_page_block
SET layout = "mirrorPage"
WHERE layout = "simplePage";
SQL;
    $connection->exec($sql);
    $sql = <<<'SQL'
UPDATE site_page_block
SET layout = "embedText"
WHERE layout = "externalContent";
SQL;
    $connection->exec($sql);
}
