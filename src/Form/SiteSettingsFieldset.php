<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class SiteSettingsFieldset extends Fieldset
{
    protected $label = 'Block Plus'; // @translate

    protected $elementGroups = [
        'block_plus' => 'Block plus', // @translate
        'breadcrumbs' => 'Breadcrumbs', // @translate
    ];

    public function init(): void
    {
        $this
            ->setAttribute('id', 'block-plus')
            ->setOption('element_groups', $this->elementGroups)

            // Block metadata page type.

            ->add([
                'name' => 'blockplus_page_types',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'element_group' => 'block_plus',
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

            // Breadcrumbs.

            ->add([
                'name' => 'blockplus_breadcrumbs_crumbs',
                'type' => MenuElement\OptionalMultiCheckbox::class,
                'options' => [
                    'element_group' => 'breadcrumbs',
                    'label' => 'Crumbs', // @translate
                    'value_options' => [
                        'home' => 'Prepend home', // @translate
                        'collections' => 'Include "Collections"', // @translate,
                        'itemset' => 'Include main item set for item', // @translate,
                        'current' => 'Append current resource', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'blockplus_breadcrumbs_crumbs',
                ],
            ])
            ->add([
                'name' => 'blockplus_breadcrumbs_prepend',
                'type' => MenuElement\DataTextarea::class,
                'options' => [
                    'element_group' => 'breadcrumbs',
                    'label' => 'Prepended links', // @translate
                    'info' => 'List of urls followed by a label, separated by a "=", one by line, that will be prepended to the breadcrumb.', // @translate
                    'as_key_value' => false,
                    'data_keys' => [
                        'uri',
                        'label',
                    ],
                ],
                'attributes' => [
                    'id' => 'blockplus_breadcrumbs_prepend',
                    'placeholder' => '/s/my-site/page/intermediate = Example page',
                ],
            ])
            ->add([
                'name' => 'blockplus_breadcrumbs_collections_url',
                'type' => Element\Text::class,
                'options' => [
                    'element_group' => 'breadcrumbs',
                    'label' => 'Url for collections', // @translate
                    'info' => 'The url to use for the link "Collections", if set above. Let empty to use the default one.', // @translate
                ],
                'attributes' => [
                    'id' => 'blockplus_breadcrumbs_collections_url',
                    'placeholder' => '/s/my-site/search?resource-type=item_sets',
                ],
            ])
            ->add([
                'name' => 'blockplus_breadcrumbs_separator',
                'type' => Element\Text::class,
                'options' => [
                    'element_group' => 'breadcrumbs',
                    'label' => 'Separator', // @translate
                    'info' => 'The separator between crumbs may be set as raw text or via css. it should be set as an html text ("&gt;").', // @translate
                ],
                'attributes' => [
                    'id' => 'blockplus_breadcrumbs_separator',
                    'placeholder' => '&gt;',
                ],
            ])
            ->add([
                'name' => 'blockplus_breadcrumbs_homepage',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'breadcrumbs',
                    'label' => 'Display on home page', // @translate
                ],
                'attributes' => [
                    'id' => 'blockplus_breadcrumbs_homepage',
                ],
            ])

        ;
    }
}
