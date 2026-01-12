<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

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

            // Layouts.

            ->add([
                'name' => 'blockplus_page_model_rights',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'block_plus',
                    'label' => 'Allow site editor to create page models and groups of blocks', // @translate
                ],
                'attributes' => [
                    'id' => 'blockplus_page_model_rights',
                ],
            ])

            ->add([
                'name' => 'blockplus_page_model_skip_blockplus',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'block_plus',
                    'label' => 'Skip page models defined internally by the module Block Plus', // @translate
                    'info' => 'Default page models are mainly used as examples or for upgrade from Omeka Classic: home_page, exhibit, exhibit_page, simple_page and resource_text.', // @translate'
                ],
                'attributes' => [
                    'id' => 'blockplus_page_model_skip_blockplus',
                ],
            ])

            ->add([
                'name' => 'blockplus_page_models',
                'type' => CommonElement\IniTextarea::class,
                'options' => [
                    'element_group' => 'block_plus',
                    'label' => 'Page models and groups of blocks', // @translate
                    'info' => 'List all page models and blocks groups formatted as ini with a section for each group.', // @translate
                    'documentation' => 'https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus#Usage',
                ],
                'attributes' => [
                    'id' => 'blockplus_page_models',
                ],
            ])

            // Breadcrumbs.

            ->add([
                'name' => 'blockplus_breadcrumbs_crumbs',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'element_group' => 'breadcrumbs',
                    'label' => 'Crumbs', // @translate
                    'value_options' => [
                        // Copy options in view helper \BlockPlus\View\Helper\Breadcrumbs.
                        'home' => 'Prepend home', // @translate
                        'collections' => 'Include "Collections"', // @translate,
                        'itemset' => 'Include main item set for item', // @translate,
                        'itemsetstree' => 'Include item sets tree', // @translate,
                        'current' => 'Append current resource', // @translate
                        'current_link' => 'Append current resource as a link', // @translate
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
                    'data_options' => [
                        'uri' => null,
                        'label' => null,
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
                        'print' => 'Print', // @translate
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

            // Resource block See also (similar resources).

            // TODO How to implement heading for all resource blocks?
            ->add([
                'name' => 'blockplus_seealso_heading',
                'type' => Element\Text::class,
                'options' => [
                    'element_group' => 'block_plus_resources',
                    'label' => 'Heading for "See also" block', // @translate
                    'info' => 'Text displayed as heading above similar resources. Leave empty for no heading.', // @translate
                ],
                'attributes' => [
                    'id' => 'blockplus_seealso_heading',
                ],
            ])
            ->add([
                'name' => 'blockplus_seealso_limit',
                'type' => Element\Number::class,
                'options' => [
                    'element_group' => 'block_plus_resources',
                    'label' => 'Number of similar resources to display in block See also', // @translate
                ],
                'attributes' => [
                    'id' => 'blockplus_seealso_limit',
                    'min' => 0,
                    'max' => 100,
                ],
            ])
            ->add([
                'name' => 'blockplus_seealso_pool',
                'type' => \Omeka\Form\Element\Query::class,
                'options' => [
                    'element_group' => 'block_plus_resources',
                    'label' => 'Query to limit similar resources in block See also', // @translate
                    'info' => 'When set, this query defines the pool of resources. When empty, properties below are used to find similar resources.', // @translate
                    'query_resource_type' => 'items',
                ],
                'attributes' => [
                    'id' => 'blockplus_seealso_pool',
                ],
            ])
            ->add([
                'name' => 'blockplus_seealso_properties',
                'type' => CommonElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'block_plus_resources',
                    'label' => 'Properties to match for similar resources in block See also', // @translate
                    'info' => 'Used only when query above is empty.', // @translate
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'blockplus_seealso_properties',
                    'multiple' => true,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select properties…', // @translate
                ],
            ])
            ->add([
                'name' => 'blockplus_seealso_all_sites',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'block_plus_resources',
                    'label' => 'Search similar resources in all sites', // @translate
                ],
                'attributes' => [
                    'id' => 'blockplus_seealso_all_sites',
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
