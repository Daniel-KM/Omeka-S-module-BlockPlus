<?php declare(strict_types=1);

namespace BlockPlus\Form;

class MastodonFieldset extends AbstractSocialFieldset
{
    protected $network = 'mastodon';

    protected $accountInfo = 'The account with its server, for example "@name@mastodon.social", or its url. Other servers of the Fediverse that implement the api of Mastodon can be used.'; // @translate

    protected $accountPlaceholder = '@name@mastodon.social';
}
