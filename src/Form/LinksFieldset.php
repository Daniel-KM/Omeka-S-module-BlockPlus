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
            ]);
    }
}
