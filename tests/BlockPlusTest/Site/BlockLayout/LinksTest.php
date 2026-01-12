<?php declare(strict_types=1);

namespace BlockPlusTest\Site\BlockLayout;

use BlockPlusTest\BlockPlusTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Tests for the Links block layout.
 */
class LinksTest extends AbstractHttpControllerTestCase
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
     * Test that the Links block layout is registered.
     */
    public function testBlockLayoutIsRegistered(): void
    {
        $config = $this->getServiceLocator()->get('Config');
        $blockLayouts = $config['block_layouts']['invokables'] ?? [];
        $this->assertArrayHasKey('links', $blockLayouts);
    }

    /**
     * Test that the block class exists.
     */
    public function testBlockClassExists(): void
    {
        $this->assertTrue(class_exists(\BlockPlus\Site\BlockLayout\Links::class));
    }

    /**
     * Test that a page with Links block can be created.
     */
    public function testPageWithLinksBlockCanBeCreated(): void
    {
        // Create a test site.
        $site = $this->createSite('test-links-site', 'Test Links Site');

        // Create a page with a Links block.
        $blocks = [
            $this->createBlockData('links', [
                'heading' => 'Quick Links',
                'links' => [],
            ]),
        ];
        $page = $this->createPage($site->id(), 'test-links', 'Test Links Page', $blocks);

        $this->assertNotNull($page);
        $this->assertEquals('test-links', $page->slug());
    }
}
