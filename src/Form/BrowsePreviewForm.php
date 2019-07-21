<?php
namespace BlockPlus\Form;

use Zend\Form\Element;
use Zend\Form\Form;

class BrowsePreviewForm extends Form
{
    public function init()
    {
        $this->add([
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
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][query]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Query', // @translate
                'info' => 'Display resources using this search query', // @translate
                'documentation' => 'https://omeka.org/s/docs/user-manual/sites/site_pages/#browse-preview',
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][limit]',
            'type' => Element\Number::class,
            'options' => [
                'label' => 'Limit', // @translate
                'info' => 'Maximum number of resources to display in the preview.', // @translate
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][heading]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Preview title', // @translate
                'info' => 'Heading above resource list, if any.', // @translate
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][link-text]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Link text', // @translate
                'info' => 'Text for link to full browse view, if any. There is no link for media.', // @translate
            ],
        ]);

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][partial]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Partial', // @translate
                'info' => 'Set a different partial to display. Default is "common/block-layout/browse-preview" (no extension).', // @translate
            ],
            'attributes' => [
                'placeholder' => 'common/block-layout/browse-preview',
            ],
        ]);
    }
}
