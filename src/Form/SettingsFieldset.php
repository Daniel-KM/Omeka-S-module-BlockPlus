<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Common\Form\Element as CommonElement;
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

            // Html mode.

            ->add([
                'name' => 'blockplus_html_mode_page',
                'type' => CommonElement\OptionalRadio::class,
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
                'type' => CommonElement\OptionalRadio::class,
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

            // Layouts.

            ->add([
                'name' => 'blockplus_page_models',
                'type' => CommonElement\IniTextarea::class,
                'options' => [
                    'element_group' => 'block_plus',
                    'label' => 'Page models and groups of blocks', // @translate
                    'info' => 'List all page models and blocks groups formatted as ini with a section for each group.', // @translate
                    'documentation' => 'https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus#Usage',
                    // The html of the blocks may contain attributes.
                    'ini_allow_double_quotes' => true,
                ],
                'attributes' => [
                    'id' => 'blockplus_page_models',
                ],
            ])

            // Other options.

            ->add([
                'name' => 'blockplus_property_itemset',
                'type' => CommonElement\OptionalPropertySelect::class,
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
            ->add([
                'name' => 'blockplus_twitter_mirrors',
                'type' => CommonElement\ArrayTextarea::class,
                'options' => [
                    'element_group' => 'block_plus',
                    'label' => 'Mirror sites for the block Twitter', // @translate
                    'info' => 'The api of Twitter requires a paid account, so the messages are fetched from the rss feed of a mirror site (Nitter instance). Set one url by line: they are checked in turn until one is available.', // @translate
                ],
                'attributes' => [
                    'id' => 'blockplus_twitter_mirrors',
                    'rows' => 4,
                    'placeholder' => 'https://nitter.net',
                ],
            ])
        ;
    }
}
