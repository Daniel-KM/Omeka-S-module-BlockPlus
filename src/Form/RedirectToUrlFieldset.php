<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class RedirectToUrlFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][url]',
                'type' => Element\Url::class,
                'options' => [
                    'label' => 'Redirect the current page to this url', // @translate
                ],
            ]);
    }
}
