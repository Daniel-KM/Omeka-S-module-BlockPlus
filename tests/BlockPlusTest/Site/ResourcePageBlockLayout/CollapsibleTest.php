<?php declare(strict_types=1);

namespace BlockPlusTest\Site\ResourcePageBlockLayout;

use BlockPlusTest\BlockPlusTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Resource page blocks "Collapsible (start)" and "Collapsible (end)".
 */
class CollapsibleTest extends AbstractHttpControllerTestCase
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

    protected function render(string $layout, $resource): string
    {
        $services = $this->getServiceLocator();
        $view = $services->get('ViewHelperManager')->getRenderer();
        return $services->get('Omeka\ResourcePageBlockLayoutManager')->get($layout)->render($view, $resource);
    }

    public function testBlocksAreRegisteredForAllResources(): void
    {
        $manager = $this->getServiceLocator()->get('Omeka\ResourcePageBlockLayoutManager');
        foreach (['collapsibleStart' => 'Collapsible (start)', 'collapsibleEnd' => 'Collapsible (end)'] as $name => $label) {
            $layout = $manager->get($name);
            $this->assertSame($label, $layout->getLabel());
            $this->assertSame(['items', 'media', 'item_sets', 'digital_objects'], $layout->getCompatibleResourceNames());
        }
    }

    public function testStartAndEndWrapTheOtherBlocks(): void
    {
        $item = $this->api()->create('items', [])->getContent();
        $this->createdResources[] = ['type' => 'items', 'id' => $item->id()];

        $html = $this->render('collapsibleStart', $item)
            . '<div class="block other">Viewer</div>'
            . $this->render('collapsibleEnd', $item);

        // The markup is well-formed: the end closes the divs of the start.
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML('<root>' . $html . '</root>'));
        $xpath = new \DOMXPath($dom);

        $block = $xpath->query('/root/div[contains(@class, "block-collapsible")]');
        $this->assertSame(1, $block->length);

        // The button is outside of the content and controls it.
        $button = $xpath->query('button[@class="block-collapsible-button"]', $block->item(0))->item(0);
        $content = $xpath->query('div[@class="block-collapsible-content"]', $block->item(0))->item(0);
        $this->assertNotNull($button);
        $this->assertNotNull($content);
        $this->assertSame($content->getAttribute('id'), $button->getAttribute('aria-controls'));
        $this->assertStringStartsWith('block-collapsible-' . $item->id() . '-', $content->getAttribute('id'));
        $this->assertSame('true', $button->getAttribute('aria-expanded'));
        $this->assertSame('Enlarge', $button->getAttribute('data-label-expand'));
        $this->assertSame('Reduce', $button->getAttribute('data-label-collapse'));

        // The other blocks are inside the content.
        $this->assertSame('Viewer', trim($xpath->query('div[contains(@class, "other")]', $content)->item(0)->textContent));
    }

    public function testEachStartHasItsOwnId(): void
    {
        $item = $this->api()->create('items', [])->getContent();
        $this->createdResources[] = ['type' => 'items', 'id' => $item->id()];
        preg_match('~aria-controls="([^"]+)"~', $this->render('collapsibleStart', $item), $first);
        preg_match('~aria-controls="([^"]+)"~', $this->render('collapsibleStart', $item), $second);
        $this->assertNotSame($first[1], $second[1]);
    }
}
