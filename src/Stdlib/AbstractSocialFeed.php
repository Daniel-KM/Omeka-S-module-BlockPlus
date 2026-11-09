<?php declare(strict_types=1);

namespace BlockPlus\Stdlib;

use Laminas\Http\Client as HttpClient;

/**
 * Get the last messages of an account on a social network.
 *
 * The result is an array with the keys "account" and "messages". The account
 * has the keys "id", "name", "fullname" and "url". Each message has the keys
 * "id", "url", "created_at", "timestamp", "content" (purified html), "author",
 * "author_url", "is_retweet" (a repost or a boost), "is_reply" and "is_pinned".
 */
abstract class AbstractSocialFeed
{
    /**
     * Some servers reject requests without user agent.
     */
    const USER_AGENT = 'Omeka S (module BlockPlus)';

    /**
     * Timeout in seconds for each request, that is done during rendering.
     */
    const TIMEOUT = 8;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var \HTMLPurifier|null
     */
    protected $purifier;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Get the identifier of an account from a name, a handle or an url.
     *
     * @return string Empty string when the account is invalid.
     */
    abstract public static function normalizeAccount(string $account): string;

    protected function getBody(string $url, array $query = [], string $accept = '*/*'): ?string
    {
        try {
            $response = $this->httpClient
                ->resetParameters()
                ->setUri($url)
                ->setOptions(['timeout' => self::TIMEOUT])
                ->setHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => $accept])
                ->setParameterGet($query)
                ->send();
        } catch (\Throwable $e) {
            return null;
        }
        return $response->isSuccess() ? $response->getBody() : null;
    }

    protected function getJson(string $url, array $query = []): ?array
    {
        $body = $this->getBody($url, $query, 'application/json');
        if ($body === null) {
            return null;
        }
        $json = json_decode($body, true);
        return is_array($json) ? $json : null;
    }

    protected function messageDate(?string $date): array
    {
        $date = $date ? date_create($date) : null;
        return [
            'created_at' => $date ? $date->format('c') : null,
            'timestamp' => $date ? (int) $date->format('U') : null,
        ];
    }

    protected function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * Keep only a safe html: paragraphs, links and images.
     */
    protected function purify(string $html): string
    {
        if (!$this->purifier) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Cache.DefinitionImpl', null);
            $config->set('HTML.Allowed', 'p,br,a[href|title],img[src|alt],span');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
            $config->set('HTML.TargetBlank', true);
            $config->set('HTML.Nofollow', true);
            $this->purifier = new \HTMLPurifier($config);
        }
        return trim($this->purifier->purify($html));
    }
}
