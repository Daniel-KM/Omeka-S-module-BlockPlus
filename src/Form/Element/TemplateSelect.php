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
    protected function filteredFilesInFolder($dir, $layout = '', $extension = ''): array
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';

        if ($isWindows) {
            $dir = str_replace('\\', '/', $dir);
            $base = rtrim($dir, '/');
            if (!$base) {
                return [];
            }
            $layout = str_replace('\\', '/', $layout);
            $layout = ltrim($layout, '/');
        } else {
            $base = rtrim($dir, '\\/') ?: '/';
            $layout = ltrim($layout, '\\/');
        }

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

        $directory = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);

        // RegexIterator has an issue on Windows, so skip it.
        if ($isWindows) {
            foreach ($iterator as $filepath => $file) {
                if (!$file || !preg_match($regex, $filepath)) {
                    continue;
                }
                $file = $filepath;
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                if (mb_strlen((string) $extension)) {
                    $file = mb_substr($file, 0, -1 - mb_strlen($extension));
                }
                $fileLayout = mb_substr($file, mb_strlen($base) + 1);
                $files[$fileLayout] = mb_substr($fileLayout, mb_strlen($dirLayout));
            }
            natcasesort($files);

            return $files;
        }

        $regexIterator = new \RegexIterator($iterator, $regex, \RecursiveRegexIterator::GET_MATCH);
        foreach ($regexIterator as $file) {
            $fileLayout = reset($file);
            $extension = pathinfo($fileLayout, PATHINFO_EXTENSION);
            if (mb_strlen($extension)) {
                $fileLayout = mb_substr($fileLayout, 0, -1 - mb_strlen($extension));
            }
            $files[$fileLayout] = mb_substr($fileLayout, mb_strlen($dirLayout));
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
