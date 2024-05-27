<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
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
                        'email' => 'Share by email', // @translate
                        'facebook' => 'Share on Facebook', // @translate
                        'pinterest' => 'Share on Pinterest', // @translate
                        'twitter' => 'Share on Twitter', // @translate
                    ],
                ],
            ])
        ;
    }
}
