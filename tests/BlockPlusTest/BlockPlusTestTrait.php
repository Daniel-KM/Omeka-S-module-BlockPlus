<?php declare(strict_types=1);

namespace BlockPlusTest;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Manager as ApiManager;

/**
 * Shared test helpers for BlockPlus module tests.
 */
trait BlockPlusTestTrait
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array List of created resource IDs for cleanup.
     */
    protected $createdResources = [];

    /**
     * @var array List of created site IDs for cleanup.
     */
    protected $createdSites = [];

    /**
     * Get the API manager.
     */
    protected function api(): ApiManager
    {
        return $this->getServiceLocator()->get('Omeka\ApiManager');
    }

    /**
     * Get the service locator.
     */
    protected function getServiceLocator(): ServiceLocatorInterface
    {
        if ($this->services === null) {
            $this->services = $this->getApplication()->getServiceManager();
        }
        return $this->services;
    }

    /**
     * Get the entity manager.
     */
    protected function getEntityManager()
    {
        return $this->getServiceLocator()->get('Omeka\EntityManager');
    }

    /**
     * Login as admin user.
     */
    protected function loginAdmin(): void
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $adapter = $auth->getAdapter();
        $adapter->setIdentity('admin@example.com');
        $adapter->setCredential('root');
        $auth->authenticate();
    }

    /**
     * Logout current user.
     */
    protected function logout(): void
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $auth->clearIdentity();
    }

    /**
     * Create a test site.
     *
     * @param string $slug Site slug.
     * @param string $title Site title.
     * @return \Omeka\Api\Representation\SiteRepresentation
     */
    protected function createSite(string $slug, string $title)
    {
        $response = $this->api()->create('sites', [
            'o:slug' => $slug,
            'o:title' => $title,
            'o:theme' => 'default',
            'o:is_public' => true,
        ]);
        $site = $response->getContent();
        $this->createdSites[] = $site->id();
        return $site;
    }

    /**
     * Create a test page for a site.
     *
     * @param int $siteId Site ID.
     * @param string $slug Page slug.
     * @param string $title Page title.
     * @param array $blocks Optional blocks to add.
     * @return \Omeka\Api\Representation\SitePageRepresentation
     */
    protected function createPage(int $siteId, string $slug, string $title, array $blocks = [])
    {
        $response = $this->api()->create('site_pages', [
            'o:site' => ['o:id' => $siteId],
            'o:slug' => $slug,
            'o:title' => $title,
            'o:block' => $blocks,
        ]);
        return $response->getContent();
    }

    /**
     * Create block data for a BlockPlus block.
     *
     * @param string $layout Block layout name.
     * @param array $data Block data.
     * @return array
     */
    protected function createBlockData(string $layout, array $data = []): array
    {
        return [
            'o:layout' => $layout,
            'o:data' => $data,
        ];
    }

    /**
     * Clean up created resources after test.
     */
    protected function cleanupResources(): void
    {
        // Delete created items.
        foreach ($this->createdResources as $resource) {
            try {
                $this->api()->delete($resource['type'], $resource['id']);
            } catch (\Exception $e) {
                // Ignore errors during cleanup.
            }
        }
        $this->createdResources = [];

        // Delete created sites.
        foreach ($this->createdSites as $siteId) {
            try {
                $this->api()->delete('sites', $siteId);
            } catch (\Exception $e) {
                // Ignore errors during cleanup.
            }
        }
        $this->createdSites = [];
    }
}
