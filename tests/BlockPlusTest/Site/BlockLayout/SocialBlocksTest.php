<?php declare(strict_types=1);

namespace BlockPlusTest\Site\BlockLayout;

use BlockPlusTest\BlockPlusTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Blocks Bluesky and Mastodon.
 */
class SocialBlocksTest extends AbstractHttpControllerTestCase
{
    use BlockPlusTestTrait;

    protected array $cacheFiles = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        foreach ($this->cacheFiles as $file) {
            @unlink($file);
        }
        $this->cleanupResources();
        parent::tearDown();
    }

    public function blockProvider(): array
    {
        return [
            'bluesky' => ['bluesky', 'https://bsky.app/profile/PSL-Univ.bsky.social', 'psl-univ.bsky.social'],
            'mastodon' => ['mastodon', 'https://mastodon.social/@Gargron', 'gargron@mastodon.social'],
        ];
    }

    /**
     * @dataProvider blockProvider
     */
    public function testHydrateKeepsOnlyTheOptionsAndNormalizesTheAccount(string $layout, string $account, string $expected): void
    {
        $site = $this->createSite('test-' . $layout . '-hydrate', 'Test ' . $layout);
        $page = $this->createPage($site->id(), $layout, $layout, [
            $this->createBlockData($layout, ['account' => $account, 'limit' => '2', 'reposts' => '1', 'replies' => '0', 'other' => 'x']),
        ]);
        $this->assertSame(
            ['account' => $expected, 'limit' => '2', 'reposts' => '1', 'replies' => '0'],
            $page->blocks()[0]->data()
        );
    }

    /**
     * @dataProvider blockProvider
     */
    public function testHydrateRequiresAValidAccount(string $layout): void
    {
        $site = $this->createSite('test-' . $layout . '-invalid', 'Test ' . $layout);
        $this->expectException(\Omeka\Api\Exception\ValidationException::class);
        $this->createPage($site->id(), $layout, $layout, [
            $this->createBlockData($layout, ['account' => 'not an account']),
        ]);
    }

    /**
     * @dataProvider blockProvider
     */
    public function testRenderFromCache(string $layout, string $account, string $expected): void
    {
        $services = $this->getServiceLocator();
        $site = $this->createSite('test-' . $layout . '-render', 'Test ' . $layout);
        $page = $this->createPage($site->id(), $layout, $layout, [
            $this->createBlockData($layout, ['account' => $account, 'limit' => 1]),
        ]);
        $block = $page->blocks()[0];
        $blockLayout = $services->get('Omeka\BlockLayoutManager')->get($layout);

        // Prepare a fresh cache, so the network is not requested.
        $method = new \ReflectionMethod($blockLayout, 'cacheFile');
        $method->setAccessible(true);
        $cacheFile = $method->invoke($blockLayout, $services, $expected, ['limit' => 1, 'reposts' => false, 'replies' => false]);
        $this->cacheFiles[] = $cacheFile;
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, json_encode([
            'account' => ['id' => '1', 'name' => $expected, 'fullname' => 'Université PSL', 'url' => 'https://example.org/psl'],
            'messages' => [[
                'id' => '42',
                'url' => 'https://example.org/psl/42',
                'created_at' => '2026-11-04T10:38:56+00:00',
                'timestamp' => strtotime('2026-11-04T10:38:56Z'),
                'content' => '<p>Cached message</p>',
                'author' => $expected,
                'author_url' => 'https://example.org/psl',
                'is_retweet' => false,
                'is_reply' => false,
                'is_pinned' => false,
            ]],
            'fetched' => time(),
        ]));

        $view = $services->get('ViewHelperManager')->getRenderer();
        // The attributes are escaped.
        $html = html_entity_decode($blockLayout->render($view, $block), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('class="block block-' . $layout . '"', $html);
        $this->assertStringContainsString('<p>Cached message</p>', $html);
        $this->assertStringContainsString('Université PSL', $html);
        $this->assertStringContainsString('href="https://example.org/psl/42"', $html);
        $this->assertStringContainsString('datetime="2026-11-04T10:38:56+00:00"', $html);
    }

    /**
     * @dataProvider blockProvider
     */
    public function testFormIsRendered(string $layout): void
    {
        $services = $this->getServiceLocator();
        $site = $this->createSite('test-' . $layout . '-form', 'Test ' . $layout);
        $view = $services->get('ViewHelperManager')->getRenderer();
        $html = html_entity_decode($services->get('Omeka\BlockLayoutManager')->get($layout)->form($view, $site), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('[o:data][account]', $html);
        $this->assertStringContainsString('[o:data][reposts]', $html);
        $this->assertStringContainsString('[o:data][replies]', $html);
        // The note about privacy is displayed, without input.
        $this->assertStringContainsString('not compliant with the GDPR', $html);
        $this->assertStringNotContainsString('[o:data][privacy]', $html);
    }
}
