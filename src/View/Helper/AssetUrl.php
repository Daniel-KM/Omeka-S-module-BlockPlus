<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

/**
 * View helper for returning a path to an asset.
 *
 * Override core helper to allow to override internal assets in a generic way.
 *
 * @see \AdvancedResourceTemplate\Service\ViewHelper\AssetUrlFactory
 * @see \AdvancedSearch\Service\ViewHelper\AssetUrlFactory
 * @see \BlockPlus\Service\ViewHelper\AssetUrlFactory
 */
class AssetUrl extends \Omeka\View\Helper\AssetUrl
{
    /**
     * @var array Array of all internals overrides to use for asset URLs
     */
    protected $internals;

    public function __construct($currentTheme, $modules, $externals, $internals)
    {
        $this->currentTheme = $currentTheme;
        $this->activeModules = $modules;
        $this->externals = $externals;
        $this->internals = $internals;
    }

    public function __invoke($file, $module = null, $override = false, $versioned = true)
    {
        if ($module === 'Omeka'
            && isset($this->internals[$file])
            && array_key_exists($this->internals[$file], $this->activeModules)
        ) {
            return sprintf(
                self::MODULE_ASSETS_PATH,
                $this->getView()->basePath(),
                $this->internals[$file],
                $file,
                $versioned ? '?v=' . $this->activeModules[$this->internals[$file]]->getIni('version') : ''
            );
        }

        return parent::__invoke($file, $module, $override, $versioned);
    }
}
