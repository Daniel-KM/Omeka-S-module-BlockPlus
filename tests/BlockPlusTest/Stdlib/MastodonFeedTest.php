<?php declare(strict_types=1);

namespace BlockPlusTest\Stdlib;

use BlockPlus\Stdlib\MastodonFeed;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Test as TestAdapter;
use PHPUnit\Framework\TestCase;

class MastodonFeedTest extends TestCase
{
    protected function profile(): array
    {
        return [
            'id' => '1',
            'username' => 'Gargron',
            'acct' => 'Gargron',
            'display_name' => 'Eugen Rochko',
            'url' => 'https://mastodon.social/@Gargron',
        ];
    }

    /**
     * Statuses like the ones of the api, with a status, a reply and a boost.
     */
    protected function statuses(): array
    {
        return [
            [
                'id' => '117564197289781943',
                'created_at' => '2026-11-05T14:01:27.000Z',
                'in_reply_to_id' => null,
                'url' => 'https://mastodon.social/@Gargron/117564197289781943',
                'spoiler_text' => '',
                'content' => '<p>View on Ambleside <a href="https://mastodon.social/tags/photo" class="mention hashtag" onclick="alert(1)">#photo</a></p><script>alert(2)</script>',
                'account' => ['acct' => 'Gargron', 'url' => 'https://mastodon.social/@Gargron'],
                'media_attachments' => [
                    ['type' => 'image', 'preview_url' => 'https://files.mastodon.social/small/a.jpg', 'description' => 'Lake'],
                    ['type' => 'video', 'preview_url' => 'https://files.mastodon.social/small/b.jpg'],
                ],
            ],
            [
                'id' => '117561344417636889',
                'created_at' => '2026-11-05T01:55:56.000Z',
                'in_reply_to_id' => '1',
                'url' => 'https://mastodon.social/@Gargron/117561344417636889',
                'content' => '<p>Reply</p>',
                'account' => ['acct' => 'Gargron'],
            ],
            [
                'id' => '117569846249278793',
                'created_at' => '2026-11-06T13:58:04.000Z',
                'url' => 'https://mastodon.social/users/Gargron/statuses/117569846249278793/activity',
                'reblog' => [
                    'id' => '117518861256497764',
                    'created_at' => '2026-10-28T13:51:54.000Z',
                    'url' => 'https://laserdisc.party/@carithlee/117518861256497764',
                    'spoiler_text' => 'Content warning',
                    'content' => '<p>Boosted</p>',
                    'account' => ['acct' => 'carithlee@laserdisc.party', 'url' => 'https://laserdisc.party/@carithlee'],
                ],
            ],
        ];
    }

    protected function mastodon(array $responses = []): MastodonFeed
    {
        $adapter = new TestAdapter();
        $adapter->setResponse(array_shift($responses) ?? "HTTP/1.1 404 Not Found\r\n\r\n");
        foreach ($responses as $response) {
            $adapter->addResponse($response);
        }
        return new MastodonFeed(new Client(null, ['adapter' => $adapter]));
    }

    public function testStatusIsConverted(): void
    {
        $result = $this->mastodon()->parseStatuses($this->profile(), 'mastodon.social', $this->statuses(), ['limit' => 0]);
        $this->assertSame([
            'id' => '1',
            'name' => 'Gargron@mastodon.social',
            'fullname' => 'Eugen Rochko',
            'url' => 'https://mastodon.social/@Gargron',
        ], $result['account']);
        $this->assertCount(1, $result['messages']);
        $message = $result['messages'][0];
        $this->assertSame('https://mastodon.social/@Gargron/117564197289781943', $message['url']);
        $this->assertSame(strtotime('2026-11-05T14:01:27Z'), $message['timestamp']);

        $content = $message['content'];
        $this->assertStringContainsString('<a href="https://mastodon.social/tags/photo"', $content);
        $this->assertStringNotContainsString('onclick', $content);
        $this->assertStringNotContainsString('<script', $content);
        $this->assertStringContainsString('<img src="https://files.mastodon.social/small/a.jpg" alt="Lake"', $content);
        // Only images are displayed.
        $this->assertStringNotContainsString('b.jpg', $content);
    }

    public function testBoostDisplaysTheOriginalStatus(): void
    {
        $messages = $this->mastodon()->parseStatuses($this->profile(), 'mastodon.social', $this->statuses(), ['limit' => 0, 'reposts' => true, 'replies' => true])['messages'];
        $this->assertCount(3, $messages);
        $this->assertTrue($messages[1]['is_reply']);
        $boost = $messages[2];
        $this->assertTrue($boost['is_retweet']);
        $this->assertSame('https://laserdisc.party/@carithlee/117518861256497764', $boost['url']);
        $this->assertSame('carithlee@laserdisc.party', $boost['author']);
        $this->assertSame('https://laserdisc.party/@carithlee', $boost['author_url']);
        $this->assertStringContainsString('Content warning', $boost['content']);
    }

    public function testFetchRequestsTheServerOfTheAccount(): void
    {
        $adapter = new TestAdapter();
        $adapter->setResponse("HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n" . json_encode($this->profile()));
        $adapter->addResponse("HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n" . json_encode($this->statuses()));
        $client = new Client(null, ['adapter' => $adapter]);
        $result = (new MastodonFeed($client))->fetch('@Gargron@mastodon.social', ['limit' => 2]);
        $this->assertSame('Eugen Rochko', $result['account']['fullname']);
        $this->assertCount(1, $result['messages']);
        // The second request is the one of the statuses, without boosts and
        // replies.
        $uri = $client->getUri();
        $this->assertSame('mastodon.social', $uri->getHost());
        $this->assertSame('/api/v1/accounts/1/statuses', $uri->getPath());
        $this->assertStringContainsString('exclude_reblogs=true', $client->getLastRawRequest());
    }

    public function testFetchUnavailable(): void
    {
        $this->assertNull($this->mastodon()->fetch('@Gargron@mastodon.social'));
        $this->assertNull($this->mastodon()->fetch('Gargron'));
    }

    public function accountProvider(): array
    {
        return [
            ['@Gargron@mastodon.social', 'gargron@mastodon.social'],
            ['Gargron@mastodon.social', 'gargron@mastodon.social'],
            ['https://mastodon.social/@Gargron', 'gargron@mastodon.social'],
            ['https://mastodon.social/@Gargron/117564197289781943', 'gargron@mastodon.social'],
            ['@name@social.example.org:8443', 'name@social.example.org:8443'],
            ['Gargron', ''],
            ['@name@localhost', ''],
            ['@name@../admin', ''],
            ['', ''],
        ];
    }

    /**
     * @dataProvider accountProvider
     */
    public function testNormalizeAccount(string $account, string $expected): void
    {
        $this->assertSame($expected, MastodonFeed::normalizeAccount($account));
    }
}
