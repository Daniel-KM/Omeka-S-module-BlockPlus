<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class AssetFieldset extends Fieldset
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
                    'id' => 'asset-heading',
                ],
            ])
            /*
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][attachments]',
                'type' => Element\Hidden::class,
                'attributes' => [
                    'id' => 'asset-attachments',
                ],
            ])
            */
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][className]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Class', // @translate
                    'info' => 'Optional CSS class for styling HTML.', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => BlockPlusElement\TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "asset".', // @translate
                    'template' => 'common/block-layout/asset',
                ],
                'attributes' => [
                    'id' => 'asset-template',
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
