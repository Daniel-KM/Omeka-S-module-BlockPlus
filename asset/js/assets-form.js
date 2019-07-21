(function ($) {
    $(document).ready(function () {
        // TODO Make multiple assets form sortable and with subvalues (url and label).
        $('#content').on('click', '.asset-form-add', function () {
            var first = $(this).closest('.asset-form-element');
            var last = first.clone();
            $(last)
                .addClass('empty')
                .find('input[type=hidden]').val('').end()
                .find('img.selected-asset-image').attr('src', '').end()
                .find('.selected-asset-name').html('').end()
                .find('.selected-asset').hide();
            first.after(last);
        });
    });
})(jQuery);
