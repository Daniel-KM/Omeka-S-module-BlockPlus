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

        $pageModels = $this->getPageModels($services);

        $form = new SitePageForm(null, $options ?? []);
        $form
            ->setPageModels($pageModels)
            ->setCurrentTheme($theme);
        return $form;
    }

    /**
     * Copied:
     * @see \BlockPlus\Module::getPageModels()
     * @see \BlockPlus\Service\Form\SitePageFormFactory::getPageModels()
     */
    protected function getPageModels($services): array
    {
        /**
         * @var array $config
         * @var \Omeka\Settings\Settings $settings
         * @var \Omeka\Settings\SiteSettings $siteSettings
         * @var \Omeka\Site\Theme\Manager $themeManager
         */
        // $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');
        $siteSettings = $services->get('Omeka\Settings\Site');
        $themeManager = $services->get('Omeka\Site\ThemeManager');

        $theme = $themeManager->getCurrentTheme();
        $themeConfig = $theme->getConfigSpec();
        $themeSettings = $siteSettings->get($theme->getSettingsKey(), []);

        $result = array_merge(
            $config['page_models'] ?? [],
            $settings->get('blockplus_page_models', []),
            $siteSettings->get('blockplus_page_models', []),
            $themeConfig['page_models'] ?? [],
            $themeSettings['page_models'] ?? []
        );

        // TODO Keep main/site/theme order? Use nested select? Add an icon in the list?
        uasort($result, fn ($a, $b) => strcasecmp($a['o:label'] ?? '', $b['o:label'] ?? ''));

        return $result;
    }
}
