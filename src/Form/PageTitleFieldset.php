<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\TemplateSelect;
use Zend\Form\Fieldset;

class PageTitleFieldset extends Fieldset
{
    public function init()
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "page-title".', // @translate
                    'template' => 'common/block-layout/page-title',
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
