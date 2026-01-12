<?php declare(strict_types=1);

namespace BlockPlusTest\Site\BlockLayout;

use BlockPlusTest\BlockPlusTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Tests for the TableOfContents block layout.
 */
class TableOfContentsTest extends AbstractHttpControllerTestCase
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
     * Test that the TableOfContents block layout is registered.
     */
    public function testBlockLayoutIsRegistered(): void
    {
        $config = $this->getServiceLocator()->get('Config');
        $blockLayouts = $config['block_layouts']['invokables'] ?? [];
        $this->assertArrayHasKey('tableOfContents', $blockLayouts);
    }

    /**
     * Test that the block class exists.
     */
    public function testBlockClassExists(): void
    {
        $this->assertTrue(class_exists(\BlockPlus\Site\BlockLayout\TableOfContents::class));
    }

    /**
     * Test that a page with TableOfContents block can be created.
     */
    public function testPageWithTableOfContentsBlockCanBeCreated(): void
    {
        // Create a test site.
        $site = $this->createSite('test-toc-site', 'Test TOC Site');

        // Create a page with a TableOfContents block.
        $blocks = [
            $this->createBlockData('tableOfContents', [
                'depth' => 1,
                'root' => null,
            ]),
        ];
        $page = $this->createPage($site->id(), 'test-toc', 'Test TOC Page', $blocks);

        $this->assertNotNull($page);
        $this->assertEquals('test-toc', $page->slug());
    }
}
