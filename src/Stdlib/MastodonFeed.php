<?php declare(strict_types=1);

namespace BlockPlus\Stdlib;

/**
 * Get the last messages of a Mastodon account via the public api.
 *
 * The api of Mastodon is implemented by most of the servers of the Fediverse
 * (Mastodon, Pleroma, Akkoma, Friendica, GoToSocial when public, etc.). The
 * account is set as "@name@server", so the api of its own server is requested.
 *
 * @see https://docs.joinmastodon.org/methods/accounts/#statuses
 */
class MastodonFeed extends AbstractSocialFeed
{
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
        [$name, $server] = explode('@', $account, 2);
        $api = 'https://' . $server . '/api/v1/accounts';

        $profile = $this->getJson($api . '/lookup', ['acct' => $name]);
        if (empty($profile['id']) || !is_scalar($profile['id'])) {
            return null;
        }

        $statuses = $this->getJson($api . '/' . rawurlencode((string) $profile['id']) . '/statuses', [
            'limit' => min(40, max(1, (int) $options['limit'])),
            'exclude_replies' => $options['replies'] ? 'false' : 'true',
            'exclude_reblogs' => $options['reposts'] ? 'false' : 'true',
        ]);
        if ($statuses === null) {
            return null;
        }

        return $this->parseStatuses($profile, $server, $statuses, $options);
    }

    /**
     * Get the account as "name@server" from "@name@server" or an url.
     */
    public static function normalizeAccount(string $account): string
    {
        $account = trim($account);
        if (preg_match('~^https?://([^/?#]+)/@([^/?#@]+)~', $account, $matches)) {
            $account = $matches[2] . '@' . $matches[1];
        }
        $account = mb_strtolower(ltrim($account, '@'));
        return preg_match('~^\w[\w.-]{0,63}@([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{0,61}[a-z0-9](:\d{1,5})?$~', $account)
            ? $account
            : '';
    }

    public function parseStatuses(array $profile, string $server, array $statuses, array $options = []): array
    {
        $options += [
            'limit' => 1,
            'reposts' => false,
            'replies' => false,
        ];
        $account = [
            'id' => (string) $profile['id'],
            'name' => ($profile['acct'] ?? '') . '@' . $server,
            'fullname' => ($profile['display_name'] ?? '') ?: ($profile['username'] ?? $profile['acct'] ?? ''),
            'url' => $this->safeUrl($profile['url'] ?? '') ?? ('https://' . $server . '/@' . ($profile['username'] ?? '')),
        ];

        $limit = max(0, (int) $options['limit']);
        $messages = [];
        foreach ($statuses as $status) {
            if ($limit && count($messages) >= $limit) {
                break;
            }
            if (!is_array($status) || empty($status['id'])) {
                continue;
            }
            // A boost (reblog) contains the original status.
            $isRepost = !empty($status['reblog']);
            $isReply = !empty($status['in_reply_to_id']);
            if (($isRepost && !$options['reposts']) || ($isReply && !$options['replies'])) {
                continue;
            }
            $original = $isRepost ? $status['reblog'] : $status;
            $messages[] = [
                'id' => (string) $status['id'],
                'url' => $this->safeUrl($original['url'] ?? '') ?? $account['url'],
            ] + $this->messageDate($original['created_at'] ?? null) + [
                'content' => $this->purify($this->statusToHtml($original)),
                'author' => $original['account']['acct'] ?? $account['name'],
                'author_url' => $this->safeUrl($original['account']['url'] ?? '') ?? $account['url'],
                'is_retweet' => $isRepost,
                'is_reply' => $isReply,
                'is_pinned' => !empty($status['pinned']),
            ];
        }

        return [
            'account' => $account,
            'messages' => $messages,
        ];
    }

    protected function statusToHtml(array $status): string
    {
        $html = '';
        // A content warning is displayed before the content.
        if (!empty($status['spoiler_text'])) {
            $html .= '<p>' . $this->escape((string) $status['spoiler_text']) . '</p>';
        }
        $html .= (string) ($status['content'] ?? '');
        foreach ($status['media_attachments'] ?? [] as $media) {
            if (($media['type'] ?? '') === 'image' && !empty($media['preview_url'])) {
                $html .= '<img src="' . $this->escape($media['preview_url']) . '" alt="' . $this->escape((string) ($media['description'] ?? '')) . '">';
            }
        }
        return $html;
    }

    protected function safeUrl($url): ?string
    {
        return is_string($url) && preg_match('~^https?://~', $url) ? $url : null;
    }
}
