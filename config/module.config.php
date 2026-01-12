<?php declare(strict_types=1);

namespace BlockPlus;

// Page models and blocks group are stored in the same place, with the key "page_models".
$pageModels = include __DIR__ . '/page_models.config.php';

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
            'downloadZip' => View\Helper\DownloadZip::class,
            'isHtml4' => View\Helper\IsHtml4::class,
            'pageMetadata' => View\Helper\PageMetadata::class,
            'pagesMetadata' => View\Helper\PagesMetadata::class,
            'primaryItemSet' => View\Helper\PrimaryItemSet::class,
            'thumbnailUrl' => View\Helper\ThumbnailUrl::class,
        ],
        'factories' => [
            'blockGroupData' => Service\ViewHelper\BlockGroupDataFactory::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            Controller\Site\DownloadController::class => Controller\Site\DownloadController::class,
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'download-zip' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/download/:resource-type[/:resource-id]',
                            'constraints' => [
                                'resource-type' => 'resource|item|media',
                                'resource-id' => '\d+',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'BlockPlus\Controller\Site',
                                'controller' => 'DownloadController',
                                'action' => 'download',
                            ],
                        ],
                    ],
                ],
            ],
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
            'mappingMapSearch' => Service\BlockLayout\MappingMapSearchFactory::class,
            'mirrorPage' => Service\BlockLayout\MirrorPageFactory::class,
            'showcase' => Service\BlockLayout\ShowcaseFactory::class,
        ],
    ],
    'resource_page_block_layouts' => [
        'invokables' => [
            'block' => Site\ResourcePageBlockLayout\Block::class,
            'breadcrumbs' => Site\ResourcePageBlockLayout\Breadcrumbs::class,
            'buttons' => Site\ResourcePageBlockLayout\Buttons::class,
            'citationResource' => Site\ResourcePageBlockLayout\CitationResource::class,
            'description' => Site\ResourcePageBlockLayout\Description::class,
            'downloadPrimary' => Site\ResourcePageBlockLayout\DownloadPrimary::class,
            'downloadZip' => Site\ResourcePageBlockLayout\DownloadZip::class,
            // Keep logical order.
            'htmlArticleStart' => Site\ResourcePageBlockLayout\HtmlArticleStart::class,
            'htmlArticleEnd' => Site\ResourcePageBlockLayout\HtmlArticleEnd::class,
            'htmlAsideStart' => Site\ResourcePageBlockLayout\HtmlAsideStart::class,
            'htmlAsideEnd' => Site\ResourcePageBlockLayout\HtmlAsideEnd::class,
            'htmlDivStart' => Site\ResourcePageBlockLayout\HtmlDivStart::class,
            'htmlDivEnd' => Site\ResourcePageBlockLayout\HtmlDivEnd::class,
            'htmlDivMoreStart' => Site\ResourcePageBlockLayout\HtmlDivMoreStart::class,
            'htmlDivMoreEnd' => Site\ResourcePageBlockLayout\HtmlDivMoreEnd::class,
            'htmlDivToolsStart' => Site\ResourcePageBlockLayout\HtmlDivToolsStart::class,
            'htmlDivToolsEnd' => Site\ResourcePageBlockLayout\HtmlDivToolsEnd::class,
            'htmlSectionStart' => Site\ResourcePageBlockLayout\HtmlSectionStart::class,
            'htmlSectionEnd' => Site\ResourcePageBlockLayout\HtmlSectionEnd::class,
            'linkedResourcesByItemSet' => Site\ResourcePageBlockLayout\LinkedResourcesByItemSet::class,
            'mediaPartOfItem' => Site\ResourcePageBlockLayout\MediaPartOfItem::class,
            'messages' => Site\ResourcePageBlockLayout\Messages::class,
            'noItem' => Site\ResourcePageBlockLayout\NoItem::class,
            'noMedia' => Site\ResourcePageBlockLayout\NoMedia::class,
            'previousNext' => Site\ResourcePageBlockLayout\PreviousNext::class,
            'printPage' => Site\ResourcePageBlockLayout\PrintPage::class,
            'resourceType' => Site\ResourcePageBlockLayout\ResourceType::class,
            'seeAlso' => Site\ResourcePageBlockLayout\SeeAlso::class,
            'thumbnail' => Site\ResourcePageBlockLayout\Thumbnail::class,
            'title' => Site\ResourcePageBlockLayout\Title::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
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
            Form\Element\PageModelSelect::class => Service\Form\Element\PageModelSelectFactory::class,
            /** @deprecated Since Omeka S v4.1, use core block template mechanism. Will be removed once all modules will be upgraded. */
            Form\Element\TemplateSelect::class => Service\Form\Element\TemplateSelectFactory::class,
            Form\SearchFormFieldset::class => Service\Form\SearchFormFieldsetFactory::class,
            Form\SitePageForm::class => Service\Form\SitePageFormFactory::class,
        ],
        'aliases' => [
            // The site page form does not implement form events, so override it for now.
            \Omeka\Form\SitePageForm::class => Form\SitePageForm::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'pageModels' => Service\ControllerPlugin\PageModelsFactory::class,
        ],
    ],
    'page_models' => $pageModels,
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
            'asset-skip' => 'Block Plus: Skip', // @translate
            'asset-deprecated-plus' => 'Block Plus: Asset (deprecated)', // @translate
        ],
        'block' => [
            'block-arborescence' => 'Block Plus: Arborescence', // @translate
            'block-glossary' => 'Block Plus: Glossary', // @translate
            'block-html' => 'Block Plus: Html', // @translate
        ],
        'breadcrumbs' => [
            'breadcrumbs-standard' => 'Block Plus: Omeka breadcrumbs', // @translate
        ],
        'browsePreview' => [
            'browse-preview-carousel' => 'Block Plus: Carousel', // @translate
            'browse-preview-filter-year' => 'Block Plus: Filter by year', // @translate
            'browse-preview-gallery' => 'Block Plus: Gallery (square)', // @translate
            'browse-preview-gallery-medium' => 'Block Plus: Gallery (medium)', // @translate
            'browse-preview-gallery-medium-item' => 'Block Plus: Gallery (medium, link to item)', // @translate
            'browse-preview-subjects' => 'Block Plus: Subjects', // @translate
            'browse-preview-timeline-list' => 'Block Plus: Timeline list', // @translate
            'browse-preview-deprecated' => 'Block Plus: Browse preview (deprecated)', // @translate
        ],
        'externalContent' => [
            'external-content-html' => 'Block Plus: Include html from group', // @translate
        ],
        'heading' => [
            'heading-link' => 'Block Plus: Heading link', // @translate
            'heading-details-start' => 'Block Plus: Details/summary (start)', // @translate
            'heading-details-end' => 'Block Plus: Details/summary (end)', // @translate
            'heading-skip' => 'Block Plus: Skip', // @translate
        ],
        'html' => [
            'html-accordion' => 'Block Plus: Accordion (h3)', // @translate
            'html-anchor' => 'Block Plus: Anchor for headings', // @translate
            'html-dialog' => 'Block Plus: Dialog (class for name)', // @translate
            'html-glossary' => 'Block Plus: Glossary', // @translate
            'html-page-header' => 'Block Plus: Page header', // @translate
            'html-skip' => 'Block Plus: Skip', // @translate
        ],
        'itemWithMetadata' => [
            'item-with-metadata-deprecated' => 'Block Plus: Item with metadata (deprecated)', // @translate
        ],
        'lineBreak' => [
            'list-break-skip' => 'Block Plus: Skip', // @translate
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
        'pageTitle' => [
            'page-title-skip' => 'Block Plus: Skip', // @translate
        ],
        'searchResults' => [
            'search-results-browse-preview-deprecated' => 'Block Plus: Browse preview (deprecated)', // @translate
        ],
        'showcase' => [
            'showcase-html' => 'Block Plus: Include html from group', // @translate
        ],
        'tableOfContents' => [
            'table-of-contents-deprecated' => 'Block Plus: Table of contents (deprecated)', // @translate
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => \Laminas\I18n\Translator\Loader\Gettext::class,
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'js_translate_strings' => [
        'Cancel', // @translate
        'Class', // @translate
        'Collapse the list of groups of blocks', // @translate
        'Confirm download', // @translate
        'Download', // @translate
        'Expand to display the list of groups of blocks', // @translate
        'Insert Footnotes', // @translate
        'Page metadata', // @translate
        'Please wait for previous group of blocks to be prepared before adding a new one.', // @translate
        'This group does not contain any block.', // @translate
        'Url (deprecated)', // @translate
    ],
    'blockplus' => [
        'settings' => [
            'blockplus_html_mode_page' => 'inline',
            'blockplus_html_config_page' => 'default',
            'blockplus_page_models' => [],
            'blockplus_property_itemset' => '',
        ],
        'site_settings' => [
            // Layouts.
            'blockplus_page_model_skip_blockplus' => false,
            'blockplus_page_model_rights' => false,
            'blockplus_page_models' => [],
            // Breadcrumbs.
            'blockplus_breadcrumbs_crumbs' => [
                'home',
                'collections',
                'itemset',
                'itemsetstree',
                'current',
                // 'current_link',
            ],
            'blockplus_breadcrumbs_prepend' => [],
            'blockplus_breadcrumbs_collections_url' => '',
            'blockplus_breadcrumbs_separator' => '',
            'blockplus_breadcrumbs_homepage' => false,
            // Resource blocks.
            // Buttons.
            'blockplus_block_buttons' => [],
            'blockplus_block_display_as_button' => false,
            // Download zip.
            'blockplus_download_enabled' => false,
            'blockplus_download_content' => 'all',
            'blockplus_download_type' => 'original',
            'blockplus_download_single_as_file' => false,
            'blockplus_download_max' => 25,
            'blockplus_download_zip_text' => <<<'TXT'
                Source: {main_title}
                Document: {resource_title}
                Author: {dcterms:creator}
                Date: {dcterms:date}
                Number of files: {file_count}
                Downloaded on: {date}
                
                Citation: {citation}
                
                URL: {resource_url}
                TXT,
            // See also (similar resources).
            // TODO How to implement heading for all resource blocks?
            'blockplus_seealso_heading' => 'See also', // @translate
            'blockplus_seealso_limit' => 4,
            'blockplus_seealso_pool' => '',
            'blockplus_seealso_properties' => [],
            'blockplus_seealso_all_sites' => false,
            // Previous/Next resources.
            'blockplus_items_order_for_itemsets' => [],
            'blockplus_prevnext_items_query' => '',
            'blockplus_prevnext_item_sets_query' => '',
        ],
        'block_settings' => [
            'block' => [
                'params' => '',
                'params_type' => 'auto',
            ],
            'breadcrumbs' => [
            ],
            'buttons' => [
                'buttons' => [],
                'display_as_button' => false,
            ],
            'd3Graph' => [
                'params' => <<<'JSON'
                {
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
                JSON,
            ],
            'externalContent' => [
                'embeds' => [],
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
                'components' => [
                    'heading',
                    'body',
                    'thumbnail',
                ],
                'thumbnail_type' => 'square',
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
            'mappingMapSearch' => [
                // This is a derivative from block Mapping Map Query from module Mapping.
                // The form and data the same than the original block.
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
                'link' => '',
                'search_config' => null,
                'selector' => '',
            ],
            'searchResults' => [
                'resource_type' => 'items',
                'query' => [],
                'limit' => 12,
                'pagination' => true,
                'autoscroll' => false,
                'sort_headings' => [],
                'resource_template' => null,
                'components' => [
                    'search-form',
                    'resource-heading',
                    'resource-body',
                    'thumbnail',
                ],
                'properties' => [],
                'linkText' => '',
            ],
            'showcase' => [
                'entries' => [],
                'components' => [
                    'heading',
                    'caption',
                    'body',
                    'media',
                    // 'thumbnail',
                ],
                'layout' => '',
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
