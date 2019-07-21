<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\PartialSelect;
use Zend\Form\Element;
use Zend\Form\Fieldset;

class EmbedTextFieldset extends Fieldset
{
    public function init()
    {
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][heading]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Block title', // @translate
                'info' => 'Heading for the block, if any.', // @translate
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][embeds]',
            'type' => Element\Url::class,
            'options' => [
                'label' => 'Embedded url', // @translate
                'info' => 'The standard "oEmbed" normalizes the integration of external resources. Main third parties are supported. Set the url to be used for the iframe.', // @translate
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][html]',
            'type' => Element\Textarea::class,
            'attributes' => [
                'class' => 'block-html full wysiwyg',
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][alignment]',
            'type' => Element\Radio::class,
            'options' => [
                'label' => 'Embedded alignment', // @translate
                'value_options' => [
                    'left' => 'left', // @translate
                    'right' => 'right', // @translate
                ],
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][show_title_option]',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Show title', // @translate
                'value_options' => [
                    'title' => 'title', // @translate
                    'no_title' => 'no title', // @translate
                ],
            ],
        ]);
        $this->add([
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
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][link_text]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Link text', // @translate
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][link_url]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Link url', // @translate
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][partial]',
            'type' => PartialSelect::class,
            'options' => [
                'label' => 'Partial to display', // @translate
                'info' => 'Partials are in folder "common/block-layout" of the theme and should start with "embed-text".', // @translate
                'partial' => 'common/block-layout/embed-text',
            ],
            'attributes' => [
                'class' => 'chosen-select',
            ],
        ]);
    }
}
