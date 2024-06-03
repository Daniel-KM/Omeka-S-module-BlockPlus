<?php declare(strict_types=1);

namespace BlockPlus;

return [
    'service_manager' => [
        'invokables' => [
            Mvc\MvcListeners::class => Mvc\MvcListeners::class,
        ],
        'factories' => [
            // Override theme factory to inject module pages and block templates.
            'Omeka\Site\ThemeManager' => Service\ThemeManagerFactory::class,
        ],
    ],
    'listeners' => [
        Mvc\MvcListeners::class,
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'assetElement' => View\Helper\AssetElement::class,
            'blockMetadata' => View\Helper\BlockMetadata::class,
            'breadcrumbs' => View\Helper\Breadcrumbs::class,
            'ckEditor' => View\Helper\CkEditor::class,
            'pageMetadata' => View\Helper\PageMetadata::class,
            'pagesMetadata' => View\Helper\PagesMetadata::class,
            'primaryItemSet' => View\Helper\PrimaryItemSet::class,
            'thumbnailUrl' => View\Helper\ThumbnailUrl::class,
        ],
    ],
    'block_layouts' => [
        'invokables' => [
            'block' => Site\BlockLayout\Block::class,
            'breadcrumbs' => Site\BlockLayout\Breadcrumbs::class,
            'buttons' => Site\BlockLayout\Buttons::class,
            'd3Graph' => Site\BlockLayout\D3Graph::class,
            'heading' => Site\BlockLayout\Heading::class,
            'itemSetShowcase' => Site\BlockLayout\ItemSetShowcase::class,
            'links' => Site\BlockLayout\Links::class,
            'listOfSites' => Site\BlockLayout\ListOfSites::class,
            'messages' => Site\BlockLayout\Messages::class,
            'pageMetadata' => Site\BlockLayout\PageMetadata::class,
            'redirectToUrl' => Site\BlockLayout\RedirectToUrl::class,
            'searchForm' => Site\BlockLayout\SearchForm::class,
            'searchResults' => Site\BlockLayout\SearchResults::class,
            'tableOfContents' => Site\BlockLayout\TableOfContents::class,
            'treeStructure' => Site\BlockLayout\TreeStructure::class,
            'twitter' => Site\BlockLayout\Twitter::class,
        ],
        'factories' => [
            'externalContent' => Service\BlockLayout\ExternalContentFactory::class,
            'mirrorPage' => Service\BlockLayout\MirrorPageFactory::class,
            'resourceText' => Service\BlockLayout\ResourceTextFactory::class,
            'showcase' => Service\BlockLayout\ShowcaseFactory::class,
        ],
    ],
    'resource_page_block_layouts' => [
        'invokables' => [
            'block' => Site\ResourcePageBlockLayout\Block::class,
            'breadcrumbs' => Site\ResourcePageBlockLayout\Breadcrumbs::class,
            'buttons' => Site\ResourcePageBlockLayout\Buttons::class,
            'description' => Site\ResourcePageBlockLayout\Description::class,
            'mediaPartOfItem' => Site\ResourcePageBlockLayout\MediaPartOfItem::class,
            'messages' => Site\ResourcePageBlockLayout\Messages::class,
            'previousNext' => Site\ResourcePageBlockLayout\PreviousNext::class,
            'resourceType' => Site\ResourcePageBlockLayout\ResourceType::class,
            'thumbnail' => Site\ResourcePageBlockLayout\Thumbnail::class,
            'title' => Site\ResourcePageBlockLayout\Title::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\Element\BlockShowTitleSelect::class => Form\Element\BlockShowTitleSelect::class,
            // Blocks.
            Form\BlockFieldset::class => Form\BlockFieldset::class,
            Form\ButtonsFieldset::class => Form\ButtonsFieldset::class,
            Form\D3GraphFieldset::class => Form\D3GraphFieldset::class,
            Form\ExternalContentFieldset::class => Form\ExternalContentFieldset::class,
            Form\HeadingFieldset::class => Form\HeadingFieldset::class,
            Form\ItemSetShowcaseFieldset::class => Form\ItemSetShowcaseFieldset::class,
            Form\ListOfSitesFieldset::class => Form\ListOfSitesFieldset::class,
            Form\MirrorPageFieldset::class => Form\MirrorPageFieldset::class,
            Form\RedirectToUrlFieldset::class => Form\RedirectToUrlFieldset::class,
            Form\ResourceTextFieldset::class => Form\ResourceTextFieldset::class,
            Form\SearchResultsFieldset::class => Form\SearchResultsFieldset::class,
            Form\ShowcaseFieldset::class => Form\ShowcaseFieldset::class,
            Form\TableOfContentsFieldset::class => Form\TableOfContentsFieldset::class,
            Form\TreeStructureFieldset::class => Form\TreeStructureFieldset::class,
            Form\TwitterFieldset::class => Form\TwitterFieldset::class,
            // Main and site config.
            Form\SettingsFieldset::class => Form\SettingsFieldset::class,
            Form\SiteSettingsFieldset::class => Form\SiteSettingsFieldset::class,
        ],
        'factories' => [
            /** @deprecated Since Omeka S v4.1, use core block template mechanism. Will be removed once all modules will be upgraded. */
            Form\Element\TemplateSelect::class => Service\Form\Element\TemplateSelectFactory::class,
            Form\PageMetadataFieldset::class => Service\Form\PageMetadataFieldsetFactory::class,
            Form\SearchFormFieldset::class => Service\Form\SearchFormFieldsetFactory::class,
        ],
    ],
    'page_templates' => [
    ],
    'block_templates' => [
        'asset' => [
            'asset-hero-bootstrap' => 'Block Plus: Hero bootstrap', // @translate
            'asset-partners' => 'Block Plus: Partners', // @translate
            'asset-deprecated-plus' => 'Block Plus: Asset (deprecated)', // @translate
            'asset-deprecated-class-url' => 'Block Plus: Asset class url (deprecated)', // @translate
            'asset-deprecated-left-right' => 'Block Plus: Left Right (deprecated)', // @translate
        ],
        'block' => [
            'block-arborescence' => 'Block Plus: Arborescence', // @translate
            'block-glossary' => 'Block Plus: Glossary', // @translate
        ],
        'breadcrumbs' => [
            'breadcrumbs-standard' => 'Block Plus: Omeka breadcrumbs', // @translate
        ],
        'browsePreview' => [
            'browse-preview-carousel' => 'Block Plus: Carousel', // @translate
            'browse-preview-gallery' => 'Block Plus: Gallery', // @translate
            'browse-preview-deprecated' => 'Block Plus: Browse preview (deprecated)', // @translate
        ],
        'media' => [
            'media-item-showcase-deprecated' => 'Block Plus: Item showcase (deprecated)', // @translate
        ],
        'html' => [
            'html-glossary' => 'Block Plus: Glossary', // @translate
            'html-page-header' => 'Block Plus: Page header', // @translate
        ],
        'itemWithMetadata' => [
            'item-with-metadata-deprecated' => 'Block Plus: Item with metadata (deprecated)', // @translate
        ],
        'listOfPages' => [
            'list-of-pages-deprecated' => 'Block Plus: List of pages (deprecated)', // @translate
        ],
        'listOfSites' => [
            'list-of-sites-deprecated' => 'Block Plus: List of sites (deprecated)', // @translate
        ],
        'pageDateTime' => [
            'page-date-time-plus' => 'Block Plus: Page date time', // @translate
        ],
        'tableOfContents' => [
            'table-of-contents-deprecated' => 'Block Plus: Table of contents (deprecated)', // @translate
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'js_translate_strings' => [
        'Class', // @translate
        'Url (deprecated)', // @translate
        'Insert Footnotes', // @translate
    ],
    'blockplus' => [
        'settings' => [
            'blockplus_html_mode_page' => 'inline',
            'blockplus_html_config_page' => 'default',
            'blockplus_property_itemset' => '',
        ],
        'site_settings' => [
            // Page metadata.
            'blockplus_page_types' => [
                'home' => 'Home', // @translate
                'exhibit' => 'Exhibit', // @translate
                'exhibit_page' => 'Exhibit page', // @translate
                'simple' => 'Simple page', // @translate
            ],
            // Breadcrumbs.
            'blockplus_breadcrumbs_crumbs' => [
                'home',
                'collections',
                'itemset',
                'itemsetstree',
                'current',
            ],
            'blockplus_breadcrumbs_prepend' => [],
            'blockplus_breadcrumbs_collections_url' => '',
            'blockplus_breadcrumbs_separator' => '',
            'blockplus_breadcrumbs_homepage' => false,
            // Resource blocks.
            // Buttons.
            'blockplus_block_buttons' => [],
            // Previous/Next resources.
            'blockplus_items_order_for_itemsets' => [],
            'blockplus_prevnext_items_query' => '',
            'blockplus_prevnext_item_sets_query' => '',
        ],
        'block_settings' => [
            'block' => [
                'params' => '',
            ],
            'breadcrumbs' => [
            ],
            'buttons' => [
                'buttons' => [],
            ],
            'd3Graph' => [
                'params' => '{
    "items": {
        "limit": 100
    } ,
    "item_sets": null,
    "relations": [
        "objects",
        "subjects",
        "item_sets"
    ],
    "config": {
        "height": 800,
        "forceCharge": -100,
        "forceLinkDistance": 100,
        "baseCirclePow": 0.6,
        "baseCircleMin": 5,
        "fontSizeTop": 35,
        "fontSizeMin": ".1px",
        "fontSizeMax": "16px"
    }
}
',
            ],
            'externalContent' => [
                'embeds' => [],
                'html' => '',
                'show_title_option' => 'title',
                'caption_position' => 'center',
                'link_text' => 'Know more', // @translate
                'link_url' => '#',
            ],
            'heading' => [
                'text' => '',
                'level' => '',
            ],
            // TODO Migrate itemSetShowcase to showcase or Media.
            'itemSetShowcase' => [
                'item_sets' => [],
                'thumbnail_type' => 'square',
                'show_title_option' => 'item_set_title',
            ],
            'links' => [
                'links' => [],
            ],
            // TODO Pull request diff in listOfSites in core or move it to module Internationalisation.
            // Diff with Omeka S: exclude more than current page.
            'listOfSites' => [
                'sort' => 'alpha',
                'limit' => null,
                // The standard block uses exclude_current only.
                'exclude_current' => true,
                'exclude' => [
                    // 'current',
                    // 'main',
                    // 'translated',
                ],
                'pagination' => false,
                'summaries' => true,
                'thumbnails' => true,
            ],
            'messages' => [
            ],
            'mirrorPage' => [
                'page' => null,
            ],
            'pageMetadata' => [
                'type' => '',
                'credits' => '',
                'summary' => '',
                'featured' => false,
                'tags' => [],
                'cover' => null,
                'params' => '',
                'attachments' => [],
            ],
            'redirectToUrl' => [
                'url' => '',
            ],
            'resourceText' => [
                'attachments' => [],
                'html' => '',
                'thumbnail_type' => 'square',
                'show_title_option' => 'item_title',
                // This option is mainly for compability with Omeka Classic exhibits.
                'caption_position' => 'center',
            ],
            'searchForm' => [
                'html' => '',
                'link' => '',
                'search_config' => null,
                'selector' => '',
            ],
            'searchResults' => [
                'resource_type' => 'items',
                'query' => [],
                'limit' => 12,
                'pagination' => true,
                'sort_headings' => [],
                'resource_template' => null,
            ],
            'showcase' => [
                'html' => '',
                'entries' => [],
                'thumbnail_type' => 'square',
                'show_title_option' => 'item_title',
            ],
            'tableOfContents' => [
                'depth' => 1,
                'root' => false,
            ],
            'treeStructure' => [
                'root' => '',
                'term' => 'dcterms:hasPart',
                'expanded' => 0,
            ],
            'twitter' => [
                'account' => '',
                'limit' => 1,
                'retweet' => false,
                'authorization' => '',
                'api' => '1.1',
                // Account data are stored because the id is required in Twitter api v2.
                'account_data' => [],
                // The bearer token is saved separately when it is an automatic one.
                'authorization_bearer' => '',
                // The guest token may be needed too.
                'guest_token' => '',
            ],
        ],
    ],
];
