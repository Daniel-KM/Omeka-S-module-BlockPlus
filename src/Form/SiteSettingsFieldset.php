<?php
namespace BlockPlus\Form;

use Zend\Form\Element;
use Zend\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
{
    protected $label = 'Block Plus'; // @translate

    public function init()
    {
        $this
            ->add([
                'name' => 'blockplus_page_types',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Page types', // @translate
                    'info' => 'Specify the list of types that will be available to build specific pages.', // @translate
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
