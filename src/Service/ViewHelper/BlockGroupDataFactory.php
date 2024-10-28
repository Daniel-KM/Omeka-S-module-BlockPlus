<?php declare(strict_types=1);

namespace BlockPlus\Service\ViewHelper;

use BlockPlus\View\Helper\BlockGroupData;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class BlockGroupDataFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new BlockGroupData(
            $services->get('Omeka\EntityManager')
        );
    }
}
