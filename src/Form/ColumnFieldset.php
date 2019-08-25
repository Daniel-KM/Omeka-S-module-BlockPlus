<?php
namespace BlockPlus\Form;

use Zend\Form\Element;
use Zend\Form\Fieldset;

class ColumnFieldset extends Fieldset
{
    public function init()
    {
        // TODO Use radio, but they are not automatically populated, except last one.

        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][type]',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Type', // @translate
                    'empty_option' => '',
                    'value_options' => [
                        'start' => 'New column', // @translate
                        'inter' => 'End previous and start new', // @translate
                        'end' => 'End column', // @translate
                    ],
                ],
                'attributes' => [
                    'required' => true,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select below…', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][tag]',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Tag for new column', // @translate
                    'value_options' => [
                        'div' => 'div', // @translate
                        'aside' => 'aside', // @translate
                    ],
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][class]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'CSS class', // @translate
                    'info' => 'Set the classes according to the css of your theme. Only classes "main" and "column" are managed.', // @translate
                ],
                'attributes' => [
                    'placeholder' => 'main column align-left',
                ],
            ]);

        // Set the value of the optional radio.
        $this
            ->get('o:block[__blockIndex__][o:data][tag]')
            ->setValue('div');
    }
}
