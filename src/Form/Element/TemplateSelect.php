<?php declare(strict_types=1);

namespace BlockPlus\Form\Element;

use Laminas\Form\Element\Select;

class TemplateSelect extends Select
{
    protected $templatePathStack = [];
    protected $theme = '';

    public function setOptions($options)
    {
        if (!empty($options['template'])) {
            $options['value_options'] = $this->findTemplates($options['template']);
        }

        return parent::setOptions($options);
    }

    /**
     * Find all partial templates whose filename starts with a string.
     *
     * @param string $layout
     * @return array
     */
    protected function findTemplates($layout)
    {
        // Hacky way to get all filenames for the asset. Theme first, then
        // modules, then core.
        $templates = [$layout => 'Default']; // @translate

        // Check filenames in core.
        $directory = OMEKA_PATH . '/application/view/';
        // Check filenames in modules.
        $recursiveList = $this->filteredFilesInFolder($directory, $layout, 'phtml');
        $templates += $recursiveList;

        // Check filenames in modules.
        foreach ($this->templatePathStack as $directory) {
            $recursiveList = $this->filteredFilesInFolder($directory, $layout, 'phtml');
            $templates += $recursiveList;
        }

        // Check filenames in the theme.
        if (mb_strlen($this->theme)) {
            $directory = OMEKA_PATH . '/themes/' . $this->theme . '/view/';
            $recursiveList = $this->filteredFilesInFolder($directory, $layout, 'phtml');
            $templates += $recursiveList;
        }

        return $templates;
    }

    /**
     * Get files filtered by a path and extensions recursively in a directory.
     *
     * @param string $dir
     * @param string $layout Subdirectory or start of a file without extension.
     * @param string $extension
     * @return array Files are returned without extensions.
     */
    protected function filteredFilesInFolder($dir, $layout = '', $extension = '')
    {
        $base = rtrim($dir, '\\/') ?: '/';
        $layout = ltrim($layout, '\\/');

        $isLayoutDir = $layout === '' || mb_substr($layout, -1) === '/';
        $dir = $isLayoutDir
            ? $base . '/' . $layout
            : dirname($base . '/' . $layout);
        if (empty($dir) || !file_exists($dir) || !is_dir($dir) || !is_readable($dir)) {
            return [];
        }

        // The files are saved from the base.
        $files = [];
        $dirLayout = $isLayoutDir
            ? $layout
            : (dirname($layout) ? dirname($layout) . '/' : '');
        $regex = '~' . preg_quote($layout, '~')
            . '.*'
            . ($extension ? '\.' . preg_quote($extension, '~') : '')
            . '$~';

        $Directory = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $RegexIterator = new \RegexIterator($Iterator, $regex, \RecursiveRegexIterator::GET_MATCH);
        foreach ($RegexIterator as $file) {
            $file = reset($file);
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (mb_strlen($extension)) {
                $file = mb_substr($file, 0, -1 - mb_strlen($extension));
            }
            $files[$file] = mb_substr($file, mb_strlen($dirLayout));
        }
        natcasesort($files);

        return $files;
    }

    /**
     * @param array $templatePathStack
     * @return \BlockPlus\Form\Element\TemplateSelect
     */
    public function setTemplatePathStack(array $templatePathStack)
    {
        $this->templatePathStack = $templatePathStack;
        return $this;
    }

    /**
     * @param string $theme
     * @return \BlockPlus\Form\Element\TemplateSelect
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
        return $this;
    }
}
