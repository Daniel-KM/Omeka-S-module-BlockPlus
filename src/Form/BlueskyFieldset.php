<?php declare(strict_types=1);

namespace BlockPlus\Form;

class BlueskyFieldset extends AbstractSocialFieldset
{
    protected $network = 'bluesky';

    protected $accountInfo = 'The handle of the account, for example "name.bsky.social", or its url.'; // @translate

    protected $accountPlaceholder = 'psl-univ.bsky.social';
}
