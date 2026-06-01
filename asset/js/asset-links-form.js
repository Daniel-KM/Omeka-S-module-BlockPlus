(function ($) {
    /**
     * Augment the core asset-options sidebar with an "Asset resource link"
     * section, mirroring the existing "Page link" section but targeting any
     * Omeka resource (Item, Item set, Media). The hidden input is marked as
     * .asset-option so that the core populate/load loop in site-page-edit.js
     * synchronises values with the per-attachment .asset-resource-id input.
     *
     * Class names are prefixed with "asset-resource-" to avoid collision with
     * the core ".resource-link" rule (display: inline-flex on resource list
     * links) which would otherwise lay this section on a single line.
     */
    var pickingResourceForAssetLink = false;

    function injectResourceLinkSection(translate) {
        var sidebar = $('#asset-options');
        if (sidebar.length === 0 || sidebar.find('.asset-resource-link').length > 0) {
            return;
        }
        var html =
            '<div class="asset-resource-link" data-default-html="">' +
            '<h3>' + translate.resource + '</h3>' +
            '<div class="asset-resource-status">' +
            '<div class="asset-option-selection">' +
            '<span class="asset-selected-resource"></span>' +
            '<a href="" target="_blank" class="o-icon-external" hidden aria-label="' + translate.preview + '" title="' + translate.preview + '"></a>' +
            '<span class="none-selected">' + translate.noneSelected + '</span>' +
            '<input type="hidden" name="asset-resource-id" id="asset-resource-id" value="" class="asset-option">' +
            '</div>' +
            '</div>' +
            '<button type="button" class="asset-resource-select">' + translate.select + '</button>' +
            '<button type="button" class="asset-resource-clear">' + translate.clear + '</button>' +
            '</div>';
        // Insert inside .sidebar-content (sibling of .page-link), not before
        // #asset-options-confirm-panel which lives outside .sidebar-content and
        // would break the layout.
        var anchor = sidebar.find('.sidebar-content > .page-link');
        if (anchor.length) {
            anchor.after(html);
        } else {
            sidebar.find('.sidebar-content').append(html);
        }
        var defaultHtml = sidebar.find('.asset-resource-link .asset-option-selection').html();
        sidebar.find('.asset-resource-link').attr('data-default-html', defaultHtml);
    }

    function updateResourceDisplay(resourceId, title, url) {
        var sidebar = $('#asset-options');
        var hasResource = Boolean(resourceId);
        sidebar.find('#asset-resource-id').val(resourceId || '');
        sidebar.find('.asset-selected-resource').text(title || '');
        sidebar.find('.asset-selected-resource + a').attr('href', url || '');
        // Hide the "[No resource selected]" placeholder when a resource is set.
        sidebar.find('.asset-resource-link .none-selected').toggleClass(
            'inactive', hasResource
        );
        // Hide the external preview icon when no resource is set (otherwise it
        // would link to an empty href and float to the right of the row).
        sidebar.find('.asset-resource-link .o-icon-external').prop('hidden', !hasResource);
    }

    function clearResourceDisplay() {
        var sidebar = $('#asset-options');
        var defaultHtml = sidebar.find('.asset-resource-link').attr('data-default-html');
        if (defaultHtml) {
            sidebar.find('.asset-resource-link .asset-option-selection').html(defaultHtml);
        }
        sidebar.find('.asset-resource-link .none-selected').removeClass('inactive');
        sidebar.find('.asset-resource-link .o-icon-external').prop('hidden', true);
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
            $('#asset-options .asset-resource-link').toggle(hasResource);
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
        $('#content').on('click', '#asset-options .asset-resource-select', function (e) {
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

        $('#content').on('click', '#asset-options .asset-resource-clear', function (e) {
            e.preventDefault();
            clearResourceDisplay();
        });

        // Intercept the click on a resource line in capture phase, before the
        // core delegated handler triggers o:resource-selected. The core flow
        // ends with openAttachmentOptions(), which opens an extra sidebar
        // (caption + "Apply changes"); that step is irrelevant here, where we
        // only need the resource id/title/url. Capture +
        // stopImmediatePropagation bypass the core entirely for our picker
        // mode.
        document.addEventListener('click', function (e) {
            if (!pickingResourceForAssetLink) {
                return;
            }
            var target = e.target.closest ? e.target.closest('.select-resource') : null;
            if (!target) {
                return;
            }
            var sidebarEl = document.getElementById('select-resource');
            if (!sidebarEl || !sidebarEl.contains(target)) {
                return;
            }
            // Quick-select multi-pick mode: leave it to the core.
            if (sidebarEl.querySelector('#item-results.active')) {
                return;
            }
            e.stopImmediatePropagation();
            e.preventDefault();
            var resource = $(target).closest('.resource').data('resource-values') || {};
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
            Omeka.closeSidebar($('#resource-details'));
            Omeka.closeSidebar($('#attachment-options'));
        }, true);

        // Reset the flag if the user closes the resource sidebar without
        // picking.
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
                .attr('data-resource-title', $('#asset-options .asset-selected-resource').text())
                .attr('data-resource-url', $('#asset-options .asset-selected-resource + a').attr('href') || '');
        });
    });
})(jQuery);
