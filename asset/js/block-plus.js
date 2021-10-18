(function ($) {

    $(document).ready(function () {

        // Manage fields "class" and "url" (deprecated) for block Asset.
        // Other blocks that use the helper don't need these fields.
        // TODO Check validity with timeline.
        // FIXME First load of the block.
        const blockAsset = `
<div class="attachment-class">
    <h3 id="attachment-class-label">${Omeka.jsTranslate('Class')}</h3>
    <input type="text" name="asset-class" aria-labelledby="attachment-class-label" class="asset-option"/>
</div>
<div class="attachment-url">
    <h3 id="attachment-url-label">${Omeka.jsTranslate('Url (deprecated)')}</h3>
    <input type="text" name="asset-url" aria-labelledby="attachment-url-label" class="asset-option"/>
</div>
`;
        $('#asset-options .sidebar-content').append(blockAsset);

        // $('#asset-options .sidebar-content .description texarea').addClass('block-html full wysiwyg');

        $(document).on('o:sidebar-opened', '.sidebar', function () {
            $('#asset-options .sidebar-content').find('.attachment-class, .attachment-url').show();
            // TODO Ckeditor for assets.
            // window.CKEDITOR.replace($('#asset-options .sidebar-content').find('.description textarea'));
        });

        // See js/site-page-edit.js.
        $(document).on('o:sidebar-closed', '.sidebar', function () {
            $('#asset-options .sidebar-content').find('.attachment-class, .attachment-url').hide();
        });

        // Fix issue with radios.
        $('.block.value .inputs input:radio').each(function() {
            $(this).prop('checked', $(this).prop('checked') || $(this).attr('checked') === 'checked');
        });

    });

})(jQuery);
