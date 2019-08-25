<?php
namespace BlockPlus\Form;

use Zend\Form\Element;
use Zend\Form\Fieldset;

class SeparatorFieldset extends Fieldset
{
    public function init()
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
