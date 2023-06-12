<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Laminas\Form\Fieldset;

class SettingsFieldset extends Fieldset
{
    protected $label = 'Block Plus'; // @translate

    protected $elementGroups = [
        'block_plus' => 'Block plus', // @translate
    ];

    public function init(): void
    {
        $this
            ->setAttribute('id', 'block-plus')
            ->setOption('element_groups', $this->elementGroups)
            ->add([
                'name' => 'blockplus_html_mode_page',
                'type' => BlockPlusElement\OptionalRadio::class,
                'options' => [
                    'element_group' => 'block_plus',
                    'label' => 'Html edition mode for pages', // @translate
                    'value_options' => [
                        'inline' => 'Inline (default)', // @translate
                        'document' => 'Document (maximizable)', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'blockplus_html_mode_page',
                ],
            ])
            ->add([
                'name' => 'blockplus_html_config_page',
                'type' => BlockPlusElement\OptionalRadio::class,
                'options' => [
                    'element_group' => 'block_plus',
                    'label' => 'Html edition config and toolbar for pages', // @translate
                    'value_options' => [
                        // @see https://ckeditor.com/cke4/presets-all
                        'default' => 'Default', // @translate
                        'standard' => 'Standard', // @translate
                        'full' => 'Full', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'blockplus_html_config_page',
                ],
            ])

            // Other options.

            ->add([
                'name' => 'blockplus_property_itemset',
                'type' => MenuElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'block_plus',
                    'label' => 'Property to set primary item set', // @translate
                    'info' => 'When an item is included in multiple item sets, the main one may be determined by this property.', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'blockplus_property_itemset',
                    'class' => 'chosen-select',
                    'multiple' => false,
                    'data-placeholder' => 'Select a property…', // @translate
                ],
            ])
        ;
    }
}
