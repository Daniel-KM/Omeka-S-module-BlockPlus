/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */
CKEDITOR.editorConfig = function( config ) {
    // Define changes to default configuration here.
    // For complete reference see:
    // http://docs.ckeditor.com/#!/api/CKEDITOR.config

    config.toolbar = [
        {
            name: 'advanced',
            items: [
                'Sourcedialog', '-',
                'Link', 'Unlink', 'Anchor', '-',
                'Format', 'Styles', 'PasteFromWord',
            ],
        },
        '/',
        {
            items: [
                    'Bold', 'Italic', 'Underline', 'Strike', 'RemoveFormat', '-',
                    'NumberedList', 'BulletedList', 'Indent', 'Outdent', 'Blockquote', '-',
                    'Footnotes', '-',
                    'Maximize',
                ],
        },
    ];

    // Disable content filtering
    config.allowedContent = true;
    config.extraPlugins = 'footnotes,sourcedialog';

    // Add some css to support attributes to "section", "li" and "sup" for footnotes.
    config.extraAllowedContent = 'section(footnotes);header;li[id,data-footnote-id];a[href,id,rel];cite;sup[data-footnote-id]';
};
