<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class LinksFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][links]',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'label' => 'Links', // @translate
                    'as_key_value' => true,
                ],
                'attributes' => [
                    'rows' => 6,
                    'placeholder' => '/s/main/page/alpha = Alpha
/s/main/page/beta = Beta = short description
',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => BlockPlusElement\TemplateSelect::class,
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
