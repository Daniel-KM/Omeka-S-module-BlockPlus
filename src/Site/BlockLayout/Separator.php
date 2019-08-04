<?php
namespace BlockPlus\Site\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Zend\Form\Element;
use Zend\Form\Fieldset;
use Zend\View\Renderer\PhpRenderer;

class Separator extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Separator'; // @translate
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['separator'];

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = new Fieldset();
        $fieldset->add([
            'name' => 'o:block[__blockIndex__][o:data][class]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Class', // @translate
                'info' => 'Set the class or the name of the separator. Default is "transparent". Other ones depends on the theme.', // @translate
            ],
            'attributes' => [
                'placeholder' => 'transparent',
            ],
        ]);

        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $class = $block->dataValue('class', 'transparent');
        return '<div class="break separator ' . $class . '"></div>';
    }
}
