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

    $(document).on('click', '.block-collapsible-button', function() {
        const button = $(this);
        const isCollapsing = button.attr('aria-expanded') === 'true';
        $('#' + button.attr('aria-controls')).prop('hidden', isCollapsing);
        button.closest('.block-collapsible').parent()
            .toggleClass('block-collapsible-collapsed', isCollapsing);
        button
            .attr('aria-expanded', isCollapsing ? 'false' : 'true')
            .text(button.data(isCollapsing ? 'label-collapse' : 'label-expand'));
        // Viewers (Mirador, Universal Viewer, etc.) resize on this event.
        window.dispatchEvent(new Event('resize'));
    });

});
