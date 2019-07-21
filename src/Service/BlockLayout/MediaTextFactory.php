<?php
namespace BlockPlus\Service\BlockLayout;

use BlockPlus\Site\BlockLayout\MediaText;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class MediaTextFactory implements FactoryInterface
{
    /**
     * Create the MediaText block layout service.
     *
     * @param ContainerInterface $serviceLocator
     * @return MediaText
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $htmlPurifier = $serviceLocator->get('Omeka\HtmlPurifier');
        return new MediaText($htmlPurifier);
    }
}
