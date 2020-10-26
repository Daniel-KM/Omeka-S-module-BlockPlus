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
     * @param ContainerInterface $serviceLocator
     * @return ResourceText
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $htmlPurifier = $serviceLocator->get('Omeka\HtmlPurifier');
        return new ResourceText($htmlPurifier);
    }
}
