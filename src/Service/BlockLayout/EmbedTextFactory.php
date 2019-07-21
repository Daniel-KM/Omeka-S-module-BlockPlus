<?php
namespace BlockPlus\Service\BlockLayout;

use BlockPlus\Site\BlockLayout\EmbedText;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class EmbedTextFactory implements FactoryInterface
{
    /**
     * Create the EmbedText block layout service.
     *
     * @param ContainerInterface $serviceLocator
     * @return EmbedText
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new EmbedText(
            $services->get('Omeka\HtmlPurifier'),
            $services->get('Config')['oembed']['whitelist'],
            $services->get('Omeka\HttpClient'),
            $services->get('Omeka\File\Downloader')
        );
    }
}
