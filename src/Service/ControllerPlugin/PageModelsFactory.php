<?php declare(strict_types=1);

namespace BlockPlus\Service\ControllerPlugin;

use BlockPlus\Mvc\Controller\Plugin\PageModels;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PageModelsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new PageModels(
            $services->get('Config'),
            $services->get('Omeka\Settings'),
            $services->get('Omeka\Settings\Site'),
            $services->get('Omeka\Site\ThemeManager')
        );
    }
}
