<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class HtmlFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][html]',
                'type' => Element\Textarea::class,
                'attributes' => [
                    'id' => 'html-html',
                    'class' => 'block-html full wysiwyg',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => BlockPlusElement\TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "html".', // @translate
                    'template' => 'common/block-layout/html',
                ],
                'attributes' => [
                    'id' => 'html-template',
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
