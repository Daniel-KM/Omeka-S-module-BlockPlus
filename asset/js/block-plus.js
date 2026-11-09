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

    /**
     * Collapsible resource block: the button is moved before the parent
     * container, so it remains visible when the block is collapsed.
     */
    $('.block-collapsible-button').each(function() {
        const collapsible = document.getElementById(this.getAttribute('aria-controls'));
        const container = collapsible ? collapsible.parentElement : null;
        if (container && container.matches('.block')) {
            container.before(this);
        }
    });

    $(document).on('click', '.block-collapsible-button', function() {
        const button = $(this);
        const collapsible = $('#' + button.attr('aria-controls'));
        const isCollapsing = button.attr('aria-expanded') === 'true';
        collapsible.prop('hidden', isCollapsing)
            .parent().toggleClass('block-collapsible-collapsed', isCollapsing);
        button
            .attr('aria-expanded', isCollapsing ? 'false' : 'true')
            .text(button.data(isCollapsing ? 'label-collapse' : 'label-expand'));
        // Viewers (Mirador, Universal Viewer, etc.) resize on this event.
        window.dispatchEvent(new Event('resize'));
    });

});
