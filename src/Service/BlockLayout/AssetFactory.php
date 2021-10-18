<?php declare(strict_types=1);

namespace BlockPlus\Service\BlockLayout;

use BlockPlus\Site\BlockLayout\Asset;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AssetFactory implements FactoryInterface
{
    /**
     * Create the Asset block layout service.
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Asset(
            $services->get('Omeka\HtmlPurifier')
        );
    }
}
