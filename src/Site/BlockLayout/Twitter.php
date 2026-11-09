<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use BlockPlus\Stdlib\TwitterMirror;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Representation\SitePageBlockRepresentation;

/**
 * Display the last messages of a Twitter (X) account.
 *
 * The messages are fetched from the rss feed of a mirror site (Nitter), listed
 * in the main settings.
 */
class Twitter extends AbstractSocialBlock
{
    const NETWORK = 'twitter';

    const PARTIAL_NAME = 'common/block-layout/twitter';

    const FIELDSET = \BlockPlus\Form\TwitterFieldset::class;

    /**
     * The option "retweet" is kept for compatibility with existing blocks.
     */
    const DATA_KEYS = ['account', 'limit', 'retweet', 'replies'];

    public function getLabel()
    {
        return 'Twitter'; // @translate
    }

    protected function normalizeAccount(string $account): string
    {
        return TwitterMirror::normalizeAccount($account);
    }

    protected function options(SitePageBlockRepresentation $block): array
    {
        return [
            'limit' => (int) $block->dataValue('limit', 1),
            'retweet' => (bool) $block->dataValue('retweet', false),
            'replies' => (bool) $block->dataValue('replies', false),
        ];
    }

    protected function fetch(ServiceLocatorInterface $services, string $account, array $options): ?array
    {
        $mirrors = $services->get('Omeka\Settings')->get('blockplus_twitter_mirrors')
            ?: $services->get('Config')['blockplus']['settings']['blockplus_twitter_mirrors'];
        return (new TwitterMirror($services->get('Omeka\HttpClient')))->fetch($account, (array) $mirrors, $options);
    }
}
