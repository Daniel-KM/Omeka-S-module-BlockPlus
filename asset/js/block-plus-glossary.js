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
        const prevLabel = Omeka.jsTranslate('Previous');
        const prevDisabledLabel = Omeka.jsTranslate('No previous');
        const nextLabel = Omeka.jsTranslate('Next');
        const nextDisabledLabel = Omeka.jsTranslate('No next');
        $('.glossary-pagination .pagination-previous').replaceWith(
            previousPage
                ? `<a data-page="${ previousPage }" href="" class="pagination-previous" title="${prevLabel}" aria-label="${prevLabel}"></a>`
                : `<span class="pagination-previous" title="${prevDisabledLabel}" aria-label="${prevDisabledLabel}"></span>`
        );
        $('.glossary-pagination .pagination-next').replaceWith(
            nextPage
                ? `<a data-page="${ nextPage }" href="" class="pagination-next" title="${nextLabel}" aria-label="${nextLabel}"></a>`
                : `<span class="pagination-next" title="${nextDisabledLabel}" aria-label="${nextDisabledLabel}"></span>`
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

    // Handle url fragment on page load (for example #W).
    var initFromHash = function() {
        const hash = window.location.hash;
        if (hash && hash.length > 1) {
            const skiplink = decodeURIComponent(hash.substring(1));
            const $link = $('.glossary-alphabet a[data-skiplink="' + skiplink + '"]');
            if ($link.length) {
                $('.glossary-alphabet a.current').removeClass('current');
                $link.addClass('current');
                $('.glossary-pagination .pagination-current').val(1);
                updateGlossary();
            }
        }
    };

    // Initialize on page load.
    initFromHash();

    // Handle browser back/forward navigation.
    $(window).on('hashchange', function() {
        initFromHash();
    });

});
