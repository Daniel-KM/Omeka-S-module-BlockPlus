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
            'assets' => Site\BlockLayout\Assets::class,
            'browsePreview' => Site\BlockLayout\BrowsePreview::class,
            // TODO Omeka core use "itemShowCase" instead of "itemShowcase".
            'itemShowCase' => Site\BlockLayout\ItemShowcase::class,
        ],
        'factories' => [
            'mediaText' => Service\BlockLayout\MediaTextFactory::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\AssetsFieldset::class => Form\AssetsFieldset::class,
            Form\BrowsePreviewFieldset::class => Form\BrowsePreviewFieldset::class,
            Form\ItemShowcaseFieldset::class => Form\ItemShowcaseFieldset::class,
            Form\MediaTextFieldset::class => Form\MediaTextFieldset::class,
        ],
        'factories' => [
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
                // Each asset is an array with asset id and optional url and label.
                'assets' => [],
                'partial' => null,
            ],
            'browsePreview' => [
                'resource_type' => 'items',
                'query' => '',
                'limit' => 12,
                'heading' => '',
                'link-text' => 'Browse all', // @translate
                'partial' => '',
            ],
            'itemShowcase' => [
                'attachments' => [],
                'thumbnail_type' => 'square',
                'show_title_option' => 'item_title',
                'heading' => '',
                'partial' => '',
            ],
            'mediaText' => [
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
        ],
    ],
];
