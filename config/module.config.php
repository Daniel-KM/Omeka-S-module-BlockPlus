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
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\AssetsForm::class => Form\AssetsForm::class,
            Form\BrowsePreviewForm::class => Form\BrowsePreviewForm::class,
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
        ],
    ],
];
