<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class SearchResultsFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][resource_type]',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Resource type', // @translate
                    'value_options' => [
                        'items' => 'Items', // @translate
                        'item_sets' => 'Item sets', // @translate
                        'media' => 'Media', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'search-results-resource-type',
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][query]',
                'type' => OmekaElement\Query::class,
                'options' => [
                    'label' => 'Search pool query', // @translate
                    'info' => 'Used to restrict resources to search, for example on an item set.', // @translate
                    'query_resource_type' => null,
                    'query_partial_excludelist' => ['common/advanced-search/site'],
                ],
                'attributes' => [
                    'id' => 'search-results-query',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][limit]',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Limit', // @translate
                    'info' => 'Maximum number of resources to display in the preview.', // @translate
                ],
                'attributes' => [
                    'id' => 'search-results-limit',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][pagination]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Pagination', // @translate
                    'info' => 'Show pagination to browse all resources on the same page.', // @translate
                ],
                'attributes' => [
                    'id' => 'search-results-pagination',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][autoscroll]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Auto-scroll to block', // @translate
                    'info' => 'When enabled, the page will scroll to this block after form submission. Useful when the block is not at the top of the page.', // @translate
                ],
                'attributes' => [
                    'id' => 'search-results-autoscroll',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][sort_headings]',
                'type' => OmekaElement\PropertySelect::class,
                'options' => [
                    'label' => 'Sort headings', // @translate
                    'info' => 'Display sort links for the list of results.', // @translate
                    'term_as_value' => true,
                    'prepend_value_options' => [
                        'created' => 'Created', // @translate
                        'resource_class_label' => 'Resource class', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'search-results-sort-headings',
                    'class' => 'chosen-select',
                    'multiple' => true,
                    'data-placeholder' => 'Select properties', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][resource_template]',
                'type' => OmekaElement\ResourceTemplateSelect::class,
                'options' => [
                    'label' => 'Resource template for sort headings', // @translate
                    'info' => 'If set, the alternative labels of this resource template will be used to display the sort headings.', // @translate
                    'empty_option' => '',
                ],
                'attributes' => [
                    'id' => 'search-results-resource-template',
                    'class' => 'chosen-select',
                    'multiple' => false,
                    'data-placeholder' => 'Select resource template…', // @translate
                ],
            ])
            // Implemented for compatibility with old templates of the block
            // Browse Preview after migration.
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][components]',
                'type' => Element\MultiCheckbox::class,
                'options' => [
                    'label' => 'Components', // @translate
                    'info' => 'Components to display for each resource. If not set in Site Settings, Heading defaults to resource Title and Body to resource Description', // @translate
                    'value_options' => [
                        [
                            'value' => 'search-form',
                            'label' => 'Search form', // @translate
                        ],
                        [
                            'value' => 'resource-heading',
                            'label' => 'Heading', // @translate
                        ],
                        [
                            'value' => 'resource-body',
                            'label' => 'Body', // @translate
                        ],
                        [
                            'value' => 'thumbnail',
                            'label' => 'Thumbnail', // @translate
                        ],
                    ],
                ],
                'attributes' => [
                    'id' => 'search-results-components',
                    'value' => [
                        'search-form',
                        'resource-heading',
                        'resource-body',
                        'thumbnail',
                    ],
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][properties]',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'label' => 'Properties to display for each result', // @translate
                ],
                'attributes' => [
                    'id' => 'search-results-properties',
                    'rows' => 5,
                    'placeholder' => <<<TXT
                        dcterms:creator
                        dcterms:date
                        dcterms:subject
                        TXT,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][link-text]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Link text', // @translate
                    'info' => 'Text for link to full browse view, if any.', // @translate
                ],
                'attributes' => [
                    'id' => 'search-results-link-text',
                ],
            ])
        ;
    }
}
