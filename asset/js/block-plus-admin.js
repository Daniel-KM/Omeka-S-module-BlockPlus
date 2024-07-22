(function ($) {

    // Config copied from application/asset/js/site-page-edit.js for the sidebar asset.
    function wysiwyg(context) {
        var config = {
            toolbar:
            [
                ['Sourcedialog', 'Bold', 'Italic', 'Underline', 'Link', 'Unlink', 'PasteFromWord'],
            ],
            height: '96px'
        };

        context.find('.wysiwyg').each(function () {
            if ($(this).data('ckeditor-overridden')) {
                return;
            }
            var editor = $(this).ckeditor().editor;
            if (editor) {
                editor.destroy();
                $(this).next('.cke_textarea_inline').remove();
            }
            if ($(this).is('.caption')) {
                editor = CKEDITOR.inline(this, config)
            } else if (CKEDITOR.config.customHtmlMode === 'document') {
                editor = CKEDITOR.replace(this);
            } else {
                editor = CKEDITOR.inline(this);
            }
            $(this).data('ckeditorInstance', editor);
            $(this).data('ckeditor-overridden', true);
        })
    }

    $(document).ready(function () {

        // ckeditor for html textarea.
        // Override the feature in site-page-edit.
        $(document).on('o:ckeditor-config', function () {
            wysiwyg($(this));
        });

        // Fix issue with radios.
        $('.block.value .inputs input:radio').each(function() {
            $(this).prop('checked', $(this).prop('checked') || $(this).attr('checked') === 'checked');
        });

        /**
         * Block group plus.
         */

        const addBlockGroupPlus = $('<button>', {
            type: 'button',
            id: 'add-block-group-plus',
            value: 'addBlockGroupPlus',
            class: 'add-block-group-plus expand',
            title: Omeka.jsTranslate('Expand to select the list of block group layouts'),
            'aria-label': Omeka.jsTranslate('Expand to select the list of block group layouts'),
            'data-text-expand': Omeka.jsTranslate('Expand to select the list of block group layouts'),
            'data-text-collapse': Omeka.jsTranslate('Collapse the list of block group layouts'),
        }).append($('<span>', {class: 'add-block-plus fas fa-plus'}));

        const addBlockGroupList = $('<ul>', {
            id: 'block-group-layouts',
            class: 'collapsible add-block-group-list',
        });

        for (const [layout, data] of Object.entries(blockGroups)) {
            const buttonBlockGroup = $('<button>', {
                type: 'button',
                class: 'option',
                value: layout,
            });
            buttonBlockGroup.text(Omeka.jsTranslate(data.label));
            addBlockGroupList
                .append($('<li>')
                    .append(buttonBlockGroup)
                );
        }

        $('#new-block .sidebar-header')
            .append(addBlockGroupPlus)
            .append(addBlockGroupList);

        $(document).on('click', '#block-group-layouts button', function () {
            // Do not trigger another block group if one is pending.
            // TODO Use an async check.
            if (addBlockGroupPlus.data('is-pending')) {
                alert(Omeka.jsTranslate('Wait for previous block group to be prepared before adding a new one.'));
                return;
            }
            // Prepare display.
            const buttonBlockGroup = $(this);
            // Add block group. Grouped blocks are appended via the event "o:block-added".
            const blockGroupLayout = buttonBlockGroup.val();
            addBlockGroupPlus
                .data('is-pending', blockGroupLayout)
                .data('total-grouped-blocks', 0);
            $('#new-block button[value="blockGroup"]').click();
        });

        $('#blocks').on('o:block-added', '.block', function () {
            const blockGroupLayout = addBlockGroupPlus.data('is-pending');
            if (!blockGroupLayout || addBlockGroupPlus.data('total-grouped-blocks') === undefined) {
                return;
            }

            const thisBlock = $(this);
            let blockLayout = thisBlock.data('block-layout');
            const blockGroupData = blockGroups[blockGroupLayout];
            const groupedBlocks = blockGroupData.blocks ? blockGroupData.blocks : {};

            if (blockLayout === 'blockGroup') {
                // Append all grouped blocks.
                // TODO Use an async process (loop for), not a forEach, even if it simpler to reorder blocks.
                Object.values(groupedBlocks).forEach(groupedBlockData => {
                    $(`#new-block button[value="${groupedBlockData['o:layout']}"]`).click();
                    addBlockGroupPlus.data('total-grouped-blocks', addBlockGroupPlus.data('total-grouped-blocks') + 1);
                });
            } else {
                addBlockGroupPlus.data('total-grouped-blocks', addBlockGroupPlus.data('total-grouped-blocks') - 1);
            }

            // Check if all blocks are ready in order to finalize the process.
            if (addBlockGroupPlus.data('total-grouped-blocks') === 0) {
                // Get the last block group.
                const blockGroup = $('#blocks .block.block-group[data-block-layout="blockGroup"]').last();
                if (blockGroup.length) {
                    // Move grouped blocks to block group in the right order.
                    const blockGroupBlocks = blockGroup.find('.block-group-blocks');
                    for (var groupedBlockData of Object.values(groupedBlocks)) {
                        const groupedBlock = blockGroup.find(`~ .block[data-block-layout="${groupedBlockData['o:layout']}"]`).first();
                        if (groupedBlock.length) {
                            $(groupedBlock).detach().appendTo(blockGroupBlocks);
                            let blockGroupLayoutData = blockGroup.data('block-layout-data');
                            blockGroupLayoutData.span = blockGroupLayoutData.span ? ++blockGroupLayoutData.span : 1;
                            blockGroup.data('block-layout-data', blockGroupLayoutData);
                        }
                    }
                }
                // Finalize the process.
                addBlockGroupPlus
                    .removeData('is-pending')
                    .removeData('total-grouped-blocks');
            }
        });

        $(document).on('click', 'button.expand, button.collapse', function(e) {
            var message, eventName;
            var toggle = $(this);
            toggle.toggleClass('collapse').toggleClass('expand');
            if (toggle.hasClass('expand')) {
                message = toggle.data('text-expand') ? toggle.data('text-expand') : Omeka.jsTranslate('Expand');
                eventName = 'o:collapsed';
            } else {
                message = toggle.data('text-collapse') ? toggle.data('text-collapse') : Omeka.jsTranslate('Collapse');
                eventName = 'o:expanded';
            }
            toggle
                .attr('aria-label', message)
                .attr('title', message)
                .trigger(eventName);
        });

    });

})(jQuery);
