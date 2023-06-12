<?php declare(strict_types=1);

namespace BlockPlus\Service\BlockLayout;

use BlockPlus\Site\BlockLayout\BrowsePreview;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class BrowsePreviewFactory implements FactoryInterface
{
    /**
     * Create the Html block layout service.
     *
     * @param ContainerInterface $serviceLocator
     * @return BrowsePreview
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new BrowsePreview(
            $services->get('Omeka\HtmlPurifier')
        );
    }
}
