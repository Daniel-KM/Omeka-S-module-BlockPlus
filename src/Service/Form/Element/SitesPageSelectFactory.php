<?php
namespace BlockPlus\Service\Form\Element;

use BlockPlus\Form\Element\SitesPageSelect;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class SitesPageSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $currentSite = $services->get('ControllerPluginManager')->get('currentSite');

        $element = new SitesPageSelect(null, $options);
        $element->setApiManager($services->get('Omeka\ApiManager'));
        $element->setSite($currentSite());
        return $element;
    }
}
