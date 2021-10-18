<?php declare(strict_types=1);

namespace BlockPlus\Service\BlockLayout;

use BlockPlus\Site\BlockLayout\ListOfPages;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ListOfPagesFactory implements FactoryInterface
{
    /**
     * Create the listOfPages block layout service.
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ListOfPages(
            $services->get('Omeka\Site\NavigationLinkManager'),
            $services->get('Omeka\Site\NavigationTranslator')
        );
    }
}
