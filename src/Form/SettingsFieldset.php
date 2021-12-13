<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Laminas\Form\Fieldset;

class SettingsFieldset extends Fieldset
{
    protected $label = 'Block Plus'; // @translate

    public function init(): void
    {
        $this
            ->add([
                'name' => 'blockplus_html_mode',
                'type' => BlockPlusElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Html mode', // @translate
                    'value_options' => [
                        'inline' => 'Inline (default)', // @translate
                        'document' => 'Document (maximizable)', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'blockplus_html_mode',
                ],
            ])
        ;
    }
}
