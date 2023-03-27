<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * Identical helper available:
 * @see \BlockPlus\View\Helper\IsHomePage
 * @see \Menu\View\Helper\IsHomePage
 */
class IsHomePage extends AbstractHelper
{
    /**
     * Check if the current page is the home page (first page in main menu).
     *
     * @return bool
     */
    public function __invoke(): bool
    {
        $view = $this->getView();

        $site = $view->currentSite();
        if (empty($site)) {
            return false;
        }

        // Check the alias of the root of Omeka S with rerouting.
        if ($this->isCurrentUrl($view->basePath())) {
            return true;
        }

        // Since 1.4, there is a site setting for home page.
        $homepage = $site->homepage();
        if ($homepage) {
            $params = $view->params()->fromRoute();
            return isset($params['__CONTROLLER__'])
                && $params['__CONTROLLER__'] === 'Page'
                && $homepage->id() === $view->api()
                ->read('site_pages', ['site' => $site->id(), 'slug' => $params['page-slug']])
                ->getContent()->id();
        }

        // Check the first normal pages.
        $linkedPages = $site->linkedPages();
        if ($linkedPages) {
            $firstPage = current($linkedPages);
            $url = $view->url('site/page', [
                 'site-slug' => $site->slug(),
                 'page-slug' => $firstPage->slug(),
             ]);

            if ($this->isCurrentUrl($url)) {
                return true;
            }
        }

        // Check the root of the site.
        $url = $view->url('site', ['site-slug' => $site->slug()]);
        if ($this->isCurrentUrl($url)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the given URL matches the current request URL.
     *
     * Upgrade of a method of Omeka Classic / globals.php.
     *
     * @param string $url Relative or absolute
     * @return bool
     */
    protected function isCurrentUrl($url)
    {
        $view = $this->getView();
        $currentUrl = $this->currentUrl();
        $serverUrl = $view->serverUrl();
        $baseUrl = $view->basePath();

        // Strip out the protocol, host, base URL, and rightmost slash before
        // comparing the URL to the current one
        $stripOut = [$serverUrl . $baseUrl, @$_SERVER['HTTP_HOST'], $baseUrl];
        $currentUrl = rtrim(str_replace($stripOut, '', $currentUrl), '/');
        $url = rtrim(str_replace($stripOut, '', $url), '/');

        if (strlen($url) == 0) {
            return strlen($currentUrl) == 0;
        }
        // Don't check if the url is part of the current url.
        return $url == $currentUrl;
    }

    /**
     * Get the current URL.
     *
     * @return string
     */
    protected function currentUrl($absolute = false)
    {
        return $absolute
             ? $this->getView()->serverUrl(true)
             : $this->getView()->url(null, [], true);
    }
}
