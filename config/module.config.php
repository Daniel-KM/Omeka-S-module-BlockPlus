<?php declare(strict_types=1);

namespace BlockPlus;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'assetElement' => View\Helper\AssetElement::class,
            'blockMetadata' => View\Helper\BlockMetadata::class,
            'pageMetadata' => View\Helper\PageMetadata::class,
            'pagesMetadata' => View\Helper\PagesMetadata::class,
            'thumbnailUrl' => View\Helper\ThumbnailUrl::class,
        ],
    ],
    'block_layouts' => [
        'invokables' => [
            'block' => Site\BlockLayout\Block::class,
            'browsePreview' => Site\BlockLayout\BrowsePreview::class,
            'd3Graph' => Site\BlockLayout\D3Graph::class,
            'division' => Site\BlockLayout\Division::class,
            'itemSetShowcase' => Site\BlockLayout\ItemSetShowcase::class,
            // Omeka core uses "itemShowCase" instead of "itemShowcase". Won't fix: https://github.com/omeka/omeka-s/pull/1431
            'itemShowCase' => Site\BlockLayout\ItemShowcase::class,
            'itemWithMetadata' => Site\BlockLayout\ItemWithMetadata::class,
            'links' => Site\BlockLayout\Links::class,
            'listOfSites' => Site\BlockLayout\ListOfSites::class,
            'pageMetadata' => Site\BlockLayout\PageMetadata::class,
            'pageTitle' => Site\BlockLayout\PageTitle::class,
            'redirectToUrl' => Site\BlockLayout\RedirectToUrl::class,
            'searchForm' => Site\BlockLayout\SearchForm::class,
            'searchResults' => Site\BlockLayout\SearchResults::class,
            'separator' => Site\BlockLayout\Separator::class,
            'tableOfContents' => Site\BlockLayout\TableOfContents::class,
            'treeStructure' => Site\BlockLayout\TreeStructure::class,
            'twitter' => Site\BlockLayout\Twitter::class,
        ],
        'factories' => [
            'asset' => Service\BlockLayout\AssetFactory::class,
            'externalContent' => Service\BlockLayout\ExternalContentFactory::class,
            'html' => Service\BlockLayout\HtmlFactory::class,
            'listOfPages' => Service\BlockLayout\ListOfPagesFactory::class,
            'mirrorPage' => Service\BlockLayout\MirrorPageFactory::class,
            'resourceText' => Service\BlockLayout\ResourceTextFactory::class,
        ],
        'aliases' => [
            'itemShowcase' => 'itemShowCase',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\Element\OptionalRadio::class => Form\Element\OptionalRadio::class,
            Form\AssetFieldset::class => Form\AssetFieldset::class,
            Form\BlockFieldset::class => Form\BlockFieldset::class,
            Form\BrowsePreviewFieldset::class => Form\BrowsePreviewFieldset::class,
            Form\D3GraphFieldset::class => Form\D3GraphFieldset::class,
            Form\DivisionFieldset::class => Form\DivisionFieldset::class,
            Form\ExternalContentFieldset::class => Form\ExternalContentFieldset::class,
            Form\HtmlFieldset::class => Form\HtmlFieldset::class,
            Form\ItemSetShowcaseFieldset::class => Form\ItemSetShowcaseFieldset::class,
            Form\ItemShowcaseFieldset::class => Form\ItemShowcaseFieldset::class,
            Form\ItemWithMetadataFieldset::class => Form\ItemWithMetadataFieldset::class,
            Form\ListOfPagesFieldset::class => Form\ListOfPagesFieldset::class,
            Form\ListOfSitesFieldset::class => Form\ListOfSitesFieldset::class,
            Form\MirrorPageFieldset::class => Form\MirrorPageFieldset::class,
            Form\PageTitleFieldset::class => Form\PageTitleFieldset::class,
            Form\RedirectToUrlFieldset::class => Form\RedirectToUrlFieldset::class,
            Form\ResourceTextFieldset::class => Form\ResourceTextFieldset::class,
            Form\SearchFormFieldset::class => Form\SearchFormFieldset::class,
            Form\SearchResultsFieldset::class => Form\SearchResultsFieldset::class,
            Form\SeparatorFieldset::class => Form\SeparatorFieldset::class,
            Form\TableOfContentsFieldset::class => Form\TableOfContentsFieldset::class,
            Form\TreeStructureFieldset::class => Form\TreeStructureFieldset::class,
            Form\TwitterFieldset::class => Form\TwitterFieldset::class,
            // Site config.
            Form\SiteSettingsFieldset::class => Form\SiteSettingsFieldset::class,
        ],
        'factories' => [
            Form\Element\SitesPageSelect::class => Service\Form\Element\SitesPageSelectFactory::class,
            Form\Element\TemplateSelect::class => Service\Form\Element\TemplateSelectFactory::class,
            Form\Element\ThumbnailTypeSelect::class => Service\Form\Element\ThumbnailTypeSelectFactory::class,
            Form\PageMetadataFieldset::class => Service\Form\PageMetadataFieldsetFactory::class,
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
    ],
    'blockplus' => [
        'site_settings' => [
            'blockplus_page_types' => [
                'home' => 'Home', // @translate
                'exhibit' => 'Exhibit', // @translate
                'exhibit_page' => 'Exhibit page', // @translate
                'simple' => 'Simple page', // @translate
            ],
        ],
        'block_settings' => [
            // The new source upstream "asset" block stores assets as attachments.
            'asset' => [
                'heading' => '',
                'assets' => [
                    [
                        'id' => null,
                        'page' => null,
                        'alt_link_title' => '',
                        'caption' => '',
                        'url' => '',
                        'class' => '',
                    ],
                ],
                'className' => '',
                'alignment' => 'default',
                'template' => '',
            ],
            'block' => [
                'heading' => '',
                'params' => '',
                'template' => '',
            ],
            'browsePreview' => [
                'heading' => '',
                'resource_type' => 'items',
                'query' => '',
                'limit' => 12,
                'components' => [
                    'resource-heading',
                    'resource-body',
                    'thumbnail',
                ],
                'pagination' => false,
                'sort_headings' => [],
                'resource_template' => null,
                'link-text' => 'Browse all', // @translate
                'template' => '',
            ],
            'd3Graph' => [
                'heading' => '',
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
                'template' => '',
            ],
            'division' => [
                'type' => 'start',
                'tag' => 'div',
                'class' => 'column',
            ],
            'externalContent' => [
                'heading' => '',
                'embeds' => [],
                'html' => '',
                'alignment' => 'left',
                'show_title_option' => 'title',
                'caption_position' => 'center',
                'link_text' => 'Know more', // @translate
                'link_url' => '#',
                'template' => '',
            ],
            'html' => [
                'heading' => '',
                'html' => '',
                'divclass' => '',
                'template' => '',
            ],
            'itemSetShowcase' => [
                'heading' => '',
                'item_sets' => [],
                'thumbnail_type' => 'square',
                'show_title_option' => 'item_set_title',
                'template' => '',
            ],
            'itemShowcase' => [
                'attachments' => [],
                'thumbnail_type' => 'square',
                'show_title_option' => 'item_title',
                'heading' => '',
                'template' => '',
            ],
            'itemWithMetadata' => [
                'attachments' => [],
                'heading' => '',
                'template' => '',
            ],
            'links' => [
                'heading' => '',
                'links' => [],
                'template' => '',
            ],
            // Use block Menu of module Menu is cleaner.
            'listOfPages' => [
                'heading' => '',
                'pagelist' => '',
                'template' => '',
            ],
            'listOfSites' => [
                'heading' => '',
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
                'template' => '',
            ],
            'mirrorPage' => [
                'page' => null,
            ],
            // Media embed is not available in BlockPlus.
            // 'media' => [],
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
            'pageTitle' => [
                'template' => '',
            ],
            'redirectToUrl' => [
                'url' => '',
            ],
            'resourceText' => [
                'heading' => '',
                'attachments' => [],
                'html' => '',
                'thumbnail_type' => 'square',
                'alignment' => 'left',
                'show_title_option' => 'item_title',
                // This option is mainly for compability with Omeka Classic exhibits.
                'caption_position' => 'center',
                'template' => '',
            ],
            'searchForm' => [
                'heading' => '',
                'template' => '',
            ],
            'searchResults' => [
                'heading' => '',
                'resource_type' => 'items',
                'query' => [],
                'limit' => 12,
                'pagination' => true,
                'sort_headings' => [],
                'resource_template' => null,
                'template' => '',
            ],
            'separator' => [
                'class' => '',
            ],
            'tableOfContents' => [
                'depth' => null,
                'heading' => '',
                'root' => false,
                'template' => '',
            ],
            'treeStructure' => [
                'heading' => '',
                'root' => '',
                'term' => 'dcterms:hasPart',
                'expanded' => 0,
                'template' => '',
            ],
            'twitter' => [
                'heading' => '',
                'account' => '',
                'limit' => 1,
                'retweet' => false,
                'authorization' => '',
                'api' => '1.1',
                'template' => '',
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
