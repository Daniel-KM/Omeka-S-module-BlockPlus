<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\PartialSelect;
use Omeka\Form\Element\Asset;
use Zend\Form\Element;
use Zend\Form\Fieldset;

class AssetsFieldset extends Fieldset
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
                'name' => 'o:block[__blockIndex__][o:data][assets]',
                'type' => Fieldset::class,
                'options' => [
                    'label' => 'Assets', // @translate
                ],
                'attributes' => [
                    'class' => 'assets-list',
                    'data-next-index' => '0',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][partial]',
                'type' => PartialSelect::class,
                'options' => [
                    'label' => 'Partial to display', // @translate
                    'info' => 'Partials are in folder "common/block-layout" of the theme and should start with "assets".', // @translate
                    'partial' => 'common/block-layout/assets',
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ]);

        $fieldsetBase = $this->get('o:block[__blockIndex__][o:data][assets]');
        $fieldsetBase
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][assets][__assetIndex__]',
                'type' => Fieldset::class,
                'options' => [
                    'label' => 'Asset', // @translate
                    'use_as_base_fieldset' => true,
                ],
                'attributes' => [
                    'class' => 'asset-data',
                    'data-index' => '__assetIndex__',
                ],
            ]);
        $fieldsetRepeat = $fieldsetBase->get('o:block[__blockIndex__][o:data][assets][__assetIndex__]');
        $fieldsetRepeat
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][assets][__assetIndex__][asset]',
                'type' => Asset::class,
                'options' => [
                    'label' => 'Asset file', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][assets][__assetIndex__][title]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Title', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][assets][__assetIndex__][url]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Url', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][assets][__assetIndex__][caption]',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Caption', // @translate
                ],
                'attributes' => [
                    'class' => 'block-html full wysiwyg',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][assets][__assetIndex__][class]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'CSS class', // @translate
                ],
            ])
            // TODO Move remove / creation of new fieldset to js?
            ->add([
                'name' => 'add_asset',
                'type' => Element\Button::class,
                'options' => [
                    'label' => 'Add another', // @translate
                ],
                'attributes' => [
                    'class' => 'asset-form-add button',
                ],
            ])
            ->add([
                'name' => 'remove_asset',
                'type' => Element\Button::class,
                'options' => [
                    'label' => 'Remove', // @translate
                ],
                'attributes' => [
                    'class' => 'asset-form-remove button red',
                ],
            ]);
    }
}
