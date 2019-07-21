<?php
namespace BlockPlus\Form;

use Omeka\Form\Element\Asset;
use Zend\Form\Element;
use Zend\Form\Form;

class HeroForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][asset]',
            'type' => Asset::class,
            'options' => [
                'label' => 'Asset', // @translate
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][text]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Text', // @translate
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][button]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Button text', // @translate
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][url]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Button url', // @translate
                'info' => 'Url may be absolute (starting with https or http), at root of Omeka (starting with a "/"), else it will be a url inside the current site.', // @translate
            ],
        ]);
    }
}
