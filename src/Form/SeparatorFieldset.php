<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SeparatorFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][class]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Class', // @translate
                    'info' => 'Set the class or the name of the separator. Default is "transparent". Other ones depends on the theme.', // @translate
                ],
                'attributes' => [
                    'placeholder' => 'transparent',
                ],
            ]);
    }
}
