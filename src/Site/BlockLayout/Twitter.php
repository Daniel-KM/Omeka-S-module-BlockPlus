<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\Http\ClientStatic;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

class Twitter extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/twitter';

    /**
     * The user agent should be allowed by Twitter.
     */
    const USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/96.0';

    /**
     * The url to get the user id.
     */
    const URL_GRAPHQL = 'https://api.twitter.com/graphql/jMaTS-_Ea8vh9rpKggJbCQ/UserByScreenName';

    /**
     * The url to get the authorization bearer.
     */
    const URL_JS = 'https://abs.twimg.com/responsive-web/client-web/main.90f9e505.js';

    /**
     * @var string
     */
    protected $authorizationToken;

    /**
     * @var string
     */
    protected $guestToken;

    public function getLabel()
    {
        return 'Twitter'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $messenger = new Messenger;

        $data = $block->getData();

        if (empty($data['authorization'])) {
            $messenger->addWarning('No authorization token is set, so the default one is used.'); // @translate
            // Save the automatic token separately.
            $data['authorization_bearer'] = $this->getAuthorizationToken();
        } else {
            $this->authorizationToken = trim(str_ireplace('Bearer', '', $data['authorization']));
            // Save the token separately too for simplicity.
            $data['authorization_bearer'] = $this->authorizationToken;
        }

        $data['guest_token'] = $this->getGuestToken();

        $account = $data['account'] ?? '';
        if ($account) {
            $accountData = $this->fetchAccountData($account);
            if (empty($accountData)) {
                $messenger->addError(new Message('The Twitter account "%s" is not available.', $account)); // @translate
            } elseif (isset($accountData['error'])) {
                $messenger->addError(new Message('The Twitter account "%s" is not available: %s', $account, $accountData['error'])); // @translate
                $accountData = null;
            } else {
                $response = $this->fetchMessages($accountData);
                if (empty($response) || empty($response['globalObjects']['tweets'])) {
                    $messenger->addWarning(new Message('The Twitter account "%s" is available, but has no message currently, or the rate limit has been reached.', $account)); // @translate
                } else {
                    $messenger->addSuccess(new Message('The Twitter account "%s" is available and have messages.', $account)); // @translate
                }
            }
        } else {
            $messenger->addError(new Message('A Twitter account is required to fetch messages.', $account)); // @translate
            $accountData = null;
        }
        $data['account_data'] = $accountData;
        $block->setData($data);
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['twitter'];
        $blockFieldset = \BlockPlus\Form\TwitterFieldset::class;

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $vars = $block->data();

        $this->authorizationToken = empty($vars['authorization']) ? null : ($vars['authorization_bearer'] ?? null);
        $this->guestToken = empty($vars['guest_token']) ? null : $vars['guest_token'];
        $accountData = empty($vars['account_data'])
            ? $this->fetchAccountData(empty($vars['account']) ? null : $vars['account'])
            : $vars['account_data'];
        if (empty($accountData) || !empty($accountData['error'])) {
            $view->logger()->err(new Message(
                'The twitter block for page "%s" in site "%s" has no account set.', // @translate
                $block->page()->slug(),
                $block->page()->site()->slug()
            ));
            return '';
        }

        $messages = $this->fetchMessages($accountData, $vars['limit'], $vars['retweet'], $view);

        $vars = [
            'heading' => $vars['heading'],
            'account' => $accountData,
            'messages' => $messages,
        ];
        $template = $block->dataValue('template', self::PARTIAL_NAME);
        return $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        // The twitter message cannot be searched, because it cannot be updated.
        return $block->dataValue('title', '');
    }

    protected function fetchAccountData(?string $account, ?PhpRenderer $view = null): ?array
    {
        if (empty($account)) {
            return null;
        }

        // TODO Use twitter api v2.
        $response = $this->fetchTwitterUrl(
            self::URL_GRAPHQL,
            [
                'variables' => json_encode([
                    'screen_name' => (string) $account,
                    'withHighlightedLabel' => true,
                ]),
            ],
            $view
        );

        if (empty($response)) {
            return null;
        }

        if (!empty($response['error'])) {
            return $response;
        }

        return [
            'account' => $account,
            'id' => $response['data']['user']['rest_id'],
            'url' => 'https://twitter.com/' . $response['data']['user']['legacy']['screen_name'],
            'fullname' => $response['data']['user']['legacy']['screen_name'],
            'avatar' => [
                'img' => $response['data']['user']['legacy']['profile_image_url_https'],
            ],
        ];
    }

    protected function fetchMessages(array $accountData, $limit = 1, $retweet = false, PhpRenderer $view = null): array
    {
        if (empty($accountData['id']) || $limit <= 0) {
            return [];
        }

        $accountId = $accountData['id'];
        $account = $accountData['account'];

        // The process fetched directly the web page in order to avoid to add a
        // specific package and to avoid to create credential keys in Twitter.
        // It is possible only with the mobile page, that doesn't use ajax.
        // This is no more supported since December 2020, neither any direct
        // query without javascript.
        // @see https://stackoverflow.com/questions/65403350/how-can-i-scrape-twitter-now-that-they-require-javascript

        // See previous version for extraction of last tweets from the xml of
        // mobile.

        // The canonical url is lower case.
        // $url = 'https://mobile.twitter.com/' . mb_strtolower($account);
        // $html = @file_get_contents($url);

        $response = $this->fetchTwitterUrl(
            'https://api.twitter.com/2/timeline/profile/' . $accountId . '.json',
            [
                'include_profile_interstitial_type' => 1,
                'include_blocking' => 1,
                'include_blocked_by' => 1,
                'include_followed_by' => 1,
                'include_want_retweets' => $retweet,
                'include_mute_edge' => 1,
                'include_can_dm' => 1,
                'include_can_media_tag' => 1,
                'skip_status' => 1,
                'cards_platform' => 'Web-12',
                'include_cards' => 1,
                'include_ext_alt_text' => 1,
                'include_quote_count' => 1,
                'include_reply_count' => 1,
                'tweet_mode' => 'extended',
                'include_entities' => 1,
                'include_user_entities' => 1,
                'include_ext_media_color' => 1,
                'include_ext_media_availability' => 1,
                'send_error_codes' => 1,
                'simple_quoted_tweet' => 1,
                'include_tweet_replies' => 0,
                'count' => $limit,
                'userId' => $accountId,
                'ext' => 'mediaStats,highlightedLabel',
            ],
            $view
        );

        if (empty($response) || empty($response['timeline']['instructions'][0]['addEntries']['entries'])) {
            return [];
        }

        if (!$view) {
            return $response;
        }

        $escape = $view->plugin('escapeHtml');
        $baseUrl = 'https://twitter.com/';

        $result = [];

        // Tweets are unordered in the main list, so use the sort index if any.
        // When there is a limit, there may be no sort index and the tweets are
        // ordered, but it may not be the case when there is no limit.
        $first = 0;
        $entries = array_keys($response['globalObjects']['tweets']);
        foreach (array_slice($response['timeline']['instructions'][0]['addEntries']['entries'], 0, $limit) as $id => $entry) {
            if (empty($entry['sortIndex']) || empty($response['globalObjects']['tweets'][$entry['sortIndex']])) {
                // There is a limit, so tweets are ordered.
                $tweet = $response['globalObjects']['tweets'][$entries[$first++]] ?? null;
                if (empty($tweet)) {
                    continue;
                }
            } else {
                $tweet = $response['globalObjects']['tweets'][$entry['sortIndex']];
            }
            if (empty($tweet['full_text']) && empty($tweet['text'])) {
                continue;
            }

            $text = $tweet['full_text'] ?? $tweet['text'];

            $replace = [];
            foreach ($tweet['entities'] ?? [] as $entityType => $entities) {
                foreach ($entities as $entity) {
                    switch ($entityType) {
                        case 'hashtags':
                            $replace['#' . $entity['text']] = sprintf(
                                '<a href="%s" rel="nofollow noopener" target="_blank">%s</a>',
                                $baseUrl . 'hashtag/' . rawurlencode($entity['text']),
                                $escape('#' . $entity['text'])
                            );
                            break;
                        case 'user_mentions':
                            $replace['@' . $entity['screen_name']] = sprintf(
                                '<a href="%s" rel="nofollow noopener" target="_blank">%s</a>',
                                $baseUrl . $escape($entity['screen_name']),
                                $escape('@' . $entity['screen_name'])
                            );
                            break;
                        case 'urls':
                            $replace[$entity['url']] = sprintf(
                                '<a href="%s" rel="nofollow noopener" target="_blank">%s</a>',
                                $entity['url'],
                                $entity['expanded_url']
                            );
                            break;
                        case 'media':
                            break;
                        default:
                            continue 2;
                    }
                }
            }
            $message = str_replace(array_keys($replace), array_values($replace), $text);
            $content = [
                'tweet' => $tweet,
                'id' => $id,
                'url' => $baseUrl . $account, '/status/' . $id,
                'created_at' => $tweet['created_at'],
                'timestamp' => (new \DateTime($tweet['created_at']))->format('U'),
                'content' => $message,
            ];
            $result[] = $content;
        }

        return $result;
    }

    protected function fetchTwitterUrl(string $url, array $query = [], ?PhpRenderer $view = null): array
    {
        $headers = [
            'User-Agent' => self::USER_AGENT,
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getAuthorizationToken(),
            'x-guest-token' => $this->getGuestToken(),
            'x-twitter-active-user' => 'no',
            'x-twitter-client-language' => $view ? ($view->siteSetting('locale') ?: $view->setting('locale')) : 'en',
        ];
        $response = ClientStatic::get($url, $query, $headers);
        $body = $response->getBody();
        if (empty($body)) {
            return [];
        }

        $body = json_decode($body, true);
        if (isset($body['errors'][0]['message'])) {
            $this->error = $body['errors'][0]['message'];
            return ['error' => $body['errors'][0]['message']];
        }

        return $body;
    }

    protected function getAuthorizationToken(): ?string
    {
        if (empty($this->authorizationToken)) {
            $response = ClientStatic::get(self::URL_JS);
            $body = $response->getBody();
            $matches = [];
            preg_match('/s=\"AAAAA[^\"]+\"/', $body, $matches, PREG_OFFSET_CAPTURE);
            $this->authorizationToken = empty($matches[0][0]) ? null : mb_substr($matches[0][0], 3, -1);
        }
        return $this->authorizationToken;
    }

    protected function getGuestToken()
    {
        // TODO Disable when the authorization token is a dev one.
        if (empty($this->guestToken)) {
            $baseUrl = 'https://api.twitter.com/1.1/guest/activate.json';
            $response = ClientStatic::post(
                $baseUrl,
                ['1' => '1'],
                [
                    'User-Agent' => self::USER_AGENT,
                    // 'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->getAuthorizationToken(),
                ]
            );
            $body = $response->getBody();
            if ($body) {
                $body = json_decode($body, true);
                if ($body) {
                    $this->guestToken = $body['guest_token'] ?? null;
                }
            }
        }
        return $this->guestToken;
    }
}
