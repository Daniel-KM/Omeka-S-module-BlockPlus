<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element\TemplateSelect;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class TwitterFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][heading]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Block title', // @translate
                ],
                'attributes' => [
                    'id' => 'twitter-heading',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][account]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Account', // @translate
                ],
                'attributes' => [
                    'id' => 'twitter-account',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][authorization]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Authorization token (bearer)', // @translate
                    'info' => 'You may need to set a token, not necessarily the account one. It may be an anonymous one.', // @translate
                    'documentation' => 'https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus#Twitter',
                ],
                'attributes' => [
                    'id' => 'twitter-authorization',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][api]',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Twitter api version', // @translate
                    'info' => 'According to Twitter, you may require to create an app inside dev account. In some cases, api 1.1 doesn’t require it.', // @translate
                    'value_options' => [
                        '1.1-home' => '1.1 (home timeline)', // @translate
                        '1.1' => '1.1',
                        '2.0' => '2.0',
                    ],
                ],
                'attributes' => [
                    'id' => 'twitter-api',
                    'value' => '1.1',
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
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "twitter".', // @translate
                    'template' => 'common/block-layout/twitter',
                ],
                'attributes' => [
                    'id' => 'twitter-template',
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
