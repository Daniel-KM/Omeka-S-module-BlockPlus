(function ($) {

    // Config copied from application/asset/js/site-page-edit.js for the sidebar asset.
    function wysiwyg(context) {
        var config = {
            toolbar:
            [
                ['Sourcedialog', 'Bold', 'Italic', 'Underline', 'Link', 'Unlink', 'PasteFromWord'],
            ],
            height: '96px'
        };

        context.find('.wysiwyg').each(function () {
            if ($(this).data('ckeditor-overridden')) {
                return;
            }
            var editor = $(this).ckeditor().editor;
            if (editor) {
                editor.destroy();
                $(this).next('.cke_textarea_inline').remove();
            }
            if ($(this).is('.caption')) {
                editor = CKEDITOR.inline(this, config)
            } else if (CKEDITOR.config.customHtmlMode === 'document') {
                editor = CKEDITOR.replace(this);
            } else {
                editor = CKEDITOR.inline(this);
            }
            $(this).data('ckeditorInstance', editor);
            $(this).data('ckeditor-overridden', true);
        })
    }

    $(document).ready(function () {

        // ckeditor for html textarea.
        // Override the feature in site-page-edit.
        $(document).on('o:ckeditor-config', function () {
            wysiwyg($(this));
        });

        // Fix issue with radios.
        $('.block.value .inputs input:radio').each(function() {
            $(this).prop('checked', $(this).prop('checked') || $(this).attr('checked') === 'checked');
        });

    });

})(jQuery);
