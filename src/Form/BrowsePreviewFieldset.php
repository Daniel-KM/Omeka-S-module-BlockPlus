<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element\TemplateSelect;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class BrowsePreviewFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][heading]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Block title', // @translate
                ],
                'attributes' => [
                    'id' => 'browse-preview-heading',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][html]',
                'type' => Element\Textarea::class,
                'attributes' => [
                    'id' => 'browse-preview-html',
                    'class' => 'block-html full wysiwyg',
                ],
            ])
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
                    'id' => 'browse-preview-resource-type',
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][query]',
                'type' => OmekaElement\Query::class,
                'options' => [
                    'label' => 'Query', // @translate
                    'info' => 'Display resources using this search query', // @translate
                    'query_resource_type' => null,
                    'query_partial_excludelist' => ['common/advanced-search/site'],
                ],
                'attributes' => [
                    'id' => 'browse-preview-query',
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
                    'id' => 'browse-preview-limit',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][components]',
                'type' => Element\MultiCheckbox::class,
                'options' => [
                    'label' => 'Components', // @translate
                    'info' => 'Components to display for each resource. If not set in Site Settings, Heading defaults to resource Title and Body to resource Description', // @translate
                    'value_options' => [
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
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][pagination]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Pagination', // @translate
                    'info' => 'Show pagination to browse all resources on the same page.', // @translate
                ],
                'attributes' => [
                    'id' => 'browse-preview-pagination',
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
                    'id' => 'browse-preview-sort-headings',
                    'class' => 'chosen-select',
                    'multiple' => true,
                    'data-placeholder' => 'Select properties…', // @translate
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
                    'id' => 'browse-preview-resource-template',
                    'class' => 'chosen-select',
                    'multiple' => false,
                    'data-placeholder' => 'Select resource template…', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][link-text]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Link text', // @translate
                    'info' => 'Text for link to full browse view, if any. There is no link for media.', // @translate
                ],
                'attributes' => [
                    'id' => 'browse-preview-link-text',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "browse-preview".', // @translate
                    'template' => 'common/block-layout/browse-preview',
                ],
                'attributes' => [
                    'id' => 'browse-preview-template',
                    'class' => 'chosen-select',
                ],
            ])
        ;
    }
}
