<?php declare(strict_types=1);
namespace BlockPlus\Form;

use BlockPlus\Form\Element\TemplateSelect;
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
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "search-form".', // @translate
                    'template' => 'common/block-layout/search-form',
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
