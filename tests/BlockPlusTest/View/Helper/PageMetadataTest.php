<?php declare(strict_types=1);

namespace BlockPlusTest\View\Helper;

use BlockPlusTest\BlockPlusTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Tests for the PageMetadata view helper.
 */
class PageMetadataTest extends AbstractHttpControllerTestCase
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
     * Test that the PageMetadata helper is registered.
     */
    public function testHelperIsRegistered(): void
    {
        $viewHelperManager = $this->getServiceLocator()->get('ViewHelperManager');
        $this->assertTrue($viewHelperManager->has('pageMetadata'));
    }

    /**
     * Test that the helper can be instantiated.
     */
    public function testHelperCanBeInstantiated(): void
    {
        $viewHelperManager = $this->getServiceLocator()->get('ViewHelperManager');
        $helper = $viewHelperManager->get('pageMetadata');
        $this->assertInstanceOf(\BlockPlus\View\Helper\PageMetadata::class, $helper);
    }
}
