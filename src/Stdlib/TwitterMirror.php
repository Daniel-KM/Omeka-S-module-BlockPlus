<?php declare(strict_types=1);

namespace BlockPlus\Stdlib;

/**
 * Get the last messages of a Twitter (X) account from a mirror site (Nitter).
 *
 * The api of Twitter requires a paid account, so the messages are fetched from
 * the rss feed of a Nitter instance. The instances are often unavailable, so
 * they are checked in turn until one returns a valid feed. The links to the
 * mirror are replaced by links to Twitter.
 */
class TwitterMirror extends AbstractSocialFeed
{
    const BASE_URL = 'https://x.com';

    /**
     * Fetch the account and its last messages from the first available mirror.
     *
     * @param string[] $mirrors Base urls of the Nitter instances.
     * @param array $options Keys "limit" (int), "retweet" and "replies" (bool).
     * @return array|null Associative array with keys "account", "messages" and
     *   "mirror", or null when no mirror is available.
     */
    public function fetch(string $account, array $mirrors, array $options = []): ?array
    {
        $account = self::normalizeAccount($account);
        if ($account === '') {
            return null;
        }
        foreach ($mirrors as $mirror) {
            $mirror = rtrim(trim((string) $mirror), '/');
            if (!preg_match('~^https?://[^/\s]+$~', $mirror)) {
                continue;
            }
            $xml = $this->fetchFeed($mirror . '/' . rawurlencode($account) . '/rss');
            if ($xml === null) {
                continue;
            }
            $result = $this->parseFeed($xml, $mirror, $options);
            if ($result !== null) {
                $result['mirror'] = $mirror;
                return $result;
            }
        }
        return null;
    }

    /**
     * Get the screen name of an account from a name, a "@name" or an url.
     */
    public static function normalizeAccount(string $account): string
    {
        $account = trim($account);
        if (preg_match('~^https?://[^/]+/([^/?#]+)~', $account, $matches)) {
            $account = $matches[1];
        }
        $account = ltrim($account, '@');
        return preg_match('~^\w{1,50}$~', $account) ? $account : '';
    }

    /**
     * Convert the rss feed of a Nitter instance into an account and messages.
     *
     * @see \BlockPlus\Stdlib\AbstractSocialFeed for the structure.
     */
    public function parseFeed(string $xml, string $mirror, array $options = []): ?array
    {
        $options += [
            'limit' => 1,
            'retweet' => false,
            'replies' => false,
        ];

        $previous = libxml_use_internal_errors(true);
        $rss = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
        libxml_use_internal_errors($previous);
        if (!$rss || !isset($rss->channel)) {
            return null;
        }

        $channel = $rss->channel;
        // The title is "Full name / @account".
        $title = trim((string) $channel->title);
        if (!preg_match('~^(.*?)\s*/\s*@(\w+)$~u', $title, $matches)) {
            return null;
        }
        $account = [
            'id' => $matches[2],
            'name' => $matches[2],
            'fullname' => $matches[1] === '' ? $matches[2] : $matches[1],
            'url' => self::BASE_URL . '/' . $matches[2],
        ];

        $limit = max(0, (int) $options['limit']);
        $messages = [];
        foreach ($channel->item as $item) {
            if ($limit && count($messages) >= $limit) {
                break;
            }
            $itemTitle = trim((string) $item->title);
            $isRetweet = strpos($itemTitle, 'RT by @') === 0;
            $isReply = strpos($itemTitle, 'R to @') === 0;
            if (($isRetweet && !$options['retweet']) || ($isReply && !$options['replies'])) {
                continue;
            }
            if (!preg_match('~/status/(\d+)~', (string) $item->link, $matches)) {
                continue;
            }
            $id = $matches[1];
            $author = self::normalizeAccount((string) $item->children('http://purl.org/dc/elements/1.1/')->creator) ?: $account['name'];
            $messages[] = [
                'id' => $id,
                'url' => self::BASE_URL . '/' . $author . '/status/' . $id,
            ] + $this->messageDate((string) $item->pubDate) + [
                'content' => $this->cleanContent((string) $item->description, $mirror),
                'author' => $author,
                'author_url' => self::BASE_URL . '/' . $author,
                'is_retweet' => $isRetweet,
                'is_reply' => $isReply,
                'is_pinned' => strpos($itemTitle, 'Pinned:') === 0,
            ];
        }

        return [
            'account' => $account,
            'messages' => $messages,
        ];
    }

    protected function fetchFeed(string $url): ?string
    {
        $body = $this->getBody($url, [], 'application/rss+xml, application/xml');
        return $body !== null && strpos($body, '<rss') !== false ? $body : null;
    }

    /**
     * Replace the links to the mirror and keep only a safe html.
     */
    protected function cleanContent(string $html, string $mirror): string
    {
        // The links to the mirror are replaced, but not the urls of the images
        // proxied by it ("/pic/").
        $html = preg_replace(
            '~(href=["\'])' . preg_quote($mirror, '~') . '/(?!pic/)~',
            '$1' . self::BASE_URL . '/',
            $html
        );
        return $this->purify($html);
    }
}
