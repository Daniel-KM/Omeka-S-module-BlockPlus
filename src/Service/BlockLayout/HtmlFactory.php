<?php declare(strict_types=1);
namespace BlockPlus\Service\BlockLayout;

use BlockPlus\Site\BlockLayout\Html;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class HtmlFactory implements FactoryInterface
{
    /**
     * Create the Html block layout service.
     *
     * @param ContainerInterface $serviceLocator
     * @return Html
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $htmlPurifier = $serviceLocator->get('Omeka\HtmlPurifier');
        return new Html($htmlPurifier);
    }
}
