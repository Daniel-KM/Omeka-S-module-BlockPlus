<?php declare(strict_types=1);

namespace BlockPlus\Service\Form\Element;

use BlockPlus\Form\Element\ThumbnailTypeSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ThumbnailTypeSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $types = $services->get(\Omeka\File\ThumbnailManager::class)->getTypes();
        $element = new ThumbnailTypeSelect;
        return $element
            ->setValueOptions(array_combine($types, $types));
    }
}
