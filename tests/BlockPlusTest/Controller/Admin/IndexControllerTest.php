<?php declare(strict_types=1);

namespace BlockPlusTest\Controller\Admin;

use BlockPlusTest\BlockPlusTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Tests for BlockPlus admin controller.
 */
class IndexControllerTest extends AbstractHttpControllerTestCase
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
        $this->assertNotNull($module);
        $this->assertEquals('active', $module->getState());
    }

    /**
     * Test that block layouts are registered.
     */
    public function testBlockLayoutsAreRegistered(): void
    {
        $config = $this->getServiceLocator()->get('Config');
        $this->assertArrayHasKey('block_layouts', $config);

        $blockLayouts = $config['block_layouts'];
        $this->assertArrayHasKey('invokables', $blockLayouts);

        // Check some BlockPlus block layouts are registered.
        $invokables = $blockLayouts['invokables'];
        $this->assertArrayHasKey('heading', $invokables);
        $this->assertArrayHasKey('links', $invokables);
        $this->assertArrayHasKey('buttons', $invokables);
    }

    /**
     * Test that resource page block layouts are registered.
     */
    public function testResourcePageBlockLayoutsAreRegistered(): void
    {
        $config = $this->getServiceLocator()->get('Config');
        $this->assertArrayHasKey('resource_page_block_layouts', $config);

        $resourceBlockLayouts = $config['resource_page_block_layouts'];
        $this->assertArrayHasKey('invokables', $resourceBlockLayouts);

        // Check some BlockPlus resource page block layouts are registered.
        $invokables = $resourceBlockLayouts['invokables'];
        $this->assertArrayHasKey('description', $invokables);
        $this->assertArrayHasKey('title', $invokables);
    }

    /**
     * Test that view helpers are registered.
     */
    public function testViewHelpersAreRegistered(): void
    {
        $viewHelperManager = $this->getServiceLocator()->get('ViewHelperManager');

        // Check BlockPlus view helpers are registered.
        $this->assertTrue($viewHelperManager->has('blockMetadata'));
        $this->assertTrue($viewHelperManager->has('thumbnailUrl'));
        $this->assertTrue($viewHelperManager->has('assetUrl'));
    }
}
