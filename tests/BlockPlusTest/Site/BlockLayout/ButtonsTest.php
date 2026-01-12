<?php declare(strict_types=1);

namespace BlockPlusTest\Site\BlockLayout;

use BlockPlusTest\BlockPlusTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Tests for the Buttons block layout.
 */
class ButtonsTest extends AbstractHttpControllerTestCase
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
     * Test that the Buttons block layout is registered.
     */
    public function testBlockLayoutIsRegistered(): void
    {
        $config = $this->getServiceLocator()->get('Config');
        $blockLayouts = $config['block_layouts']['invokables'] ?? [];
        $this->assertArrayHasKey('buttons', $blockLayouts);
    }

    /**
     * Test that the block class exists.
     */
    public function testBlockClassExists(): void
    {
        $this->assertTrue(class_exists(\BlockPlus\Site\BlockLayout\Buttons::class));
    }

    /**
     * Test that a page with Buttons block can be created.
     */
    public function testPageWithButtonsBlockCanBeCreated(): void
    {
        // Create a test site.
        $site = $this->createSite('test-buttons-site', 'Test Buttons Site');

        // Create a page with a Buttons block.
        $blocks = [
            $this->createBlockData('buttons', [
                'buttons' => ['share', 'download'],
            ]),
        ];
        $page = $this->createPage($site->id(), 'test-buttons', 'Test Buttons Page', $blocks);

        $this->assertNotNull($page);
        $this->assertEquals('test-buttons', $page->slug());
    }
}
