<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SearchFormFieldset extends Fieldset
{
    /**
     * @var array
     */
    protected $searchConfigs = [];

    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][html]',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Html to display', // @translate
                ],
                'attributes' => [
                    'id' => 'search-form-html',
                    'class' => 'block-html full wysiwyg',
                    'rows' => '5',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][link]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Link to display', // @translate
                    'info' => 'Formatted as "/url/full/path Label of the link".', // @translate
                ],
                'attributes' => [
                    'id' => 'search-form-link',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][selector]',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Main filter', // @translate
                    'value_options' => [
                        '' => 'None', // @translate
                        'item_sets' => 'Item sets', // @translate
                        'resource_classes' => 'Resource classes', // @translate
                        'resource_templates' => 'Resource templates', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'search-form-selector',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][search_config]',
                'type' => CommonElement\OptionalSelect::class,
                'options' => [
                    'label' => 'Search config page (module Advanced Search)', // @translate
                    'value_options' => [
                        'default' => 'Search config of the site', // @translate
                        'omeka' => 'Omeka search engine',
                    ] + $this->searchConfigs,
                    'empty_option' => '',
                ],
                'attributes' => [
                    'id' => 'searching-form-search-config',
                    'class' => 'chosen-select',
                    'required' => false,
                    'data-placeholder' => 'Select a search engine…', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => BlockPlusElement\TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "search-form".', // @translate
                    'template' => 'common/block-layout/search-form',
                ],
                'attributes' => [
                    'id' => 'search-form-template',
                    'class' => 'chosen-select',
                ],
            ]);
    }

    public function setSearchConfigs(array $searchConfigs): self
    {
        $this->searchConfigs = $searchConfigs;
        return $this;
    }
}
