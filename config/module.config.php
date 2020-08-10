<?php
namespace BlockPlus;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'pageMetadata' => View\Helper\PageMetadata::class,
            'pagesMetadata' => View\Helper\PagesMetadata::class,
        ],
    ],
    'block_layouts' => [
        'invokables' => [
            'block' => Site\BlockLayout\Block::class,
            'browsePreview' => Site\BlockLayout\BrowsePreview::class,
            'column' => Site\BlockLayout\Column::class,
            'itemSetShowcase' => Site\BlockLayout\ItemSetShowcase::class,
            // Omeka core uses "itemShowCase" instead of "itemShowcase". Won't fix: https://github.com/omeka/omeka-s/pull/1431
            'itemShowCase' => Site\BlockLayout\ItemShowcase::class,
            'itemWithMetadata' => Site\BlockLayout\ItemWithMetadata::class,
            'listOfSites' => Site\BlockLayout\ListOfSites::class,
            'pageMetadata' => Site\BlockLayout\PageMetadata::class,
            'pageTitle' => Site\BlockLayout\PageTitle::class,
            'searchForm' => Site\BlockLayout\SearchForm::class,
            'searchResults' => Site\BlockLayout\SearchResults::class,
            'separator' => Site\BlockLayout\Separator::class,
            'tableOfContents' => Site\BlockLayout\TableOfContents::class,
            'treeStructure' => Site\BlockLayout\TreeStructure::class,
            'twitter' => Site\BlockLayout\Twitter::class,
        ],
        'factories' => [
            'assets' => Service\BlockLayout\AssetsFactory::class,
            'externalContent' => Service\BlockLayout\ExternalContentFactory::class,
            'html' => Service\BlockLayout\HtmlFactory::class,
            'mirrorPage' => Service\BlockLayout\MirrorPageFactory::class,
            'resourceText' => Service\BlockLayout\ResourceTextFactory::class,
        ],
        'aliases' => [
            'itemShowcase' => 'itemShowCase',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\AssetsFieldset::class => Form\AssetsFieldset::class,
            Form\BlockFieldset::class => Form\BlockFieldset::class,
            Form\BrowsePreviewFieldset::class => Form\BrowsePreviewFieldset::class,
            Form\ExternalContentFieldset::class => Form\ExternalContentFieldset::class,
            Form\HtmlFieldset::class => Form\HtmlFieldset::class,
            Form\ItemSetShowcaseFieldset::class => Form\ItemSetShowcaseFieldset::class,
            Form\ItemShowcaseFieldset::class => Form\ItemShowcaseFieldset::class,
            Form\ItemWithMetadataFieldset::class => Form\ItemWithMetadataFieldset::class,
            Form\ListOfSitesFieldset::class => Form\ListOfSitesFieldset::class,
            Form\MirrorPageFieldset::class => Form\MirrorPageFieldset::class,
            Form\PageTitleFieldset::class => Form\PageTitleFieldset::class,
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
            'assets' => [
                'heading' => '',
                'assets' => [
                    [
                        'asset' => null,
                        'title' => '',
                        'caption' => '',
                        'url' => '',
                        'class' => '',
                    ],
                ],
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
                'pagination' => false,
                'sort_headings' => [],
                'resource_template' => null,
                'link-text' => 'Browse all', // @translate
                'template' => '',
            ],
            'column' => [
                'type' => '',
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
            'listOfSites' => [
                'heading' => '',
                'sort' => 'alpha',
                'limit' => null,
                'exclude' => [
                ],
                'pagination' => false,
                'summaries' => true,
                'template' => '',
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
            'pageTitle' => [
                'template' => '',
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
            'mirrorPage' => [
                'page' => null,
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
                'template' => '',
            ],
        ],
    ],
];
