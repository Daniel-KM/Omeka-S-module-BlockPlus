<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\TemplateSelect;
use Zend\Form\Element;
use Zend\Form\Fieldset;

class ItemWithMetadataFieldset extends Fieldset
{
    public function init()
    {
        // Attachments fields are managed separately.

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
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "item-with-metadata".', // @translate
                    'template' => 'common/block-layout/item-with-metadata',
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
