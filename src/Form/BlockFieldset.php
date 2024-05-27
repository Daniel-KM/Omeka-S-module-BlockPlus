<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class BlockFieldset extends Fieldset
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
            ])
        ;
    }
}
