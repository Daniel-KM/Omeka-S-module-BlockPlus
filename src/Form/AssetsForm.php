<?php
namespace BlockPlus\Form;

use Omeka\Form\Element\Asset;
use Zend\Form\Element;
use Zend\Form\Form;

class AssetsForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][assets][]',
            'type' => Asset::class,
            'options' => [
                'label' => 'Assets', // @translate
                'multiple' => true,
            ],
        ]);
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][links_labels]',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Links and labels', // @translate
                'info' => 'List of urls and optionally labels separated by "|", for each asset, one by line.', // @translate
            ],
        ]);
    }
}
