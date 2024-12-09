$(document).ready(function() {

    $(document).on('click', '.dialog-header-close-button', function(e) {
        const dialog = this.closest('dialog.popup-dialog');
        $('body').removeClass('dialog-opened');
        if (dialog) {
            dialog.close();
        }
    });

    $(document).on('click', '.button-dialog', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const dialogName = $(this).data('dialog-name');
        const dialog = document.querySelector('dialog.popup-dialog[data-dialog-name="' + dialogName + '"]');
        if (dialog) {
            $('body').removeClass('dialog-opened');
            if ($(dialog).is(':hidden')) {
                $('body').addClass('dialog-opened');
                dialog.show();
            } else {
                dialog.close();
            }
        }
    });

});
