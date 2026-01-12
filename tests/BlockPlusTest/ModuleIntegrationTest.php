<?php declare(strict_types=1);

namespace BlockPlusTest;

use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Integration tests for the BlockPlus module.
 *
 * Tests that verify the module is properly installed and configured.
 */
class ModuleIntegrationTest extends AbstractHttpControllerTestCase
{
    use BlockPlusTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        parent::tearDown();
    }

    /**
     * Test that the module is installed and active.
     */
    public function testModuleIsActive(): void
    {
        $moduleManager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule('BlockPlus');
        $this->assertNotNull($module, 'BlockPlus module should be found');
        $this->assertEquals('active', $module->getState(), 'BlockPlus module should be active');
    }

    /**
     * Test that all expected block layouts are registered.
     */
    public function testAllBlockLayoutsAreRegistered(): void
    {
        $config = $this->getServiceLocator()->get('Config');
        $invokables = $config['block_layouts']['invokables'] ?? [];
        $factories = $config['block_layouts']['factories'] ?? [];
        $blockLayouts = array_merge($invokables, $factories);

        $expectedLayouts = [
            'heading',
            'links',
            'buttons',
            'tableOfContents',
            'd3Graph',
            'externalContent',
            'itemSetShowcase',
            'listOfSites',
            'mirrorPage',
            'pageMetadata',
            'redirectToUrl',
            'searchForm',
            'searchResults',
            'showcase',
            'treeStructure',
            'messages',
        ];

        foreach ($expectedLayouts as $layout) {
            $this->assertArrayHasKey($layout, $blockLayouts, "Block layout '$layout' should be registered");
        }
    }

    /**
     * Test that all expected resource page block layouts are registered.
     */
    public function testAllResourcePageBlockLayoutsAreRegistered(): void
    {
        $config = $this->getServiceLocator()->get('Config');
        $resourceBlockLayouts = $config['resource_page_block_layouts']['invokables'] ?? [];

        $expectedLayouts = [
            'description',
            'title',
            'messages',
            'previousNext',
            'seeAlso',
            'buttons',
        ];

        foreach ($expectedLayouts as $layout) {
            $this->assertArrayHasKey($layout, $resourceBlockLayouts, "Resource block layout '$layout' should be registered");
        }
    }

    /**
     * Test that all expected view helpers are registered.
     */
    public function testAllViewHelpersAreRegistered(): void
    {
        $viewHelperManager = $this->getServiceLocator()->get('ViewHelperManager');

        $expectedHelpers = [
            'blockMetadata',
            'thumbnailUrl',
            'pageMetadata',
            'pagesMetadata',
            'assetElement',
            'ckEditor',
        ];

        foreach ($expectedHelpers as $helper) {
            $this->assertTrue(
                $viewHelperManager->has($helper),
                "View helper '$helper' should be registered"
            );
        }
    }

    /**
     * Test that form elements are registered.
     */
    public function testFormElementsAreRegistered(): void
    {
        $formElementManager = $this->getServiceLocator()->get('FormElementManager');

        $this->assertTrue(
            $formElementManager->has(\BlockPlus\Form\Element\TemplateSelect::class),
            'TemplateSelect form element should be registered'
        );
    }

    /**
     * Test that controller plugins are registered.
     */
    public function testControllerPluginsAreRegistered(): void
    {
        $controllerPluginManager = $this->getServiceLocator()->get('ControllerPluginManager');

        $this->assertTrue(
            $controllerPluginManager->has('pageModels'),
            'pageModels controller plugin should be registered'
        );
    }

    /**
     * Test creating a complete page with multiple blocks.
     */
    public function testCreatePageWithMultipleBlocks(): void
    {
        // Create a test site.
        $site = $this->createSite('test-multi-block-site', 'Test Multi Block Site');

        // Create a page with multiple blocks.
        $blocks = [
            $this->createBlockData('heading', [
                'text' => 'Welcome',
                'level' => 1,
            ]),
            $this->createBlockData('links', [
                'heading' => 'Quick Links',
                'links' => [],
            ]),
            $this->createBlockData('buttons', [
                'buttons' => ['share'],
            ]),
        ];

        $page = $this->createPage($site->id(), 'multi-block', 'Multi Block Page', $blocks);

        $this->assertNotNull($page);
        $this->assertEquals('multi-block', $page->slug());

        // Verify all blocks were saved.
        $savedBlocks = $page->blocks();
        $this->assertCount(3, $savedBlocks);
        $this->assertEquals('heading', $savedBlocks[0]->layout());
        $this->assertEquals('links', $savedBlocks[1]->layout());
        $this->assertEquals('buttons', $savedBlocks[2]->layout());
    }

    /**
     * Test that page can be accessed via route.
     */
    public function testPageCanBeAccessedViaRoute(): void
    {
        // Create a test site.
        $site = $this->createSite('test-route-site', 'Test Route Site');

        // Create a page.
        $blocks = [
            $this->createBlockData('heading', [
                'text' => 'Test Page',
                'level' => 1,
            ]),
        ];
        $page = $this->createPage($site->id(), 'test-page', 'Test Page', $blocks);

        // Access the page.
        $this->dispatch('/s/test-route-site/page/test-page');
        $this->assertResponseStatusCode(200);
    }

    /**
     * Test that block templates configuration is available.
     */
    public function testBlockTemplatesConfigurationExists(): void
    {
        $config = $this->getServiceLocator()->get('Config');
        $this->assertArrayHasKey('block_templates', $config);

        $blockTemplates = $config['block_templates'];
        $this->assertIsArray($blockTemplates);
    }

    /**
     * Test that page models configuration is available.
     */
    public function testPageModelsConfigurationExists(): void
    {
        $config = $this->getServiceLocator()->get('Config');
        $this->assertArrayHasKey('page_models', $config);

        $pageModels = $config['page_models'];
        $this->assertIsArray($pageModels);
    }
}
