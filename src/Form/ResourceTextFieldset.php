<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class ResourceTextFieldset extends Fieldset
{
    public function init(): void
    {
        // Attachments fields are managed separately.

        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][thumbnail_type]',
                'type' => CommonElement\ThumbnailTypeSelect::class,
                'options' => [
                    'label' => 'Thumbnail type', // @translate
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
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
            ])
            ->add([
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
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][html]',
                'type' => Element\Textarea::class,
                'attributes' => [
                    'class' => 'block-html full wysiwyg',
                ],
            ])
        ;
    }
}
