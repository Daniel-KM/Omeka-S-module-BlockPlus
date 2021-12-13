<?php declare(strict_types=1);

namespace BlockPlus\Service\ViewHelper;

use BlockPlus\View\Helper\AssetUrl;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\Module\Manager as ModuleManager;

/**
 * Service factory for the assetUrl view helper.
 */
class AssetUrlFactory implements FactoryInterface
{
    /**
     * Create and return the assetUrl view helper
     *
     * @return AssetUrl
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $assetConfig = $services->get('Config')['assets'];
        return new AssetUrl(
            $services->get('Omeka\Site\ThemeManager')->getCurrentTheme(),
            $services->get('Omeka\ModuleManager')->getModulesByState(ModuleManager::STATE_ACTIVE),
            $assetConfig['use_externals'] ? $assetConfig['externals'] : [],
            $assetConfig['internals'] ?? []
        );
    }
}
