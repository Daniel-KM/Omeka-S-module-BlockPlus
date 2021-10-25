<?php declare(strict_types=1);

namespace BlockPlus\Service\BlockLayout;

use BlockPlus\Site\BlockLayout\Showcase;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ShowcaseFactory implements FactoryInterface
{
    /**
     * Create the Showcase block layout service.
     * @return Showcase
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Showcase(
            $services->get('Omeka\ApiManager'),
            $services->get('Omeka\HtmlPurifier')
        );
    }
}
