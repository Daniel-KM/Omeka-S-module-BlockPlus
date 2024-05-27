<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class D3GraphFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][params]',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Params', // @translate
                    'info' => 'The params are passed directly to the block layout.', // @translate
                ],
                'attributes' => [
                    'rows' => 12,
                    'placeholder' => '{
    "items": {
        "limit": 100
    } ,
    "item_sets": null,
    "relations": [
        "objects",
        "subjects",
        "item_sets"
    ],
    "config": {
        "height": 800,
        "forceCharge": -100,
        "forceLinkDistance": 100,
        "baseCirclePow": 0.6,
        "baseCircleMin": 5,
        "fontSizeTop": 35,
        "fontSizeMin": ".1px",
        "fontSizeMax": "16px"
    }
}
',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => BlockPlusElement\TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "d3-graph".', // @translate
                    'template' => 'common/block-layout/d3-graph',
                ],
                'attributes' => [
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
