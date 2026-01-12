<?php declare(strict_types=1);

namespace BlockPlusTest\Site\BlockLayout;

use BlockPlusTest\BlockPlusTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Tests for the Heading block layout.
 */
class HeadingTest extends AbstractHttpControllerTestCase
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
     * Test that the Heading block layout is registered.
     */
    public function testBlockLayoutIsRegistered(): void
    {
        $config = $this->getServiceLocator()->get('Config');
        $blockLayouts = $config['block_layouts']['invokables'] ?? [];
        $this->assertArrayHasKey('heading', $blockLayouts);
    }

    /**
     * Test that a page with Heading block can be created.
     */
    public function testPageWithHeadingBlockCanBeCreated(): void
    {
        // Create a test site.
        $site = $this->createSite('test-heading-site', 'Test Heading Site');

        // Create a page with a Heading block.
        $blocks = [
            $this->createBlockData('heading', [
                'text' => 'Test Heading',
                'level' => 2,
            ]),
        ];
        $page = $this->createPage($site->id(), 'test-heading', 'Test Heading Page', $blocks);

        $this->assertNotNull($page);
        $this->assertEquals('test-heading', $page->slug());

        // Verify the block was saved.
        $savedBlocks = $page->blocks();
        $this->assertCount(1, $savedBlocks);
        $this->assertEquals('heading', $savedBlocks[0]->layout());
    }
}
