<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Laminas\Form\Fieldset;

class SettingsFieldset extends Fieldset
{
    protected $label = 'Block Plus'; // @translate

    protected $elementGroups = [
        'site_pages' => 'Site pages', // @translate
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
                    'element_group' => 'site_pages',
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
                    'element_group' => 'site_pages',
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
        ;
    }
}
