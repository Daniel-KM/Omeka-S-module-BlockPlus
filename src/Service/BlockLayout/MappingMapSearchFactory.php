<?php declare(strict_types=1);

namespace BlockPlus\Service\BlockLayout;

use BlockPlus\Site\BlockLayout\MappingMapSearch;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\Module\Manager as ModuleManager;
use Omeka\Site\BlockLayout\Fallback;

class MappingMapSearchFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        /** @var \Omeka\Module\Manager $moduleManager */
        $moduleManager = $services->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule('Mapping');
        if (!$module
            || !$module->getState() === ModuleManager::STATE_ACTIVE
        ) {
            return new Fallback($requestedName);
        }

        return new MappingMapSearch(
            $services->get('Omeka\HtmlPurifier'),
            $moduleManager
        );
    }
}
