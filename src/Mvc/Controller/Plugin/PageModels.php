<?php declare(strict_types=1);

namespace BlockPlus\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Omeka\Site\Theme\Manager as ThemeManager;
use Omeka\Settings\Settings;
use Omeka\Settings\SiteSettings;

/**
 * Get all page models.
 */
class PageModels extends AbstractPlugin
{
    /**
     * @var \Omeka\Site\Theme\Theme
     */
    protected $currentTheme;

    /**
     * @var array $config
     */
    protected $config;

    /**
     * @var \Omeka\Settings\Settings $settings
     */
    protected $settings;

    /**
     * @var \Omeka\Settings\SiteSettings
     */
    protected $siteSettings;

    /**
     * @var \Omeka\Site\Theme\Manager
     */
    protected $themeManager;

    public function __construct(
        array $config,
        Settings $settings,
        SiteSettings $siteSettings,
        ThemeManager $themeManager
    ) {
        $this->config = $config;
        $this->settings = $settings;
        $this->siteSettings = $siteSettings;
        $this->themeManager = $themeManager;
    }

    /**
     * Get the list of page models of the current site.
     */
    public function __invoke(): array
    {
        $theme = $this->themeManager->getCurrentTheme();
        $themeConfig = $theme->getConfigSpec();
        $themeSettings = $this->siteSettings->get($theme->getSettingsKey(), []);

        $configPageModels = $this->config['page_models'] ?? [];
        if ($configPageModels && $this->siteSettings->get('blockplus_page_model_skip_blockplus')) {
            unset(
                $configPageModels['home_page'],
                $configPageModels['exhibit'],
                $configPageModels['exhibit_page'],
                $configPageModels['simple_page'],
                $configPageModels['resource_text']
            );
        }

        $result = array_merge(
            $configPageModels,
            $this->settings->get('blockplus_page_models', []),
            $this->siteSettings->get('blockplus_page_models', []),
            $themeConfig['page_models'] ?? [],
            $themeSettings['page_models'] ?? []
        );

        // TODO Keep main/site/theme order? Use nested select? Add an icon in the list?
        uasort($result, fn ($a, $b) => strcasecmp($a['o:label'] ?? '', $b['o:label'] ?? ''));

        return $result;
    }
}
