<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\PartialSelect;
use Zend\Form\Element;
use Zend\Form\Fieldset;

class BrowsePreviewFieldset extends Fieldset
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
            'attributes' => [
                'class' => 'chosen-select',
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
                'label' => 'Block title', // @translate
                'info' => 'Heading for the block, if any.', // @translate
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
            'type' => PartialSelect::class,
            'options' => [
                'label' => 'Partial to display', // @translate
                'info' => 'Partials are in folder "common/block-layout" of the theme and should start with "browse-preview".', // @translate
                'partial' => 'common/block-layout/browse-preview',
            ],
            'attributes' => [
                'class' => 'chosen-select',
            ],
        ]);
    }
}
