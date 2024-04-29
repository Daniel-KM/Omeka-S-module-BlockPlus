<?php declare(strict_types=1);

namespace BlockPlus\Service\BlockLayout;

use BlockPlus\Site\BlockLayout\ResourceText;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ResourceTextFactory implements FactoryInterface
{
    /**
     * Create the ResourceText block layout service.
     *
     * @param ContainerInterface $services
     * @return ResourceText
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceText(
            $services->get('Omeka\HtmlPurifier')
        );
    }
}
