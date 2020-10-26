<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\TemplateSelect;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class TableOfContentsFieldset extends Fieldset
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
                'name' => 'o:block[__blockIndex__][o:data][depth]',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Depth', // @translate
                    'info' => 'Number of child page levels to display', // @translate
                ],
                'attributes' => [
                    'min' => 1,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][root]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'List from root', // @translate
                    'info' => 'If set, all the pages will be displayed, else only the ones from the current page.', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "table-of-contents".', // @translate
                    'template' => 'common/block-layout/table-of-contents',
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
