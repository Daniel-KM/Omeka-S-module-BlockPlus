<?php declare(strict_types=1);

namespace BlockPlus\Service\Form\Element;

use BlockPlus\Form\Element\PageModelSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PageModelSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $currentTheme = $services->get('Omeka\Site\ThemeManager')->getCurrentTheme();
        $config = $currentTheme->getConfigSpec();
        $pageTemplates = $config['page_templates'] ?: [];

        $pageModels = $services->get('ControllerPluginManager')->get('pageModels');
        $pageModels = $pageModels();

        $pageModels += array_map(fn ($v) => ['label' => $v, 'is_page_template' => true], $pageTemplates);

        $element = new PageModelSelect(null, $options ?? []);
        return $element
            ->setValueOptions($pageModels)
            ->setEmptyOption('Select a page model…'); // @translate
    }
}
