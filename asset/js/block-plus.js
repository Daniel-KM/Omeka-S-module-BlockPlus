(function ($) {

    $(document).ready(function () {

        // TODO Make multiple assets form sortable.
        // TODO Use the removed base fieldset as a hidden base.
        $('#content').on('click', '.asset-form-add', function () {
            var assets = $(this).closest('.assets-list');
            var current = $(this).closest('.asset-data');
            var next = current.clone();
            var nextIndex = assets.attr('data-next-index');
            $(next)
                .attr('data-index', nextIndex)
                .find('.asset-form-element input[type=hidden]').val('').end()
                .find('.asset-form-element img.selected-asset-image').attr('src', '').end()
                .find('.asset-form-element .selected-asset-name').html('').end()
                .find('.asset-form-element .selected-asset').hide().end();

            // Increment the index or each label and field.
            next
                .find('.inputs input, .inputs textarea').each(function() {
                    var name = $(this).attr('name');
                    var regex = /\[o:data\]\[assets\]\[\d+\]/gm;
                    var replace = '[o:data][assets][' + nextIndex + ']';
                    name = name.replace(regex, replace);
                    $(this)
                        .attr('id', name)
                        .attr('name', name);
                });

            next
                .find('.field-meta label').each(function() {
                    var name = $(this).attr('for');
                    var regex = /\[o:data\]\[assets\]\[\d+\]/gm;
                    var replace = '[o:data][assets][' + nextIndex + ']';
                    name = name.replace(regex, replace);
                    $(this)
                        .attr('for', name);
                });
            // Reset all values and content.
            next
                .find('.inputs input').val('').end()
                .find('.inputs textarea').html('');

            current.after(next);

            // TODO Use the standard Omeka editor (trigger on body; allow caption without asset).
            next
                .find('.cke').remove().end()
                .find('.inputs textarea').hide().removeClass('block-html full wysiwyg')
                .closest('.inputs').find('.cke_textarea_inline').remove();
            window.CKEDITOR.replace(next.find('textarea').attr('name'));

            assets.attr('data-next-index', parseInt(nextIndex) + 1);
        });

        $('#content').on('click', '.asset-form-remove', function () {
            $(this).closest('.asset-data').remove();
        });

        // Fix issue with radios.
        $('.block.value .inputs input:radio').each(function() {
            $(this).prop('checked', $(this).prop('checked') || $(this).attr('checked') === 'checked');
        });

    });

})(jQuery);
