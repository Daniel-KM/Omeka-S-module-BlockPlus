<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class HeadingFieldset extends Fieldset
{
    public function init(): void
    {
        // Attachments fields are managed separately.

        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][text]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Content', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][level]',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Level', // @translate
                    'value_options' => [
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                        '6' => '6',
                    ],
                ],
                'attributes' => [
                    'value' => '3',
                ],
            ])
        ;
    }
}
