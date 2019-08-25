<?php
namespace BlockPlus;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'block_layouts' => [
        'invokables' => [
            'block' => Site\BlockLayout\Block::class,
            'browsePreview' => Site\BlockLayout\BrowsePreview::class,
            // TODO Omeka core uses "itemShowCase" instead of "itemShowcase".
            'itemShowCase' => Site\BlockLayout\ItemShowcase::class,
            'listOfSites' => Site\BlockLayout\ListOfSites::class,
            'searchForm' => Site\BlockLayout\SearchForm::class,
            'separator' => Site\BlockLayout\Separator::class,
        ],
        'factories' => [
            'assets' => Service\BlockLayout\AssetsFactory::class,
            'embedText' => Service\BlockLayout\EmbedTextFactory::class,
            'html' => Service\BlockLayout\HtmlFactory::class,
            'resourceText' => Service\BlockLayout\ResourceTextFactory::class,
            'simplePage' => Service\BlockLayout\SimplePageFactory::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\AssetsFieldset::class => Form\AssetsFieldset::class,
            Form\BlockFieldset::class => Form\BlockFieldset::class,
            Form\BrowsePreviewFieldset::class => Form\BrowsePreviewFieldset::class,
            Form\EmbedTextFieldset::class => Form\EmbedTextFieldset::class,
            Form\HtmlFieldset::class => Form\HtmlFieldset::class,
            Form\ItemShowcaseFieldset::class => Form\ItemShowcaseFieldset::class,
            Form\ListOfSitesFieldset::class => Form\ListOfSitesFieldset::class,
            Form\ResourceTextFieldset::class => Form\ResourceTextFieldset::class,
            Form\SearchFormFieldset::class => Form\SearchFormFieldset::class,
            Form\SeparatorFieldset::class => Form\SeparatorFieldset::class,
            Form\SimplePageFieldset::class => Form\SimplePageFieldset::class,
        ],
        'factories' => [
            Form\Element\PartialSelect::class => Service\Form\Element\PartialSelectFactory::class,
            Form\Element\SitesPageSelect::class => Service\Form\Element\SitesPageSelectFactory::class,
            Form\Element\ThumbnailTypeSelect::class => Service\Form\Element\ThumbnailTypeSelectFactory::class,
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
                'partial' => '',
            ],
            'block' => [
                'heading' => '',
                'params' => '',
                'partial' => '',
            ],
            'browsePreview' => [
                'resource_type' => 'items',
                'query' => '',
                'limit' => 12,
                'heading' => '',
                'link-text' => 'Browse all', // @translate
                'partial' => '',
            ],
            'embedText' => [
                'heading' => '',
                'embeds' => [],
                'html' => '',
                'alignment' => 'left',
                'show_title_option' => 'title',
                'caption_position' => 'center',
                'link_text' => 'Know more', // @translate
                'link_url' => '#',
                'partial' => '',
            ],
            'html' => [
                'heading' => '',
                'html' => '',
                'partial' => '',
            ],
            'itemShowcase' => [
                'attachments' => [],
                'thumbnail_type' => 'square',
                'show_title_option' => 'item_title',
                'heading' => '',
                'partial' => '',
            ],
            'listOfSites' => [
                'heading' => '',
                'sort' => 'alpha',
                'limit' => null,
                'pagination' => false,
                'summaries' => true,
                'partial' => '',
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
                'partial' => '',
            ],
            'searchForm' => [
                'heading' => '',
                'partial' => '',
            ],
            'separator' => [
                'class' => '',
            ],
            'simplePage' => [
                'page' => null,
            ],
        ],
    ],
];
