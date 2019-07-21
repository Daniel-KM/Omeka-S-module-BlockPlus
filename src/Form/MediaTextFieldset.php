<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\PartialSelect;
use BlockPlus\Form\Element\ThumbnailTypeSelect;
use Zend\Form\Element;
use Zend\Form\Fieldset;

class MediaTextFieldset extends Fieldset
{
    public function init()
    {
        // Attachments fields are managed separately.

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][thumbnail_type]',
            'type' => ThumbnailTypeSelect::class,
            'options' => [
                'label' => 'Thumbnail type', // @translate
            ],
            'attributes' => [
                'class' => 'chosen-select',
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][alignment]',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Thumbnail alignment', // @translate
                'value_options' => [
                    'left' => 'left', // @translate
                    'right' => 'right', // @translate
                ],
            ],
            'attributes' => [
                'class' => 'chosen-select',
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][show_title_option]',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Show attachment title', // @translate
                'value_options' => [
                    'item_title' => 'item title', // @translate
                    'file_name' => 'media title', // @translate
                    'no_title' => 'no title', // @translate
                ],
            ],
            'attributes' => [
                'class' => 'chosen-select',
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][caption_position]',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Captions position', // @translate
                'value_options' => [
                    'center' => 'center', // @translate
                    'left' => 'left', // @translate
                    'right' => 'right', // @translate
                ],
            ],
            'attributes' => [
                'class' => 'chosen-select',
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][heading]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Block title', // @translate
                'info' => 'Heading for the block, if any.', // @translate
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
            'name' => 'o:block[__blockIndex__][o:data][partial]',
            'type' => PartialSelect::class,
            'options' => [
                'label' => 'Partial to display', // @translate
                'info' => 'Partials are in folder "common/block-layout" of the theme and should start with "media-text".', // @translate
                'partial' => 'common/block-layout/media-text',
            ],
            'attributes' => [
                'class' => 'chosen-select',
            ],
        ]);
    }
}
