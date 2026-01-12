<?php declare(strict_types=1);

namespace BlockPlusTest\View\Helper;

use BlockPlusTest\BlockPlusTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Tests for the ThumbnailUrl view helper.
 */
class ThumbnailUrlTest extends AbstractHttpControllerTestCase
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
     * Test that the ThumbnailUrl helper is registered.
     */
    public function testHelperIsRegistered(): void
    {
        $viewHelperManager = $this->getServiceLocator()->get('ViewHelperManager');
        $this->assertTrue($viewHelperManager->has('thumbnailUrl'));
    }

    /**
     * Test that the helper can be instantiated.
     */
    public function testHelperCanBeInstantiated(): void
    {
        $viewHelperManager = $this->getServiceLocator()->get('ViewHelperManager');
        $helper = $viewHelperManager->get('thumbnailUrl');
        $this->assertInstanceOf(\BlockPlus\View\Helper\ThumbnailUrl::class, $helper);
    }
}
