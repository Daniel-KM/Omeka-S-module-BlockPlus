<?php declare(strict_types=1);

namespace BlockPlus\Service\Form\Element;

use BlockPlus\Form\Element\PageModelSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PageModelSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $pageModels = $services->get('ControllerPluginManager')->get('pageModels');

        $element = new PageModelSelect(null, $options ?? []);
        return $element
            ->setValueOptions($pageModels())
            ->setEmptyOption('Select a page model…'); // @translate
    }
}
