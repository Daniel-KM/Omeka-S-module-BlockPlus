<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class BlockFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][params]',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Params', // @translate
                    'info' => 'The params are passed directly to the block layout.', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][params_type]',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Params type', // @translate
                    'value_options' => [
                        'auto' => 'Automatic', // @translate
                        'raw' => 'Raw string', // @translate
                        'ini' => 'Format "Ini"', // @translate
                        'json_array' => 'Json', // @translate'
                        'key_value' => 'Associative array of key / value pairs (separated with "=")', // @translate'
                        'key_value_array' => 'List of arrays with two values, the key and the value (separated with "=")', // @translate'
                    ],
                ],
                'attributes' => [
                    'value' => 'auto',
                ],
            ])
        ;
    }
}
