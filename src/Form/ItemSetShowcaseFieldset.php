<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\TemplateSelect;
use BlockPlus\Form\Element\ThumbnailTypeSelect;
use Omeka\Form\Element\ItemSetSelect;
use Zend\Form\Element;
use Zend\Form\Fieldset;

class ItemSetShowcaseFieldset extends Fieldset
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
                'name' => 'o:block[__blockIndex__][o:data][item_sets]',
                'type' => ItemSetSelect::class,
                'options' => [
                    'label' => 'Item sets', // @translate
                    // 'disable_group_by_owner' => true,
                ],
                'attributes' => [
                    'id' => 'item-set-showcase-item-sets',
                    'multiple' => true,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select item sets…',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][thumbnail_type]',
                'type' => ThumbnailTypeSelect::class,
                'options' => [
                    'label' => 'Thumbnail type', // @translate
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][show_title_option]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Show title', // @translate
                    'checked_value' => 'item_set_title',
                    'unchecked_value' => 'no_title',
                    'use_hidden_element' => true,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "item-set-showcase".', // @translate
                    'template' => 'common/block-layout/item-set-showcase',
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
