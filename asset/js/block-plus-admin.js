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
         *
         * Note: the methods inside site-page-edit.js are not available.
         *
         * The config of blocks should use `o:label`, `o:block`, `o:layout`, `o:data`
         * and `o:layout_data`, but `label`, `block`, `layout`, `data`, `layout_data`
         * are allowed for simplicity.
         */

        /**
         * Set data and layout data to a block.
         */
        function updateBlockGroup(block, blockSettings) {
            if (!block || !block.length || !blockSettings || !Object.keys(blockSettings).length) {
                return;
            }

            const blockLayout = block.data('block-layout');
            const blockInputLayout = block.find(`.block-content input[type="hidden"][value="${blockLayout}"]`).first();
            if (!blockInputLayout || !blockInputLayout.attr('name') || !blockInputLayout.attr('name').length) {
                return;
            }

            //  The block name should be "o:block[XXX][o:layout]".
            const blockIndex = blockInputLayout.attr('name').substring(blockInputLayout.attr('name').indexOf('[') + 1, blockInputLayout.attr('name').indexOf(']'))
            if (!blockIndex.length) {
                return;
            }

            // The block settings should use "o:data" but "data" is allowed for
            // simplicity. They are be mixed.
            for (const [key, value] of Object.entries(blockSettings['o:data'] ?? blockSettings['data'] ?? {})) {
                const isValueMultiple = value instanceof Array;
                const appendMultiple = isValueMultiple ? '[]' : '';
                const inputName = `.block-content [name="o:block[${blockIndex}][o:data][${key}]${appendMultiple}"]`;
                const input = block.find(inputName);
                if (input.length) {
                    var val, inp;
                    const inputType = input.attr('type') ?? input.prop('tagName').toLowerCase();
                    // Manage chosen-js and ckeditor, and bad config or upgrade.
                    // In jquery, "prop" uses true/false, and "attr" uses checked.
                    if (inputType === 'radio') {
                        val = isValueMultiple ? value[0] : value;
                        block.find(inputName + `[value="${val}"]`).prop('checked', true).trigger('change');
                    } else if (inputType === 'checkbox') {
                        if (isValueMultiple) {
                            block.find(inputName).map(function () {
                                $(this).prop('checked', value.includes($(this).val())).trigger('change');
                            });
                        } else {
                            block.find(inputName).val(value).trigger('change');
                        }
                    } else if (inputType === 'select') {
                        block.find(inputName).val(value).trigger('change');
                    } else {
                        val = isValueMultiple ? value[0] : value;
                        inp = block.find(inputName);
                        inp.val(val);
                        if ($.isFunction(inp.trigger)) inp.trigger('change');
                    }
                }
            }

            // The block settings should use "o:layout_data" but "layout_data" is allowed for
            // simplicity. They are be mixed.
            if ((blockSettings['o:layout_data'] && Object.values(blockSettings['o:layout_data']).length)
                || (blockSettings['layout_data'] && Object.values(blockSettings['layout_data']).length)
            ) {
                // For layout data, the data are set as data and in hidden inputs.
                block.data('block-layout-data', blockSettings['o:layout_data'] ?? blockSettings['layout_data'] ?? {});
                for (const [key, value] of Object.entries(blockSettings['o:layout_data'] ?? blockSettings['layout_data'] ?? {})) {
                    // All layout data are hidden and there is no multiple.
                    const inputName = `.block-content [name="o:block[${blockIndex}][o:layout_data][${key}]"]`;
                    block.find(inputName).val(value);
                }
            }
        }

        const addBlockGroupPlus = $('<button>', {
            type: 'button',
            id: 'add-block-group-plus',
            value: 'addBlockGroupPlus',
            class: 'add-block-group-plus expand',
            title: Omeka.jsTranslate('Expand to display the groups of blocks'),
            'aria-label': Omeka.jsTranslate('Expand to display the groups of blocks'),
            'data-text-expand': Omeka.jsTranslate('Expand to display the groups of blocks'),
            'data-text-collapse': Omeka.jsTranslate('Collapse the list of groups of blocks'),
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
            // The label should use "o:label" but "label" is allowed for simplicity.
            buttonBlockGroup.text(Omeka.jsTranslate(data['o:label'] ?? data['label'] ?? layout));
            buttonBlockGroup.append($('<span>', {class: 'spinner'}));
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
                alert(Omeka.jsTranslate('Please wait for previous group of blocks to be prepared before adding a new one.'));
                return;
            }
            // Prepare display.
            const buttonBlockGroup = $(this);
            buttonBlockGroup.find('.spinner').addClass('processing fas fa-sync fa-spin');
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
            // The list of block should use "o:block" but "block" is allowed for simplicity.
            const groupedBlocks = blockGroupData['o:block'] ?? blockGroupData['block'] ?? {};

            if (blockLayout === 'blockGroup') {
                // Finalize the block group with settings.
                updateBlockGroup(thisBlock, blockGroupData);
                // Append all grouped blocks.
                // TODO Use an async process (loop for), not a forEach, even if it simpler to reorder blocks.
                Object.values(groupedBlocks).forEach(groupedBlockData => {
                    // The layout should use "o:layout" but "layout" is allowed for simplicity.
                    $(`#new-block button[value="${groupedBlockData['o:layout'] ?? groupedBlockData['layout']}"]`).click();
                    addBlockGroupPlus.data('total-grouped-blocks', addBlockGroupPlus.data('total-grouped-blocks') + 1);
                });
            } else {
                addBlockGroupPlus.data('total-grouped-blocks', addBlockGroupPlus.data('total-grouped-blocks') - 1);
            }

            // Check if all blocks are ready in order to finalize the process.
            if (addBlockGroupPlus.data('total-grouped-blocks') === 0) {
                // Check for issue with the last block group.
                const blockGroup = $('#blocks .block.block-group[data-block-layout="blockGroup"]').last();
                if (blockGroup.length) {
                    // Move grouped blocks to block group in the right order.
                    const blockGroupBlocks = blockGroup.find('.block-group-blocks');
                    for (var groupedBlockData of Object.values(groupedBlocks)) {
                        const groupedBlock = blockGroup.find(`~ .block[data-block-layout="${groupedBlockData['o:layout'] ?? groupedBlockData['layout']}"]`).first();
                        if (groupedBlock.length) {
                            // Finalize the grouped block with settings.
                            updateBlockGroup(groupedBlock, groupedBlockData);
                            // Move the grouped block.
                            $(groupedBlock).detach().appendTo(blockGroupBlocks);
                        }
                    }
                    // Update the block Group with the real number of attached blocks.
                    updateBlockGroup(blockGroup, {
                        'o:data': {'span': blockGroupBlocks.find('.block[data-block-layout]').length},
                    });
                }
                // Finalize the process.
                addBlockGroupPlus
                    .removeData('is-pending')
                    .removeData('total-grouped-blocks');
                const buttonBlockGroup = $(`#block-group-layouts button[value="${blockGroupLayout}"]`);
                buttonBlockGroup.find('.spinner').removeClass('processing fas fa-sync fa-spin');
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
