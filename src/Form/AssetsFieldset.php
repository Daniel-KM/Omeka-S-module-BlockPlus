<?php
namespace BlockPlus\Form;

use Omeka\Form\Element\Asset;
use Zend\Form\Element;
use Zend\Form\Fieldset;

class AssetsFieldset extends Fieldset
{
    public function init()
    {
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][assets][]',
            'type' => Asset::class,
            'options' => [
                'label' => 'Assets', // @translate
                'multiple' => true,
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][links_labels]',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Links and labels', // @translate
                'info' => 'Optional list of urls and labels separated by "|", for each asset, one by line.', // @translate
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
            'name' => 'o:block[__blockIndex__][o:data][misc]',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Miscellaneous', // @translate
                'info' => 'This text area may be use with specific partials.', // @translate
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][partial]',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Partial to display', // @translate
                'info' => 'Partials are in folder "common/block-layout" of the theme and should start with "assets".', // @translate
                'value_options' => [
                    '' => 'Default', // @translate
                ],
            ],
            'attributes' => [
                'class' => 'chosen-select',
            ],
        ]);
    }
}
