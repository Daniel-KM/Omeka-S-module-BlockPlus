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
            'hero' => Site\BlockLayout\Hero::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\HeroForm::class => Form\HeroForm::class,
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
            'hero' => [
                'asset' => null,
                'text' => '',
                'button' => 'Discover documents…', // @translate
                'url' => 'item',
            ],
        ],
    ],
];
