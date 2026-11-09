<?php declare(strict_types=1);

namespace BlockPlus\Stdlib;

/**
 * Get the last messages of a Bluesky account via the public api.
 *
 * The public api of Bluesky (AT Protocol) does not require authentication.
 *
 * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-author-feed
 */
class BlueskyFeed extends AbstractSocialFeed
{
    const API = 'https://public.api.bsky.app/xrpc';

    const BASE_URL = 'https://bsky.app';

    /**
     * @param array $options Keys "limit" (int), "reposts" and "replies" (bool).
     */
    public function fetch(string $account, array $options = []): ?array
    {
        $options += [
            'limit' => 1,
            'reposts' => false,
            'replies' => false,
        ];
        $account = self::normalizeAccount($account);
        if ($account === '') {
            return null;
        }

        $profile = $this->getJson(self::API . '/app.bsky.actor.getProfile', ['actor' => $account]);
        if (empty($profile['did']) || empty($profile['handle'])) {
            return null;
        }

        $limit = max(1, (int) $options['limit']);
        // The reposts are filtered here, so get more posts.
        $feed = $this->getJson(self::API . '/app.bsky.feed.getAuthorFeed', [
            'actor' => $profile['did'],
            'limit' => min(100, $options['reposts'] ? $limit : $limit * 3),
            'filter' => $options['replies'] ? 'posts_with_replies' : 'posts_no_replies',
        ]);
        if (!isset($feed['feed']) || !is_array($feed['feed'])) {
            return null;
        }

        return $this->parseFeed($profile, $feed['feed'], $options);
    }

    /**
     * Get a handle ("name.bsky.social") or a did from a handle or an url.
     */
    public static function normalizeAccount(string $account): string
    {
        $account = trim($account);
        if (preg_match('~^https?://bsky\.app/profile/([^/?#]+)~', $account, $matches)) {
            $account = $matches[1];
        }
        $account = mb_strtolower(ltrim($account, '@'));
        if (preg_match('~^did:(plc|web):[a-z0-9.:%-]+$~', $account)) {
            return $account;
        }
        return preg_match('~^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{0,61}[a-z0-9]$~', $account)
            ? $account
            : '';
    }

    public function parseFeed(array $profile, array $feed, array $options = []): array
    {
        $options += [
            'limit' => 1,
            'reposts' => false,
            'replies' => false,
        ];
        $account = [
            'id' => $profile['did'],
            'name' => $profile['handle'],
            'fullname' => ($profile['displayName'] ?? '') ?: $profile['handle'],
            'url' => self::BASE_URL . '/profile/' . $profile['handle'],
        ];

        $limit = max(0, (int) $options['limit']);
        $messages = [];
        foreach ($feed as $item) {
            if ($limit && count($messages) >= $limit) {
                break;
            }
            $post = $item['post'] ?? null;
            $record = $post['record'] ?? null;
            if (empty($post['uri']) || !is_array($record)) {
                continue;
            }
            $reason = $item['reason']['$type'] ?? '';
            $isRepost = $reason === 'app.bsky.feed.defs#reasonRepost';
            $isReply = !empty($record['reply']);
            if (($isRepost && !$options['reposts']) || ($isReply && !$options['replies'])) {
                continue;
            }
            $author = $post['author']['handle'] ?? $account['name'];
            $rkey = substr((string) strrchr($post['uri'], '/'), 1);
            $messages[] = [
                'id' => $rkey,
                'url' => self::BASE_URL . '/profile/' . $author . '/post/' . $rkey,
            ] + $this->messageDate($record['createdAt'] ?? null) + [
                'content' => $this->purify(
                    '<p>' . $this->textToHtml((string) ($record['text'] ?? ''), $record['facets'] ?? []) . '</p>'
                    . $this->embedToHtml($post['embed'] ?? [])
                ),
                'author' => $author,
                'author_url' => self::BASE_URL . '/profile/' . $author,
                'is_retweet' => $isRepost,
                'is_reply' => $isReply,
                'is_pinned' => $reason === 'app.bsky.feed.defs#reasonPin',
            ];
        }

        return [
            'account' => $account,
            'messages' => $messages,
        ];
    }

    /**
     * Convert the text and its facets (links, mentions, tags) into html.
     *
     * The positions of the facets are counted in bytes of the utf-8 text.
     */
    protected function textToHtml(string $text, array $facets): string
    {
        usort($facets, fn ($a, $b) => ($a['index']['byteStart'] ?? 0) <=> ($b['index']['byteStart'] ?? 0));
        $html = '';
        $position = 0;
        $length = strlen($text);
        foreach ($facets as $facet) {
            $start = (int) ($facet['index']['byteStart'] ?? -1);
            $end = (int) ($facet['index']['byteEnd'] ?? -1);
            if ($start < $position || $end <= $start || $end > $length) {
                continue;
            }
            $href = $this->facetUrl($facet['features'][0] ?? []);
            if (!$href) {
                continue;
            }
            $html .= $this->escape(substr($text, $position, $start - $position))
                . '<a href="' . $this->escape($href) . '">' . $this->escape(substr($text, $start, $end - $start)) . '</a>';
            $position = $end;
        }
        $html .= $this->escape(substr($text, $position));
        return nl2br($html, false);
    }

    protected function facetUrl(array $feature): ?string
    {
        switch ($feature['$type'] ?? '') {
            case 'app.bsky.richtext.facet#link':
                return $feature['uri'] ?? null;
            case 'app.bsky.richtext.facet#mention':
                return isset($feature['did']) ? self::BASE_URL . '/profile/' . $feature['did'] : null;
            case 'app.bsky.richtext.facet#tag':
                return isset($feature['tag']) ? self::BASE_URL . '/hashtag/' . rawurlencode($feature['tag']) : null;
            default:
                return null;
        }
    }

    /**
     * Convert the images and the external link of a post into html.
     */
    protected function embedToHtml(array $embed): string
    {
        // A quote with media contains the media separately.
        if (($embed['$type'] ?? '') === 'app.bsky.embed.recordWithMedia#view') {
            $embed = $embed['media'] ?? [];
        }
        $html = '';
        foreach ($embed['images'] ?? [] as $image) {
            if (!empty($image['thumb'])) {
                $html .= '<img src="' . $this->escape($image['thumb']) . '" alt="' . $this->escape($image['alt'] ?? '') . '">';
            }
        }
        if (!empty($embed['external']['uri'])) {
            $html .= '<p><a href="' . $this->escape($embed['external']['uri']) . '">'
                . $this->escape(($embed['external']['title'] ?? '') ?: $embed['external']['uri']) . '</a></p>';
        }
        return $html;
    }
}
