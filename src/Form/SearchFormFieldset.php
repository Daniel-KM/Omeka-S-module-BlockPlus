<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SearchFormFieldset extends Fieldset
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
                    'id' => 'search-form-heading',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][html]',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Html to display', // @translate
                ],
                'attributes' => [
                    'id' => 'search-form-html',
                    'class' => 'block-html full wysiwyg',
                    'rows' => '5',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][selector]',
                'type' => BlockPlusElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Main filter', // @translate
                    'value_options' => [
                        '' => 'None', // @translate
                        'item_sets' => 'Item sets', // @translate
                        'resource_classes' => 'Resource classes', // @translate
                        'resource_templates' => 'Resource templates', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'search-form-selector',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => BlockPlusElement\TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "search-form".', // @translate
                    'template' => 'common/block-layout/search-form',
                ],
                'attributes' => [
                    'id' => 'search-form-template',
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
