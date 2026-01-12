/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */
CKEDITOR.editorConfig = function( config ) {
    // Define changes to default configuration here.
    // For complete reference see:
    // http://docs.ckeditor.com/#!/api/CKEDITOR.config

    config.preset = 'full';

    // Keep same as standard, but buttons may differ.

    // The toolbar groups arrangement, optimized for two toolbar rows.
    config.toolbarGroups = [
        { name: 'clipboard', groups: [
            'clipboard',
            'undo',
        ] },
        { name: 'editing', groups: [
            'find',
            'selection',
            // 'spellchecker',
         ] },
        { name: 'links' },
        { name: 'insert' },
        { name: 'forms' },
        { name: 'tools' },
        { name: 'document', groups: [
            'mode',
            'document',
            'doctools',
        ] },
        { name: 'others' },
        '/',
        { name: 'basicstyles', groups: [
            'basicstyles',
            'cleanup',
         ] },
        { name: 'paragraph', groups: [
            'list',
            'indent',
            'blocks',
            'align',
            'bidi',
         ] },
        { name: 'styles' },
        { name: 'colors' },
        // { name: 'about' }
    ];

    // Remove some buttons provided by the standard plugins, which are
    // not needed in the Standard(s) toolbar.
    // config.removeButtons = 'Underline,Subscript,Superscript';
    config.removeButtons = 'Source,Scayt';

    config.stylesSet = 'default:../../../../application/asset/js/custom-ckeditor-styles.js';
    // Disable content filtering
    config.allowedContent = true;
    // Add extra plugins
    config.extraPlugins = [
        'sourcedialog',
        'removeformat',
        'footnotes',
    ];

    // Add some css to support attributes to "section", "li" and "sup" for footnotes.
    config.extraAllowedContent = 'section(footnotes);header;li[id,data-footnote-id];a[href,id,rel];cite;sup[data-footnote-id]';

    // Allow other scripts to modify configuration.
    $(document).trigger('o:ckeditor-config', config);
};
