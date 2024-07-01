<?php declare(strict_types=1);

namespace BlockPlus\Service\BlockLayout;

use BlockPlus\Site\BlockLayout\ExternalContent;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ExternalContentFactory implements FactoryInterface
{
    /**
     * Create the ExternalContent block layout service.
     *
     * @param ContainerInterface $services
     * @return ExternalContent
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ExternalContent(
            $services->get('Config')['oembed']['whitelist'],
            $services->get('Omeka\HttpClient'),
            $services->get('Omeka\File\Downloader')
        );
    }
}
