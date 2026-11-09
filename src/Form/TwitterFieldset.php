<?php declare(strict_types=1);

namespace BlockPlus\Form;

class TwitterFieldset extends AbstractSocialFieldset
{
    protected $network = 'twitter';

    protected $accountInfo = 'The messages are fetched from a mirror site set in the main settings.'; // @translate

    protected $accountPlaceholder = 'psl_univ';

    protected $repostKey = 'retweet';

    protected $repostLabel = 'Display retweets'; // @translate
}
