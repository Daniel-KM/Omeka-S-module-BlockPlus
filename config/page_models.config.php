<?php declare(strict_types=1);

namespace BlockPlus;

/**
 * The page models and groups of blocks are just a site page api representation
 * with a unique key to define the name, and optionaly a label "o:label" and a
 * caption "o:caption".
 *
 * The elements with a main key "o:layout_data" not null allow to define a whole
 * page and they can be selected when a new page is created. The other groups of
 * blocks can be selected in edition.
 *
 * It is useless to fill all default values, so the list of block names may be
 * enough to create a group of blocks.
 */

return [
    // Add a group for each page template.
    // TODO The empty key "" can be used to define the default page model.
    // TODO Manage the option "o:site" to create different models by site.
    'home_page' => [
        'o:label' => 'Home page', // @translate
        'o:caption' => 'A page model with blocks browse preview with a template for gallery, html, heading, and asset.', // @translate
        'o:layout_data' => [
            'template_name' => 'home-page',
        ],
        'o:block' => [
            [
                'o:layout' => 'browsePreview',
                'o:data' => [
                    'resource_type' => 'items',
                    'query' => 'has_media=1',
                    'limit' => 12,
                    'components' => ['resource-heading', 'resource-body', 'thumbnail'],
                    'link-text' => 'Browse all',
                ],
                'o:layout_data' => [
                    'template_name' => 'browse-preview-gallery',
                ],
            ],
            ['o:layout' => 'html'],
            ['o:layout' => 'heading'],
            ['o:layout' => 'asset'],
        ],
    ],
    'exhibit' => [
        'o:label' => 'Exhibit', // @translate
        'o:caption' => 'A page model for an exhibit summary, with blocks page title, html, heading, and list of pages.', // @translate
        'o:layout_data' => [
            'template_name' => 'exhibit',
        ],
        'o:block' => [
            ['o:layout' => 'pageTitle'],
            ['o:layout' => 'html'],
            [
                'o:layout' => 'heading',
                'o:data' => [
                    'text' => 'Pages',
                ],
            ],
            ['o:layout' => 'listOfPages'],
        ],
    ],
    'exhibit_page' => [
        'o:label' => 'Exhibit page', // @translate
        'o:caption' => 'A page model for an exhibit page, with blocks page title, html, heading and media.', // @translate
        'o:layout_data' => [
            'template_name' => 'exhibit',
        ],
        'o:block' => [
            ['o:layout' => 'pageTitle'],
            ['o:layout' => 'html'],
            ['o:layout' => 'heading'],
            ['o:layout' => 'media'],
        ],
    ],
    'simple_page' => [
        'o:label' => 'Simple page', // @translate
        'o:caption' => 'A page model for generic needs, with blocks page title and html.', // @translate
        'o:layout_data' => [
            'template_name' => 'exhibit',
        ],
        'o:block' => [
            ['o:layout' => 'pageTitle'],
            ['o:layout' => 'html'],
        ],
    ],

    // Add block groups.
    'resource_text' => [
        'o:label' => 'Resource with text', // @translate
        'o:caption' => 'A group of blocks with a heading, a media and a field for html.', // @translate
        'o:block' => [
            [
                'o:layout' => 'blockGroup',
                'o:data' => [
                    // Total of the following blocks that are nested in this group.
                    // If not set, all blocks until the next block group will be nested.
                    'span' => 3,
                ],
                'o:layout_data' => [
                    // Class "media-text" was the one used in block "Resource text".
                    // Class "block-group-resource-text" is the new one.
                    'class' => 'block-resource-text media-text',
                ],
            ],
            [
                'o:layout' => 'heading',
                'o:data' => [
                    'level' => 3,
                ],
            ],
            [
                'o:layout' => 'media',
            ],
            [
                'o:layout' => 'html',
            ],
        ],
    ],
];
