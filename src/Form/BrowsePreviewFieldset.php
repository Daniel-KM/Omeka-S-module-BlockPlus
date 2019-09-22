<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\TemplateSelect;
use Zend\Form\Element;
use Zend\Form\Fieldset;

class BrowsePreviewFieldset extends Fieldset
{
    public function init()
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][heading]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Block title', // @translate
                    'info' => 'Heading for the block, if any.', // @translate
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
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][query]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Query', // @translate
                    'info' => 'Display resources using this search query', // @translate
                    'documentation' => 'https://omeka.org/s/docs/user-manual/sites/site_pages/#browse-preview',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][limit]',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Limit', // @translate
                    'info' => 'Maximum number of resources to display in the preview.', // @translate
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
                'name' => 'o:block[__blockIndex__][o:data][link-text]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Link text', // @translate
                    'info' => 'Text for link to full browse view, if any. There is no link for media.', // @translate
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
                    'class' => 'chosen-select',
                ],
            ])
        ;
    }
}
