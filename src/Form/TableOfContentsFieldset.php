<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\PartialSelect;
use Zend\Form\Element;
use Zend\Form\Fieldset;

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
                'name' => 'o:block[__blockIndex__][o:data][partial]',
                'type' => PartialSelect::class,
                'options' => [
                    'label' => 'Partial to display', // @translate
                    'info' => 'Partials are in folder "common/block-layout" of the theme and should start with "table-of-contents".', // @translate
                    'partial' => 'common/block-layout/table-of-contents',
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
