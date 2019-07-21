<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\PartialSelect;
use Zend\Form\Element;
use Zend\Form\Fieldset;

class SearchFormFieldset extends Fieldset
{
    public function init()
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][heading]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Block title', // @translate
                    'info' => 'Heading for the block, if any.', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][partial]',
                'type' => PartialSelect::class,
                'options' => [
                    'label' => 'Partial to display', // @translate
                    'info' => 'Partials are in folder "common/block-layout" of the theme and should start with "search-form".', // @translate
                    'partial' => 'common/block-layout/search-form',
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
