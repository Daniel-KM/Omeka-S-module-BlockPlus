<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element\TemplateSelect;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element\ArrayTextarea;

class LinksFieldset extends Fieldset
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
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][links]',
                'type' => ArrayTextarea::class,
                'options' => [
                    'label' => 'Links', // @translate
                    'as_key_value' => true,
                ],
                'attributes' => [
                    'rows' => 6,
                    'placeholder' => '/s/main/page/alpha = Alpha
/s/main/page/beta = Beta
',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "links".', // @translate
                    'template' => 'common/block-layout/links',
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
