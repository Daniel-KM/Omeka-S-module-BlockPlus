$(document).ready(function() {

    // TODO Manage multiple skiplinks.
    var updateGlossary = function () {
        const skiplink = $('.glossary-alphabet a.current').data('skiplink');
        const perPage = $('.glossary-pagination').first().data('perPage');
        const totalCount = $('.glossary-items .glossary-item[data-skiplink="' + skiplink + '"]').length;
        const pageCount = perPage ? Math.ceil(totalCount / perPage) : 1;
        var currentPage = parseInt($('.glossary-pagination .pagination-current').val());
        currentPage = currentPage > 0 ? currentPage : 1;
        currentPage = currentPage > pageCount ? pageCount : currentPage;
        var offset = 0;
        var previousPage = null;
        var nextPage = null;
        if (perPage && pageCount > 1) {
            offset = (currentPage - 1) * perPage;
            previousPage = currentPage <= 1 ? null : currentPage - 1;
            nextPage = currentPage >= pageCount ? null : currentPage + 1;
        }

        $('.glossary-pagination .pagination-current').val(currentPage);
        $('.glossary-pagination .pagination-total').html(pageCount);
        $('.glossary-pagination .pagination-previous').replaceWith(
            previousPage
                ? `<a data-page="${ previousPage }" href="" class="pagination-previous" title="Précédent" aria-label="Précédent"></a>`
                : `<span class="pagination-previous" title="Sans précédent" aria-label="Sans précédent"></span>`
        );
        $('.glossary-pagination .pagination-next').replaceWith(
            nextPage
                ? `<a data-page="${ nextPage }" href="" class="pagination-next" title="Suivant" aria-label="Suivant"></a>`
                : `<span class="pagination-next" title="Sans suivant" aria-label="Sans suivant"></span>`
        );

        $('.glossary-items .glossary-item').hide();
        if (!perPage || pageCount === 1) {
            $('.glossary-items .glossary-item[data-skiplink="' + skiplink + '"]').show();
        } else {
            $('.glossary-items .glossary-item[data-skiplink="' + skiplink + '"]').slice(offset, offset + perPage).show();
        }
    };

    $(document).on('click', '.glossary-alphabet a', function(e) {
        e.preventDefault();
        const skiplink = $(this).text();
        $('.glossary-alphabet a.current').removeClass('current');
        $('.glossary-alphabet a[data-skiplink="' + skiplink + '"]').addClass('current');
        window.location.hash = skiplink;
        // Always reset to first page.
        $('.glossary-pagination .pagination-current').val(1);
        updateGlossary();
    });

    $(document).on('click', '.glossary-pagination a', function(e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        $('.glossary-pagination .pagination-current').val(page ? page : 1);
        updateGlossary();
    });

    $(document).on('submit', '.glossary-pagination form', function(e) {
        e.preventDefault();
        const page = parseInt($(this).find('input.pagination-current').val());
        $('.glossary-pagination .pagination-current').val(page ? page : 1);
        updateGlossary();
    });

});
