<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\PartialSelect;
use Zend\Form\Fieldset;

class PageTitleFieldset extends Fieldset
{
    public function init()
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][partial]',
                'type' => PartialSelect::class,
                'options' => [
                    'label' => 'Partial to display', // @translate
                    'info' => 'Partials are in folder "common/block-layout" of the theme and should start with "page-title".', // @translate
                    'partial' => 'common/block-layout/page-title',
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
