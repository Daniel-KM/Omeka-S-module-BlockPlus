<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use BlockPlus\Stdlib\BlueskyFeed;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Display the last messages of a Bluesky account via the public api.
 */
class Bluesky extends AbstractSocialBlock
{
    const NETWORK = 'bluesky';

    const PARTIAL_NAME = 'common/block-layout/bluesky';

    const FIELDSET = \BlockPlus\Form\BlueskyFieldset::class;

    public function getLabel()
    {
        return 'Bluesky'; // @translate
    }

    protected function normalizeAccount(string $account): string
    {
        return BlueskyFeed::normalizeAccount($account);
    }

    protected function fetch(ServiceLocatorInterface $services, string $account, array $options): ?array
    {
        return (new BlueskyFeed($services->get('Omeka\HttpClient')))->fetch($account, $options);
    }
}
