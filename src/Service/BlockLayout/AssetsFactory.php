<?php
namespace BlockPlus\Service\BlockLayout;

use BlockPlus\Site\BlockLayout\Assets;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AssetsFactory implements FactoryInterface
{
    /**
     * Create the Assets block layout service.
     *
     * @param ContainerInterface $serviceLocator
     * @return Assets
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Assets(
            $services->get('Omeka\HtmlPurifier')
        );
    }
}
