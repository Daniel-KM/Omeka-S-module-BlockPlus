<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class SiteSettingsFieldset extends Fieldset
{
    protected $label = 'Block Plus'; // @translate

    protected $elementGroups = [
        'block_plus' => 'Block plus', // @translate
        'breadcrumbs' => 'Breadcrumbs', // @translate
        'block_plus_resources' => 'Block plus resources', // @translate
    ];

    public function init(): void
    {
        $this
            ->setAttribute('id', 'block-plus')
            ->setOption('element_groups', $this->elementGroups)

            // Breadcrumbs.

            ->add([
                'name' => 'blockplus_breadcrumbs_crumbs',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'element_group' => 'breadcrumbs',
                    'label' => 'Crumbs', // @translate
                    'value_options' => [
                        'home' => 'Prepend home', // @translate
                        'collections' => 'Include "Collections"', // @translate,
                        'itemset' => 'Include main item set for item', // @translate,
                        'itemsetstree' => 'Include item sets tree', // @translate,
                        'current' => 'Append current resource', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'blockplus_breadcrumbs_crumbs',
                ],
            ])
            ->add([
                'name' => 'blockplus_breadcrumbs_prepend',
                'type' => CommonElement\DataTextarea::class,
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

            // Resource block buttons.

            ->add([
                'name' => 'blockplus_block_buttons',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'element_group' => 'block_plus_resources',
                    'label' => 'Settings for the resource block Buttons', // @translate
                    'value_options' => [
                        'download' => 'Download', // @translate
                        'email' => 'Share by email', // @translate
                        'facebook' => 'Share on Facebook', // @translate
                        'pinterest' => 'Share on Pinterest', // @translate
                        'twitter' => 'Share on Twitter (now X)', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'blockplus_block_buttons',
                ],
            ])

            // Resource block Previous/Next resources.

            ->add([
                'name' => 'blockplus_items_order_for_itemsets',
                'type' => Element\Textarea::class,
                'options' => [
                    'element_group' => 'block_plus_resources',
                    'label' => 'Default items order in each item set', // @translate
                    'info' => 'Set order for item set, one by row, format "id,id,id property order". Use "0" for the default.', // @translate
                ],
                'attributes' => [
                    'id' => 'blockplus_items_order_for_itemsets',
                    'placeholder' => '0 dcterms:identifier asc
17,24 created desc
73 dcterms:title asc',
                ],
            ])
            // TODO Use omeka element query, but check compatibility with module Advanced Search.
            ->add([
                'name' => 'blockplus_prevnext_items_query',
                'type' => Element\Text::class,
                'options' => [
                    'element_group' => 'block_plus_resources',
                    'label' => 'Query to limit and sort the list of items for the previous/next buttons', // @translate
                    'info' => 'Use a standard query. Arguments from module Advanced Search are supported if present and needed.', // @translate
                    'documentation' => 'https://omeka.org/s/docs/user-manual/sites/site_pages/#browse-preview',
                ],
                'attributes' => [
                    'id' => 'blockplus_prevnext_items_query',
                ],
            ])
            ->add([
                'name' => 'blockplus_prevnext_item_sets_query',
                'type' => Element\Text::class,
                'options' => [
                    'element_group' => 'block_plus_resources',
                    'label' => 'Query to limit and sort the list of item sets for the previous/next buttons', // @translate
                    'info' => 'Use a standard query. Arguments from module Advanced Search are supported if present and needed.', // @translate
                    'documentation' => 'https://omeka.org/s/docs/user-manual/sites/site_pages/#browse-preview',
                ],
                'attributes' => [
                    'id' => 'blockplus_prevnext_item_sets_query',
                ],
            ])

        ;
    }
}
