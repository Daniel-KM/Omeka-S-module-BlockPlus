<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element\OptionalRadio;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class DivisionFieldset extends Fieldset
{
    public function init(): void
    {
        // TODO Use radio, but they are not automatically populated, except last one.

        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][type]',
                'type' => OptionalRadio::class,
                'options' => [
                    'label' => 'Type', // @translate
                    'value_options' => [
                        'start' => 'New division', // @translate
                        'inter' => 'End previous and start new', // @translate
                        'end' => 'End division', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'division-type',
                    'required' => true,
                    'value' => 'start',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][tag]',
                'type' => OptionalRadio::class,
                'options' => [
                    'label' => 'Tag', // @translate
                    'value_options' => [
                        'div' => 'div', // @translate
                        'aside' => 'aside', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'division-tag',
                    'value' => 'div',
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
                    'id' => 'division-class',
                    'placeholder' => 'main column align-left',
                ],
            ]);
    }
}
