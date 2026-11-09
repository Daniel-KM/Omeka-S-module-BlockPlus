<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use BlockPlus\Stdlib\MastodonFeed;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Display the last messages of a Mastodon account or of another server of the
 * Fediverse that implements the api of Mastodon.
 */
class Mastodon extends AbstractSocialBlock
{
    const NETWORK = 'mastodon';

    const PARTIAL_NAME = 'common/block-layout/mastodon';

    const FIELDSET = \BlockPlus\Form\MastodonFieldset::class;

    public function getLabel()
    {
        return 'Mastodon (Fediverse)'; // @translate
    }

    protected function normalizeAccount(string $account): string
    {
        return MastodonFeed::normalizeAccount($account);
    }

    protected function fetch(ServiceLocatorInterface $services, string $account, array $options): ?array
    {
        return (new MastodonFeed($services->get('Omeka\HttpClient')))->fetch($account, $options);
    }
}
