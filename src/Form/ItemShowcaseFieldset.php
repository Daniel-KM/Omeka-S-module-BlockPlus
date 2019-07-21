<?php
namespace BlockPlus\Form;

use Zend\Form\Element;
use Zend\Form\Fieldset;

class ItemShowcaseFieldset extends Fieldset
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
            'name' => 'o:block[__blockIndex__][o:data][partial]',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Partial to display', // @translate
                'info' => 'The partials are in folder "common/block-layout" of the theme and should start with "item-showcase".', // @translate
                'value_options' => [
                    '' => 'Default', // @translate
                ],
            ],
            'attributes' => [
                'class' => 'chosen-select',
            ],
        ]);
    }
}
