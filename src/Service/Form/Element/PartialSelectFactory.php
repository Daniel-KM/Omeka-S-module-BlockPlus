<?php
namespace BlockPlus\Service\Form\Element;

use BlockPlus\Form\Element\PartialSelect;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class PartialSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new PartialSelect(null, $options);
        $element->setTemplatePathStack($services->get('Config')['view_manager']['template_path_stack']);
        $currentSite = $services->get('ControllerPluginManager')->get('currentSite');
        $currentSite = $currentSite();
        if ($currentSite) {
            $element->setTheme($currentSite->theme());
        }
        return $element;
    }
}
