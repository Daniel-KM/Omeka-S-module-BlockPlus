<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element\ItemSetSelect;

class ItemSetShowcaseFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][item_sets]',
                'type' => ItemSetSelect::class,
                'options' => [
                    'label' => 'Item sets', // @translate
                    // 'disable_group_by_owner' => true,
                    'query' => null,
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
                'type' => CommonElement\ThumbnailTypeSelect::class,
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
            ]);
    }
}
