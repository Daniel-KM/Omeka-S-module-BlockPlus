<?php
namespace BlockPlus\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Zend\Form\Form;

/**
 * Trait to fill value options of the element "partial" in order to manage
 * multiple display for blocks.
 */
trait FillPartialValueOptionsTrait
{
    /**
     * Fill the element partial of a form.
     *
     * @param Form $form
     * @param string $root
     * @param SiteRepresentation $site
     */
    protected function fillPartialValueOptions(Form $form, $root, SiteRepresentation $site)
    {
        $partials = $this->findPartials($root, $site);
        $form
            ->get('o:block[__blockIndex__][o:data][partial]')
            ->setValueOptions($partials);
    }

    /**
     * Find all partials whose filename starts with a string.
     *
     * @param string $root
     * @param SiteRepresentation $site
     * @return array
     */
    protected function findPartials($root, SiteRepresentation $site)
    {
        // Hacky way to get all filenames for the asset. Theme first, then
        // modules, then core.
        $partials = [$root => 'Default']; // @translate

        // Check filenames in core.
        $directory = OMEKA_PATH . '/application/view/';
        // Check filenames in modules.
        $recursiveList = $this->filteredFilesInFolder($directory, $root, ['phtml']);
        $partials += $recursiveList;

        // Check filenames in modules.
        $services = $site->getServiceLocator();
        $templatePathStack = $services->get('Config')['view_manager']['template_path_stack'];
        foreach ($templatePathStack as $directory) {
            $recursiveList = $this->filteredFilesInFolder($directory, $root, ['phtml']);
            $partials += $recursiveList;
        }

        // Check filenames in the theme.
        $directory = OMEKA_PATH . '/themes/' . $site->theme() . '/view/';
        $recursiveList = $this->filteredFilesInFolder($directory, $root, ['phtml']);
        $partials += $recursiveList;

        return $partials;
    }

    /**
     * Get files filtered by a root and extensions recursively in a directory.
     *
     * @param string $dir
     * @param string $root Directory or beginning of a file without extension.
     * @param array $extensions
     * @return array Files are returned without extensions.
     */
    protected function filteredFilesInFolder($dir, $root = '', array $extensions = [])
    {
        $base = rtrim($dir, '\\/') ?: '/';
        $root = ltrim($root, '\\/');

        $isRootDir = $root === '' || substr($root, -1) === '/';
        $dir = $isRootDir
            ? $base . '/' . $root
            : dirname($base . '/' . $root);
        if (empty($dir) || !file_exists($dir) || !is_dir($dir) || !is_readable($dir)) {
            return [];
        }

        // The files are saved from the base.
        $files = [];
        $dirRoot = $isRootDir
            ? $root
            : (dirname($root) ? dirname($root) . '/' : '');
        $regex = '~' . preg_quote(pathinfo($root, PATHINFO_FILENAME), '~')
            . '.*'
            . ($extensions ? '\.(?:' . implode('|', $extensions) . ')' : '')
            . '$~';
        $Directory = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $RegexIterator = new \RegexIterator($Iterator, $regex, \RecursiveRegexIterator::GET_MATCH);
        foreach ($RegexIterator as $file) {
            $file = reset($file);
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (strlen($extension)) {
                $file = substr($file, 0, -1 - strlen($extension));
            }
            $files[$dirRoot . $file] = $file;
        }
        natcasesort($files);

        return $files;
    }
}
