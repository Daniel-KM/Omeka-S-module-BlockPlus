<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class SiteSettingsFieldset extends Fieldset
{
    protected $label = 'Block Plus'; // @translate

    public function init(): void
    {
        $this
            ->setAttribute('id', 'block-plus')
            ->add([
                'name' => 'blockplus_page_types',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'label' => 'Page types', // @translate
                    'info' => 'Specify the list of types that will be available to build specific pages.', // @translate
                    'as_key_value' => true,
                ],
                'attributes' => [
                    'id' => 'blockplus_page_types',
                    'placeholder' => 'home = Home
exhibit = Exhibit
exhibit_page = Exhibit page
simple = Simple page', // @translate
                    'rows' => 5,
                ],
            ])
        ;
    }
}
