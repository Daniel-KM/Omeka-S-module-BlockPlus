<?php declare(strict_types=1);

namespace BlockPlusTest\View\Helper;

use BlockPlusTest\BlockPlusTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Tests for the BlockMetadata view helper.
 */
class BlockMetadataTest extends AbstractHttpControllerTestCase
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
     * Test that the BlockMetadata helper is registered.
     */
    public function testHelperIsRegistered(): void
    {
        $viewHelperManager = $this->getServiceLocator()->get('ViewHelperManager');
        $this->assertTrue($viewHelperManager->has('blockMetadata'));
    }

    /**
     * Test that the helper can be instantiated.
     */
    public function testHelperCanBeInstantiated(): void
    {
        $viewHelperManager = $this->getServiceLocator()->get('ViewHelperManager');
        $helper = $viewHelperManager->get('blockMetadata');
        $this->assertInstanceOf(\BlockPlus\View\Helper\BlockMetadata::class, $helper);
    }
}
