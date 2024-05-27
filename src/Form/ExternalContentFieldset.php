<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class ExternalContentFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][embeds]',
                'type' => Element\Url::class,
                'options' => [
                    'label' => 'Url (oEmbed or iframe)', // @translate
                    'info' => 'The standard "oEmbed" normalizes the integration of external resources. Main third parties are supported. Set the url to be used for the iframe.', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][show_title_option]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Show title', // @translate
                    'value_options' => [
                        'title' => 'title', // @translate
                        'no_title' => 'no title', // @translate
                    ],
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][caption_position]',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Captions position', // @translate
                    'value_options' => [
                        'center' => 'center', // @translate
                        'left' => 'left', // @translate
                        'right' => 'right', // @translate
                    ],
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][html]',
                'type' => Element\Textarea::class,
                'attributes' => [
                    'class' => 'block-html full wysiwyg',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][link_text]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Link text', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][link_url]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Link url', // @translate
                ],
            ])
        ;
    }
}
