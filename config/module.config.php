<?php declare(strict_types=1);

namespace BlockPlus;

return [
    'service_manager' => [
        'invokables' => [
            Mvc\MvcListeners::class => Mvc\MvcListeners::class,
        ],
        'factories' => [
            // Override theme factory to inject module pages and block templates.
            // Copied in BlockPlus, Reference, Timeline.
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
            'captionClassAndUrl' => View\Helper\CaptionClassAndUrl::class,
            'ckEditor' => View\Helper\CkEditor::class,
            'isXml' => View\Helper\IsXml::class,
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
            'showcase' => Service\BlockLayout\ShowcaseFactory::class,
        ],
    ],
    'resource_page_block_layouts' => [
        'invokables' => [
            'block' => Site\ResourcePageBlockLayout\Block::class,
            'breadcrumbs' => Site\ResourcePageBlockLayout\Breadcrumbs::class,
            'buttons' => Site\ResourcePageBlockLayout\Buttons::class,
            'description' => Site\ResourcePageBlockLayout\Description::class,
            'downloadPrimary' => Site\ResourcePageBlockLayout\DownloadPrimary::class,
            // Keep logical order.
            'htmlDivStart' => Site\ResourcePageBlockLayout\HtmlDivStart::class,
            'htmlDivEnd' => Site\ResourcePageBlockLayout\HtmlDivEnd::class,
            'htmlDivToolsStart' => Site\ResourcePageBlockLayout\HtmlDivToolsStart::class,
            'htmlDivToolsEnd' => Site\ResourcePageBlockLayout\HtmlDivToolsEnd::class,
            'htmlSectionStart' => Site\ResourcePageBlockLayout\HtmlSectionStart::class,
            'htmlSectionEnd' => Site\ResourcePageBlockLayout\HtmlSectionEnd::class,
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
            Form\PageMetadataFieldset::class => Form\PageMetadataFieldset::class,
            Form\RedirectToUrlFieldset::class => Form\RedirectToUrlFieldset::class,
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
            Form\SearchFormFieldset::class => Service\Form\SearchFormFieldsetFactory::class,
        ],
    ],
    'page_templates' => [
        'home-page' => 'Block Plus: Home page', // @translate
        'exhibit' => 'Block Plus: Exhibit', // @translate
        'exhibit-page' => 'Block Plus: Exhibit page', // @translate
        'simple-page' => 'Block Plus: Simple page', // @translate
    ],
    'block_templates' => [
        'asset' => [
            'asset-class-url' => 'Block Plus: Asset with class and url', // @translate
            'asset-bootstrap-hero' => 'Block Plus: Bootstrap Hero', // @translate
            'asset-left-right' => 'Block Plus: Left Right', // @translate
            'asset-partners' => 'Block Plus: Partners', // @translate
            'asset-deprecated-plus' => 'Block Plus: Asset (deprecated)', // @translate
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
        'heading' => [
            'heading-link' => 'Block Plus: Heading link', // @translate
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
        // Warning: the original template for block Media is "file".
        'media' => [
            'media-item-showcase-deprecated' => 'Block Plus: Item showcase (deprecated)', // @translate
            'media-resource-text-deprecated' => 'Block Plus: Resource text (deprecated)', // @translate
        ],
        'pageDateTime' => [
            'page-date-time-plus' => 'Block Plus: Page date time', // @translate
        ],
        'searchResults' => [
            'search-results-browse-preview-deprecated' => 'Block Plus: Browse preview (deprecated)', // @translate
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
                'components' => [],
                'linkText' => '',
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
