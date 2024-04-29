<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class ListOfPagesFieldset extends Fieldset
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
                    'id' => 'list-of-pages-heading',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][pagelist]',
                'type' => Element\Hidden::class,
                'attributes' => [
                    'id' => 'list-of-pages-pagelist',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => BlockPlusElement\TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "list-of-pages".', // @translate
                    'template' => 'common/block-layout/list-of-pages',
                ],
                'attributes' => [
                    'id' => 'list-of-pages-template',
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
