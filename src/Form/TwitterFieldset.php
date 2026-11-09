<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class TwitterFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][account]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Account', // @translate
                    'info' => 'The messages are fetched from a mirror site set in the main settings.', // @translate
                ],
                'attributes' => [
                    'id' => 'twitter-account',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][limit]',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Number of messages', // @translate
                ],
                'attributes' => [
                    'id' => 'twitter-limit',
                    'min' => '0',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][retweet]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Display retweets', // @translate
                ],
                'attributes' => [
                    'id' => 'twitter-retweet',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][replies]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Display replies', // @translate
                ],
                'attributes' => [
                    'id' => 'twitter-replies',
                ],
            ])
        ;
    }
}
