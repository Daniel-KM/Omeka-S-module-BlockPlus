<?php declare(strict_types=1);

namespace BlockPlus\Service\Form\Element;

use BlockPlus\Form\Element\OptionalPropertySelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OptionalPropertySelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new OptionalPropertySelect(null, $options ?? []);
        $element->setEventManager($services->get('EventManager'));
        $element->setApiManager($services->get('Omeka\ApiManager'));
        $element->setTranslator($services->get('MvcTranslator'));
        return $element;
    }
}
