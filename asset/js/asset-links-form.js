(function ($) {
    /**
     * Augment the core asset-options sidebar with a "Resource link" section,
     * mirroring the existing "Page link" section but targeting any Omeka
     * resource (Item, Item set, Media). The hidden input is marked as
     * .asset-option so that the core populate/load loop in site-page-edit.js
     * synchronises values with the per-attachment .asset-resource-id input.
     */
    var pickingResourceForAssetLink = false;

    function injectResourceLinkSection(translate) {
        var sidebar = $('#asset-options');
        if (sidebar.length === 0 || sidebar.find('.resource-link').length > 0) {
            return;
        }
        var html =
            '<div class="resource-link" data-default-html="">' +
            '<h3>' + translate.resource + '</h3>' +
            '<div class="resource-status">' +
            '<div class="asset-option-selection">' +
            '<span class="selected-resource"></span>' +
            '<a href="" target="_blank" class="o-icon-external" aria-label="' + translate.preview + '" title="' + translate.preview + '"></a>' +
            '<span class="none-selected">' + translate.noneSelected + '</span>' +
            '<input type="hidden" name="asset-resource-id" id="asset-resource-id" value="" class="asset-option">' +
            '</div>' +
            '</div>' +
            '<button type="button" class="resource-select">' + translate.select + '</button>' +
            '<button type="button" class="resource-clear">' + translate.clear + '</button>' +
            '</div>';
        sidebar.find('#asset-options-confirm-panel').before(html);
        var defaultHtml = sidebar.find('.resource-link .asset-option-selection').html();
        sidebar.find('.resource-link').attr('data-default-html', defaultHtml);
    }

    function updateResourceDisplay(resourceId, title, url) {
        var sidebar = $('#asset-options');
        sidebar.find('#asset-resource-id').val(resourceId || '');
        sidebar.find('.selected-resource').text(title || '');
        sidebar.find('.selected-resource + a').attr('href', url || '');
    }

    function clearResourceDisplay() {
        var sidebar = $('#asset-options');
        var defaultHtml = sidebar.find('.resource-link').attr('data-default-html');
        if (defaultHtml) {
            sidebar.find('.resource-link .asset-option-selection').html(defaultHtml);
        }
    }

    $(document).ready(function () {
        var translate = {
            resource: $('body').data('translateAssetLinkResource') || 'Resource link',
            preview: $('body').data('translateAssetLinkPreview') || 'Preview',
            noneSelected: $('body').data('translateAssetLinkNoneSelected') || '[No resource selected]',
            select: $('body').data('translateAssetLinkSelect') || 'Select',
            clear: $('body').data('translateAssetLinkClear') || 'Clear'
        };
        injectResourceLinkSection(translate);

        // Toggle visibility of the resource section when the configure sidebar
        // is opened: only attachments belonging to an asset-links block expose
        // a .asset-resource-id hidden input.
        $('#blocks').on('click', '.asset-options-configure', function () {
            var attachment = $(this).closest('.attachment');
            var hasResource = attachment.find('input.asset-resource-id').length > 0;
            $('#asset-options .resource-link').toggle(hasResource);
            if (!hasResource) {
                return;
            }
            var resourceInput = attachment.find('input.asset-resource-id');
            var resourceId = resourceInput.val();
            if (resourceId) {
                updateResourceDisplay(
                    resourceId,
                    resourceInput.attr('data-resource-title'),
                    resourceInput.attr('data-resource-url')
                );
            } else {
                clearResourceDisplay();
            }
        });

        // Open the core resource selector sidebar from our section.
        $('#content').on('click', '#asset-options .resource-select', function (e) {
            e.preventDefault();
            pickingResourceForAssetLink = true;
            // Detach selecting-attachment so the core o:resource-selected
            // handler does not redirect into the item attachment-options flow.
            $('.selecting-attachment').removeClass('selecting-attachment');
            var sidebar = $('#select-resource');
            var url = sidebar.data('sidebar-content-url') || '/admin/item/sidebar-select';
            Omeka.populateSidebarContent(sidebar, url);
            Omeka.openSidebar(sidebar);
        });

        $('#content').on('click', '#asset-options .resource-clear', function (e) {
            e.preventDefault();
            clearResourceDisplay();
        });

        // Capture the resource selection. The core also listens to this event;
        // we close any sidebar it may have opened to keep our flow visible.
        $('#select-resource').on('o:resource-selected', '.select-resource', function () {
            if (!pickingResourceForAssetLink) {
                return;
            }
            var resource = $(this).closest('.resource').data('resource-values') || {};
            var siteSlug = $('.page-status').data('site-url') || '';
            var resourceUrl = resource.url
                || (siteSlug && resource.value_resource_name && resource.value_resource_id
                    ? siteSlug + '/' + (resource.value_resource_name === 'item_sets' ? 'item-set' : (resource.value_resource_name === 'items' ? 'item' : resource.value_resource_name)) + '/' + resource.value_resource_id
                    : '');
            updateResourceDisplay(
                resource.value_resource_id,
                resource.display_title,
                resourceUrl
            );
            pickingResourceForAssetLink = false;
            Omeka.closeSidebar($('#select-resource'));
            Omeka.closeSidebar($('#attachment-options'));
        });

        // Reset the flag if the user closes the resource sidebar without picking.
        $('#select-resource').on('o:sidebar-closed', function () {
            pickingResourceForAssetLink = false;
        });

        // When the user applies asset options, propagate the resource title /
        // url onto the per-attachment hidden input data attributes so the
        // values persist across re-opens within the same edit session.
        $('#content').on('click', '#asset-options-confirm-panel', function () {
            var attachment = $('.selecting.attachment');
            var resourceInput = attachment.find('input.asset-resource-id');
            if (resourceInput.length === 0) {
                return;
            }
            resourceInput
                .attr('data-resource-title', $('#asset-options .selected-resource').text())
                .attr('data-resource-url', $('#asset-options .selected-resource + a').attr('href') || '');
        });
    });
})(jQuery);
