<?php
namespace BlockPlus\Form;

use BlockPlus\Form\Element\TemplateSelect;
use Omeka\Form\Element\PropertySelect;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class TreeStructureFieldset extends Fieldset
{
    public function init()
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][heading]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Block title', // @translate
                    'info' => 'Heading for the block, if any.', // @translate
                ],
                'attributes' => [
                    'id' => 'tree-structure-heading',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][root]',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Root resource id', // @translate
                    'info' => 'The root of the structure. Can be an item, an item set or any other resource type.', // @translate
                ],
                'attributes' => [
                    'id' => 'tree-structure-root',
                    'required' => true,
                    'min' => 1,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][term]',
                'type' => PropertySelect::class,
                'options' => [
                    'label' => 'Property for structure', // @translate
                    'info' => 'Generally, it is "dcterms:hasPart".', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'tree-structure-term',
                    'required' => true,
                    'multiple' => false,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select property…', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][expanded]',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Level expanded', // @translate
                    'info' => 'Set 0 to start closed, a big number to display all levels.', // @translate
                ],
                'attributes' => [
                    'id' => 'tree-structure-expanded',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "tree-structure".', // @translate
                    'template' => 'common/block-layout/tree-structure',
                ],
                'attributes' => [
                    'id' => 'tree-structure-template',
                    'class' => 'chosen-select',
                ],
            ])
        ;
    }
}
