<?php declare(strict_types=1);

namespace BlockPlusTest\Site\BlockLayout;

use BlockPlus\Site\BlockLayout\Twitter;
use BlockPlusTest\BlockPlusTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

class TwitterTest extends AbstractHttpControllerTestCase
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
        $this->getServiceLocator()->get('Omeka\Settings')->delete('blockplus_twitter_mirrors');
        $this->cleanupResources();
        parent::tearDown();
    }

    protected function blockData(int $pageId): array
    {
        $data = $this->getServiceLocator()->get('Omeka\Connection')
            ->fetchOne('SELECT `data` FROM `site_page_block` WHERE `page_id` = ? AND `layout` = "twitter"', [$pageId]);
        return json_decode((string) $data, true);
    }

    public function testHydrateKeepsOnlyTheOptionsAndNormalizesTheAccount(): void
    {
        $site = $this->createSite('test-twitter-hydrate', 'Test twitter');
        $page = $this->createPage($site->id(), 'twitter', 'Twitter', [
            $this->createBlockData('twitter', [
                'account' => 'https://x.com/psl_univ',
                'limit' => '2',
                'retweet' => '1',
                'replies' => '0',
                'authorization' => 'abc',
                'guest_token' => 'def',
            ]),
        ]);
        $this->assertSame([
            'account' => 'psl_univ',
            'limit' => '2',
            'retweet' => '1',
            'replies' => '0',
        ], $this->blockData($page->id()));
    }

    public function testHydrateRequiresAValidAccount(): void
    {
        $site = $this->createSite('test-twitter-invalid', 'Test twitter');
        $this->expectException(\Omeka\Api\Exception\ValidationException::class);
        $this->createPage($site->id(), 'twitter', 'Twitter', [
            $this->createBlockData('twitter', ['account' => 'not an account']),
        ]);
    }

    public function testCacheIsUsedWhenNoMirrorIsAvailable(): void
    {
        $services = $this->getServiceLocator();
        // A mirror that is never available.
        $services->get('Omeka\Settings')->set('blockplus_twitter_mirrors', ['http://127.0.0.1:9']);

        $block = new Twitter();
        $options = ['limit' => 1, 'retweet' => false, 'replies' => false];
        $method = new \ReflectionMethod($block, 'cacheFile');
        $method->setAccessible(true);
        $cacheFile = $method->invoke($block, $services, 'psl_univ', $options);
        $this->cacheFiles[] = $cacheFile;
        @mkdir(dirname($cacheFile), 0775, true);

        // Without cache, there is nothing to display, and the failure is
        // stored, so the mirrors are not requested for each display.
        @unlink($cacheFile);
        $this->assertNull($block->messages($services, 'psl_univ', $options));
        $failure = json_decode((string) file_get_contents($cacheFile), true);
        $this->assertGreaterThan(time() - 60, $failure['failed']);
        $this->assertArrayNotHasKey('messages', $failure);
        $this->assertNull($block->messages($services, 'psl_univ', $options));

        // An expired cache is used when the mirrors are unavailable.
        $cache = [
            'account' => ['id' => 'psl_univ', 'name' => 'psl_univ', 'fullname' => 'Université PSL', 'url' => 'https://x.com/psl_univ'],
            'messages' => [['id' => '1', 'url' => 'https://x.com/psl_univ/status/1', 'content' => '<p>Cached</p>']],
            'fetched' => time() - 10 * Twitter::CACHE_TTL,
        ];
        file_put_contents($cacheFile, json_encode($cache));
        $result = $block->messages($services, '@psl_univ', $options);
        $this->assertSame('<p>Cached</p>', $result['messages'][0]['content']);
        $failure = json_decode((string) file_get_contents($cacheFile), true);
        $this->assertSame('<p>Cached</p>', $failure['messages'][0]['content']);
        $this->assertGreaterThan(time() - 60, $failure['failed']);
    }

    public function testUpgradeRemovesTheDataOfTheApi(): void
    {
        $services = $this->getServiceLocator();
        $site = $this->createSite('test-twitter-upgrade', 'Test twitter');
        $page = $this->createPage($site->id(), 'twitter', 'Twitter', [
            $this->createBlockData('twitter', ['account' => 'psl_univ', 'limit' => 3, 'retweet' => true]),
        ]);
        $oldData = [
            'account' => 'psl_univ',
            'limit' => 3,
            'retweet' => true,
            'authorization' => '',
            'api' => '1.1',
            'account_data' => ['id' => '1', 'name' => 'psl_univ'],
            'authorization_bearer' => 'AAAA',
            'guest_token' => '123',
            'cache' => [['id' => '1']],
        ];
        $services->get('Omeka\Connection')->executeStatement(
            'UPDATE `site_page_block` SET `data` = ? WHERE `page_id` = ? AND `layout` = "twitter"',
            [json_encode($oldData), $page->id()]
        );

        // The script is included with "require", because the module uses
        // "require_once", so it would be run once only by process.
        $module = new \BlockPlus\Module();
        $module->setServiceLocator($services);
        $include = function (string $oldVersion, string $newVersion, $services): void {
            require dirname(__DIR__, 4) . '/data/scripts/upgrade.php';
        };
        \Closure::bind($include, $module, \BlockPlus\Module::class)('3.4.46', '3.4.47', $services);

        $this->assertSame(['account' => 'psl_univ', 'limit' => 3, 'retweet' => true], $this->blockData($page->id()));
    }
}
