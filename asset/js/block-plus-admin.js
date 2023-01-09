(function ($) {

        // Manage supplementary field "class" for block Asset.
        // Other blocks that use the helper don't need these fields.
        // See application/view/common/asset-options.phtml.
        // See application/asset/js/site-page-edit.js.

        const blockAsset = `
<div class="attachment-class">
    <h3 id="attachment-class-label">${Omeka.jsTranslate('Class')}</h3>
    <input type="text" name="asset-class" aria-labelledby="attachment-class-label" class="asset-option"/>
</div>
`;

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
            var editor = null;
            if ($(this).is('.caption')) {
                editor = CKEDITOR.inline(this, config)
            } else {
                editor = CKEDITOR.inline(this);
            }
            $(this).data('ckeditorInstance', editor);
        })
    }

    function updateSidebarAssetForm() {
        const sidebarContent = $('#asset-options .sidebar-content');
        if (!sidebarContent.find('.attachment-class').length) {
            sidebarContent.append(blockAsset);
            const sidebarCaption = sidebarContent.find('.description textarea');
            sidebarCaption.addClass('caption wysiwyg');
            if (sidebarCaption.ckeditor().editor) {
                sidebarCaption.ckeditor().editor.destroy();
            }
            wysiwyg(sidebarCaption.parent());
        }
    }

    $(document).ready(function () {

        $(document).on('o:sidebar-content-loaded', updateSidebarAssetForm);

        $(document).on('click', '.block[data-block-layout="asset"] .asset-options-configure', function() {
            updateSidebarAssetForm();
            const sidebarContent = $('#asset-options .sidebar-content');
            const  selectingAttachment = $(this).closest('.attachment');
            sidebarContent.find('.attachment-class [name="asset-class"]').val(selectingAttachment.find('.asset-class').val());
        });

        $(document).on('o:sidebar-opened', '.sidebar', function () {
            updateSidebarAssetForm();
            $('#asset-options .sidebar-content').find('.attachment-class').show();
        });

        // Fix issue with radios.
        $('.block.value .inputs input:radio').each(function() {
            $(this).prop('checked', $(this).prop('checked') || $(this).attr('checked') === 'checked');
        });

    });

})(jQuery);
