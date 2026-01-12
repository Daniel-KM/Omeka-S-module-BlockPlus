<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class ButtonsFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][buttons]',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'label' => 'Buttons', // @translate
                    'value_options' => [
                        'download' => 'Download', // @translate
                        'print' => 'Print', // @translate
                        'email' => 'Share by email', // @translate
                        'facebook' => 'Share on Facebook', // @translate
                        'instagram' => 'Share on Instagram', // @translate
                        'linkedin' => 'Share on LinkedIn', // @translate
                        'pinterest' => 'Share on Pinterest', // @translate
                        'twitter' => 'Share on Twitter (now X)', // @translate
                    ],
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][display_as_button]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Single button', // @translate
                    'info' => "Check to display all buttons as a single one", // @translate
                ],
            ])
        ;
    }
}
