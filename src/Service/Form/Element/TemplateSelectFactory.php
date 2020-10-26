<?php
namespace BlockPlus\Service\Form\Element;

use BlockPlus\Form\Element\TemplateSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class TemplateSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new TemplateSelect(null, $options);
        $element->setTemplatePathStack($services->get('Config')['view_manager']['template_path_stack']);
        $currentSite = $services->get('ControllerPluginManager')->get('currentSite');
        $currentSite = $currentSite();
        if ($currentSite) {
            $element->setTheme($currentSite->theme());
        }
        return $element;
    }
}
