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
            || $module->getState() !== ModuleManager::STATE_ACTIVE
        ) {
            return new Fallback($requestedName);
        }

        // Until version 2.0.
        if (!method_exists(\BlockPlus\Site\BlockLayout\MappingMapSearch::class, 'setFormElementManager')) {
            return new MappingMapSearch(
                $services->get('Omeka\HtmlPurifier'),
                $moduleManager
            );
        }

        // Since version 2.1.
        $blockLayout = new MappingMapSearch();
        $blockLayout->setModuleManager($services->get('Omeka\ModuleManager'));
        $blockLayout->setFormElementManager($services->get('FormElementManager'));
        $blockLayout->setApiManager($services->get('Omeka\ApiManager'));
        return $blockLayout;
    }
}
