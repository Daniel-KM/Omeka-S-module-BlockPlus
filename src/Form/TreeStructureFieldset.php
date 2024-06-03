<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class TreeStructureFieldset extends Fieldset
{
    public function init(): void
    {
        $this
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
                'type' => OmekaElement\PropertySelect::class,
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
        ;
    }
}
