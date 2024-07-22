<?php declare(strict_types=1);

namespace BlockPlus\Service\Form;

use BlockPlus\Form\SitePageForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SitePageFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        /**
         * @var \Omeka\Site\Theme\Theme $theme
         */
        $theme = $services->get('Omeka\Site\ThemeManager')->getCurrentTheme();
        $themeConfig = $theme->getConfigSpec();

        $pageTemplates = isset($themeConfig['page_templates']) && is_array($themeConfig['page_templates'])
            ? $themeConfig['page_templates']
            : [];

        $form = new SitePageForm(null, $options ?? []);
        $form
            ->setPageTemplates($pageTemplates)
            ->setCurrentTheme($theme);
        return $form;
    }
}
