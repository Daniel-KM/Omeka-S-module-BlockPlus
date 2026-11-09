<?php declare(strict_types=1);

namespace BlockPlusTest\Stdlib;

use BlockPlus\Stdlib\TwitterMirror;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Test as TestAdapter;
use PHPUnit\Framework\TestCase;

class TwitterMirrorTest extends TestCase
{
    const MIRROR = 'https://nitter.example.org';

    /**
     * A feed like the ones of Nitter.
     */
    protected function feed(): string
    {
        $mirror = self::MIRROR;
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <rss xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0">
              <channel>
                <atom:link href="{$mirror}/psl_univ/rss" rel="self" type="application/rss+xml" />
                <title>Université PSL / @psl_univ</title>
                <link>{$mirror}/psl_univ</link>
                <item>
                  <title>Pinned: L’Université PSL suspend son activité sur X.</title>
                  <dc:creator>@psl_univ</dc:creator>
                  <description><![CDATA[<p>L’Université PSL suspend son activité sur <a href="{$mirror}/search?q=%23X">#X</a>.<script>alert(1)</script></p><img src="https://pbs.twimg.com/media/a.jpg" onerror="alert(2)" />]]></description>
                  <pubDate>Thu, 16 Jan 2025 13:59:51 GMT</pubDate>
                  <link>{$mirror}/psl_univ/status/1879891317939138602#m</link>
                </item>
                <item>
                  <title>R to @psl_univ: @cdf1530 @ENS_ULM</title>
                  <dc:creator>@psl_univ</dc:creator>
                  <description><![CDATA[<p><a href="{$mirror}/cdf1530" title="Collège de France">@cdf1530</a></p>]]></description>
                  <pubDate>Thu, 16 Jan 2025 13:59:52 GMT</pubDate>
                  <link>{$mirror}/psl_univ/status/1879891321634300073#m</link>
                </item>
                <item>
                  <title>RT by @psl_univ: Forum des transitions</title>
                  <dc:creator>@ENS_ULM</dc:creator>
                  <description><![CDATA[<p>Forum des transitions <a href="{$mirror}/psl_univ">@psl_univ</a></p>]]></description>
                  <pubDate>Mon, 06 Jan 2025 15:29:02 GMT</pubDate>
                  <link>{$mirror}/ENS_ULM/status/1876289883310018973#m</link>
                </item>
                <item>
                  <title>Message without link</title>
                  <description><![CDATA[<p>Text</p>]]></description>
                  <link>{$mirror}/psl_univ</link>
                </item>
              </channel>
            </rss>
            XML;
    }

    protected function mirror(array $responses = []): TwitterMirror
    {
        $adapter = new TestAdapter();
        $adapter->setResponse(array_shift($responses) ?? "HTTP/1.1 404 Not Found\r\n\r\n");
        foreach ($responses as $response) {
            $adapter->addResponse($response);
        }
        return new TwitterMirror(new Client(null, ['adapter' => $adapter]));
    }

    public function testAccountIsParsed(): void
    {
        $result = $this->mirror()->parseFeed($this->feed(), self::MIRROR, ['limit' => 0]);
        $this->assertSame([
            'id' => 'psl_univ',
            'name' => 'psl_univ',
            'fullname' => 'Université PSL',
            'url' => 'https://x.com/psl_univ',
        ], $result['account']);
    }

    public function testMessagesWithoutRetweetsAndReplies(): void
    {
        $messages = $this->mirror()->parseFeed($this->feed(), self::MIRROR, ['limit' => 0])['messages'];
        $this->assertCount(1, $messages);
        $message = $messages[0];
        $this->assertSame('1879891317939138602', $message['id']);
        $this->assertSame('https://x.com/psl_univ/status/1879891317939138602', $message['url']);
        $this->assertSame(1737035991, $message['timestamp']);
        $this->assertTrue($message['is_pinned']);
        $this->assertFalse($message['is_retweet']);
    }

    public function testRetweetsAndRepliesOnDemand(): void
    {
        $messages = $this->mirror()->parseFeed($this->feed(), self::MIRROR, ['limit' => 0, 'retweet' => true, 'replies' => true])['messages'];
        $this->assertCount(3, $messages);
        $this->assertTrue($messages[1]['is_reply']);
        $this->assertTrue($messages[2]['is_retweet']);
        // The author of a retweet is the original one.
        $this->assertSame('https://x.com/ENS_ULM/status/1876289883310018973', $messages[2]['url']);
    }

    public function testLimit(): void
    {
        $messages = $this->mirror()->parseFeed($this->feed(), self::MIRROR, ['limit' => 2, 'retweet' => true, 'replies' => true])['messages'];
        $this->assertCount(2, $messages);
    }

    public function testContentIsPurifiedAndLinksPointToTwitter(): void
    {
        $content = $this->mirror()->parseFeed($this->feed(), self::MIRROR)['messages'][0]['content'];
        $this->assertStringNotContainsString('<script', $content);
        $this->assertStringNotContainsString('onerror', $content);
        $this->assertStringNotContainsString(self::MIRROR, $content);
        $this->assertStringContainsString('href="https://x.com/search?q=%23X"', $content);
        $this->assertStringContainsString('<img src="https://pbs.twimg.com/media/a.jpg"', $content);
        $this->assertStringContainsString('target="_blank"', $content);
    }

    public function testInvalidFeed(): void
    {
        $this->assertNull($this->mirror()->parseFeed('<html><title>Making sure you’re not a bot!</title></html>', self::MIRROR));
        $this->assertNull($this->mirror()->parseFeed('<rss><channel><title>No account</title></channel></rss>', self::MIRROR));
    }

    public function testFetchUsesTheFirstAvailableMirror(): void
    {
        $mirror = $this->mirror([
            "HTTP/1.1 403 Forbidden\r\nContent-Type: text/html\r\n\r\n<html>Blocked</html>",
            "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n<html><title>Making sure you’re not a bot!</title></html>",
            "HTTP/1.1 200 OK\r\nContent-Type: application/rss+xml\r\n\r\n" . strtr($this->feed(), [self::MIRROR => 'https://third.example.org']),
        ]);
        $result = $mirror->fetch('@psl_univ', ['https://first.example.org', 'https://second.example.org/', 'not an url', 'https://third.example.org'], ['limit' => 1]);
        $this->assertNotNull($result);
        $this->assertSame('https://third.example.org', $result['mirror']);
        $this->assertCount(1, $result['messages']);
    }

    public function testFetchWithoutAvailableMirror(): void
    {
        $this->assertNull($this->mirror()->fetch('psl_univ', ['https://first.example.org']));
        $this->assertNull($this->mirror()->fetch('', ['https://first.example.org']));
    }

    public function accountProvider(): array
    {
        return [
            ['psl_univ', 'psl_univ'],
            ['@psl_univ', 'psl_univ'],
            ['https://x.com/psl_univ', 'psl_univ'],
            ['https://twitter.com/psl_univ/status/1', 'psl_univ'],
            [' psl_univ ', 'psl_univ'],
            ['psl univ', ''],
            ['../admin', ''],
            ['', ''],
        ];
    }

    /**
     * @dataProvider accountProvider
     */
    public function testNormalizeAccount(string $account, string $expected): void
    {
        $this->assertSame($expected, TwitterMirror::normalizeAccount($account));
    }
}
