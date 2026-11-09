<?php declare(strict_types=1);

namespace BlockPlusTest\Stdlib;

use BlockPlus\Stdlib\BlueskyFeed;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Test as TestAdapter;
use PHPUnit\Framework\TestCase;

class BlueskyFeedTest extends TestCase
{
    protected function profile(): array
    {
        return [
            'did' => 'did:plc:ym3ni73m2ccyd66mfoer4nuj',
            'handle' => 'psl-univ.bsky.social',
            'displayName' => 'Université PSL',
        ];
    }

    /**
     * A feed like the one of the public api, with a post, a reply and a repost.
     */
    protected function feed(): array
    {
        // The positions of the facets are in bytes: "é" is two bytes.
        $text = "🏅 Prix de l’ @academie.bsky.social\nVoir psl.eu #science <script>";
        $mention = '@academie.bsky.social';
        $mentionStart = strpos($text, $mention);
        $link = 'psl.eu';
        $linkStart = strpos($text, $link);
        $tag = '#science';
        $tagStart = strpos($text, $tag);
        return [
            [
                'post' => [
                    'uri' => 'at://did:plc:ym3ni73m2ccyd66mfoer4nuj/app.bsky.feed.post/3mzi5ybgj6s2q',
                    'author' => ['handle' => 'psl-univ.bsky.social'],
                    'record' => [
                        'text' => $text,
                        'createdAt' => '2026-11-04T10:38:56.844Z',
                        'facets' => [
                            ['index' => ['byteStart' => $tagStart, 'byteEnd' => $tagStart + strlen($tag)], 'features' => [['$type' => 'app.bsky.richtext.facet#tag', 'tag' => 'science']]],
                            ['index' => ['byteStart' => $mentionStart, 'byteEnd' => $mentionStart + strlen($mention)], 'features' => [['$type' => 'app.bsky.richtext.facet#mention', 'did' => 'did:plc:abc']]],
                            ['index' => ['byteStart' => $linkStart, 'byteEnd' => $linkStart + strlen($link)], 'features' => [['$type' => 'app.bsky.richtext.facet#link', 'uri' => 'https://psl.eu']]],
                        ],
                    ],
                    'embed' => [
                        '$type' => 'app.bsky.embed.images#view',
                        'images' => [['thumb' => 'https://cdn.bsky.app/img/thumb.jpg', 'alt' => 'Lauréats']],
                    ],
                ],
            ],
            [
                'post' => [
                    'uri' => 'at://did:plc:ym3ni73m2ccyd66mfoer4nuj/app.bsky.feed.post/3mzi5ydave22q',
                    'author' => ['handle' => 'psl-univ.bsky.social'],
                    'record' => ['text' => 'Reply', 'createdAt' => '2026-11-04T10:38:57Z', 'reply' => ['root' => []]],
                ],
            ],
            [
                'post' => [
                    'uri' => 'at://did:plc:other/app.bsky.feed.post/3myhozzi6rs2v',
                    'author' => ['handle' => 'theguild.bsky.social'],
                    'record' => ['text' => 'Repost', 'createdAt' => '2026-10-22T12:46:17Z'],
                ],
                'reason' => ['$type' => 'app.bsky.feed.defs#reasonRepost'],
            ],
        ];
    }

    protected function bluesky(array $responses = []): BlueskyFeed
    {
        $adapter = new TestAdapter();
        $adapter->setResponse(array_shift($responses) ?? "HTTP/1.1 404 Not Found\r\n\r\n");
        foreach ($responses as $response) {
            $adapter->addResponse($response);
        }
        return new BlueskyFeed(new Client(null, ['adapter' => $adapter]));
    }

    public function testPostIsConverted(): void
    {
        $result = $this->bluesky()->parseFeed($this->profile(), $this->feed(), ['limit' => 0]);
        $this->assertSame([
            'id' => 'did:plc:ym3ni73m2ccyd66mfoer4nuj',
            'name' => 'psl-univ.bsky.social',
            'fullname' => 'Université PSL',
            'url' => 'https://bsky.app/profile/psl-univ.bsky.social',
        ], $result['account']);
        $this->assertCount(1, $result['messages']);
        $message = $result['messages'][0];
        $this->assertSame('3mzi5ybgj6s2q', $message['id']);
        $this->assertSame('https://bsky.app/profile/psl-univ.bsky.social/post/3mzi5ybgj6s2q', $message['url']);
        $this->assertSame(strtotime('2026-11-04T10:38:56Z'), $message['timestamp']);

        $content = $message['content'];
        $this->assertStringContainsString('🏅 Prix de l’ <a href="https://bsky.app/profile/did:plc:abc"', $content);
        $this->assertStringContainsString('>@academie.bsky.social</a>', $content);
        $this->assertStringContainsString('<a href="https://psl.eu"', $content);
        $this->assertStringContainsString('<a href="https://bsky.app/hashtag/science"', $content);
        $this->assertStringContainsString('<br />', $content);
        $this->assertStringContainsString('<img src="https://cdn.bsky.app/img/thumb.jpg" alt="Lauréats"', $content);
        $this->assertStringNotContainsString('<script', $content);
        $this->assertStringContainsString('&lt;script&gt;', $content);
    }

    public function testRepliesAndRepostsOnDemand(): void
    {
        $messages = $this->bluesky()->parseFeed($this->profile(), $this->feed(), ['limit' => 0, 'reposts' => true, 'replies' => true])['messages'];
        $this->assertCount(3, $messages);
        $this->assertTrue($messages[1]['is_reply']);
        $this->assertTrue($messages[2]['is_retweet']);
        $this->assertSame('theguild.bsky.social', $messages[2]['author']);
        $this->assertSame('https://bsky.app/profile/theguild.bsky.social', $messages[2]['author_url']);
        $this->assertSame('https://bsky.app/profile/theguild.bsky.social/post/3myhozzi6rs2v', $messages[2]['url']);
    }

    public function testFetch(): void
    {
        $result = $this->bluesky([
            "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n" . json_encode($this->profile()),
            "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n" . json_encode(['feed' => $this->feed()]),
        ])->fetch('https://bsky.app/profile/psl-univ.bsky.social', ['limit' => 1]);
        $this->assertSame('Université PSL', $result['account']['fullname']);
        $this->assertCount(1, $result['messages']);
    }

    public function testFetchUnavailable(): void
    {
        $this->assertNull($this->bluesky()->fetch('psl-univ.bsky.social'));
        $this->assertNull($this->bluesky()->fetch('not a handle'));
    }

    public function accountProvider(): array
    {
        return [
            ['psl-univ.bsky.social', 'psl-univ.bsky.social'],
            ['@PSL-Univ.bsky.social', 'psl-univ.bsky.social'],
            ['https://bsky.app/profile/psl-univ.bsky.social', 'psl-univ.bsky.social'],
            ['https://bsky.app/profile/psl-univ.bsky.social/post/3mzi5ybgj6s2q', 'psl-univ.bsky.social'],
            ['psl.eu', 'psl.eu'],
            ['did:plc:ym3ni73m2ccyd66mfoer4nuj', 'did:plc:ym3ni73m2ccyd66mfoer4nuj'],
            ['psl_univ', ''],
            ['../admin', ''],
            ['', ''],
        ];
    }

    /**
     * @dataProvider accountProvider
     */
    public function testNormalizeAccount(string $account, string $expected): void
    {
        $this->assertSame($expected, BlueskyFeed::normalizeAccount($account));
    }
}
