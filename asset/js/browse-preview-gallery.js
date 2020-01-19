$(document).ready(function() {

$(function() {
    Grid.init({
        showVisitButton: false,
    });
    // TODO Fill items from Omeka api.
    $('#og-additems').on( 'click', function() {
        var $items = $( '' ).appendTo( $( '#og-grid' ) );
        Grid.addItems( $items );
        return false;
    } );
});

});
