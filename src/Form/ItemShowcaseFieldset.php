<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\PartialSelect;
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
            'type' => PartialSelect::class,
            'options' => [
                'label' => 'Partial to display', // @translate
                'info' => 'Partials are in folder "common/block-layout" of the theme and should start with "item-showcase".', // @translate
                'partial' => 'common/block-layout/item-showcase',
            ],
            'attributes' => [
                'class' => 'chosen-select',
            ],
        ]);
    }
}
