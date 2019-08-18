<?php
namespace BlockPlus;

use Omeka\Module\AbstractModule;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * BlockPlus
 *
 * @copyright Daniel Berthereau, 2018-2019
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $serviceLocator)
    {
        $filepath = __DIR__ . '/data/scripts/upgrade.php';
        $this->setServiceLocator($serviceLocator);
        require_once $filepath;
    }
}
