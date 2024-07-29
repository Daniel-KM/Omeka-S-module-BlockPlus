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
         * The config of blocks should use `o:label`, `o:caption`, `o:block`,
         * `o:layout`, `o:data` and `o:layout_data`, but `label`, `caption`,
         * `block`, `layout`, `data`, `layout_data` are allowed for simplicity.
         */

        /**
         * Set data and layout data to a block.
         */
        function updateBlock(block, blockSettings) {
            if (!block || !block.length || !blockSettings || !Object.keys(blockSettings).length) {
                return;
            }

            const blockLayout = block.data('block-layout');
            const blockInputLayout = block.find(`.block-content input[type="hidden"][value="${blockLayout}"]`).first();
            if (!blockInputLayout || !blockInputLayout.attr('name') || !blockInputLayout.attr('name').length) {
                return;
            }

            // The block index is stored as data-block-index too, but extract it to avoid issue.
            // The block name should be "o:block[XXX][o:layout]".
            const blockIndex = blockInputLayout.attr('name').substring(blockInputLayout.attr('name').indexOf('[') + 1, blockInputLayout.attr('name').indexOf(']'))
            if (!blockIndex.length) {
                return;
            }

            // The block settings should use "o:data" but "data" is allowed for
            // simplicity. They are be mixed.
            const blockSettingsData = blockSettings['o:data'] ?? blockSettings['data'] ?? {};
            for (const [key, value] of Object.entries(blockSettingsData)) {
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
                            input.val(value).trigger('change');
                        }
                    } else if (inputType === 'select') {
                        input.val(value).trigger('change');
                    } else {
                        val = isValueMultiple ? value[0] : value;
                        input.val(val);
                        if ($.isFunction(input.trigger)) input.trigger('change');
                    }
                }
            }

            // The block settings should use "o:layout_data" but "layout_data" is allowed for
            // simplicity. They are be mixed.
            const blockSettingsLayoutData = blockSettings['o:layout_data'] ?? blockSettings['layout_data'] ?? null;
            if (blockSettingsLayoutData) {
                // For layout data, the data are set as data and in hidden inputs.
                var blockLayoutData = block.data('block-layout-data');
                for (const [key, value] of Object.entries(blockSettingsLayoutData)) {
                    blockLayoutData[key] = value;
                    // All layout data are hidden and there is no multiple.
                    const inputName = `.block-content [name="o:block[${blockIndex}][o:layout_data][${key}]"]`;
                    block.find(inputName).val(value);
                }
                const inputName = `.block-content [name="o:block[${blockIndex}][o:layout_data]"]`;
                block.find(inputName).val(JSON.stringify(blockLayoutData));
                block.data('block-layout-data', blockLayoutData);
            }
        }

        const addBlockGroupPlus = $('<button>', {
            type: 'button',
            id: 'add-block-group-plus',
            value: 'addBlockGroupPlus',
            class: 'add-block-group-plus expand',
            title: Omeka.jsTranslate('Expand to display the list of groups of blocks'),
            'aria-label': Omeka.jsTranslate('Expand to display the list of groups of blocks'),
            'data-text-expand': Omeka.jsTranslate('Expand to display the list of groups of blocks'),
            'data-text-collapse': Omeka.jsTranslate('Collapse the list of groups of blocks'),
        }).append($('<span>', { class: 'add-block-plus fas fa-plus' }));

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
            // The same for "o:caption" / "caption".
            buttonBlockGroup.text(Omeka.jsTranslate(data['o:label'] ?? data['label'] ?? layout));
            if (data['o:caption'] ?? data['caption'] ?? null) {
                buttonBlockGroup
                    .attr('title', Omeka.jsTranslate(data['o:caption'] ?? data['caption']))
                    .attr('aria-label', Omeka.jsTranslate(data['o:caption'] ?? data['caption']));
            }
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
            const blockGroupLayout = buttonBlockGroup.val();

            // The list of block should use "o:block" but "block" is allowed for simplicity.
            const blockGroupData = blockGroups[blockGroupLayout];
            const groupedBlocks = blockGroupData['o:block'] ?? blockGroupData['block'] ?? {};
            if (!Object.values(groupedBlocks).length) {
                alert(Omeka.jsTranslate('This group does not contain any block.'));
                return;
            }

            buttonBlockGroup.find('.spinner').addClass('processing fas fa-sync fa-spin');
            addBlockGroupPlus
                .data('is-pending', blockGroupLayout)
                // Don't count nested blocks here, since new blocks are appended at the root.
                // The block index may be used too.
                .data('count-existing-div-blocks', $('#blocks > .block.value[data-block-layout]').length)
                .data('total-blocks', 0);

            // Append all blocks via the event "o:block-added".
            // TODO Use an async process (loop for), not a forEach, even if it simpler to reorder blocks.
            Object.values(groupedBlocks).forEach(groupedBlockData => {
                // The layout should use "o:layout" but "layout" is allowed for simplicity.
                const blockLayout = groupedBlockData['o:layout'] ?? groupedBlockData['layout'] ?? null;
                if (blockLayout) {
                    $(`#new-block button[value="${blockLayout}"]`).click();
                    addBlockGroupPlus.data('total-blocks', addBlockGroupPlus.data('total-blocks') + 1);
                }
            });
        });

        $('#blocks').on('o:block-added', '.block', function () {
            const blockGroupLayout = addBlockGroupPlus.data('is-pending');
            if (!blockGroupLayout || addBlockGroupPlus.data('total-blocks') === undefined) {
                return;
            }

            // TODO Check if the block is in the current list of blocks to avoid issue when the user click another button, or block them all.

            // Check if all blocks are ready before finalizing the process.
            addBlockGroupPlus.data('total-blocks', addBlockGroupPlus.data('total-blocks') - 1);
            if (addBlockGroupPlus.data('total-blocks') > 0) {
                return;
            }

            // Get the last existing block.
            const lastExistingBlockIndex = addBlockGroupPlus.data('count-existing-div-blocks');
            var lastExistingBlock = lastExistingBlockIndex ? $(`#blocks > .block.value[data-block-layout]:nth-child(${lastExistingBlockIndex})`).first() : null;
            if (lastExistingBlock && !lastExistingBlock.length) {
                lastExistingBlock = null;
            }

            // Reorder appended blocks, manage nested blocks in block Group and set settings of each blocks.
            const blockGroupData = blockGroups[blockGroupLayout];
            const groupedBlocks = blockGroupData['o:block'] ?? blockGroupData['block'] ?? {};
            var blockGroupBlocks;
            var previousBlockGroup = null;
            var previousBlockGroupSpan = 0;
            var previousBlockGroupSpanPredefined = 0;

            for (var blockSettings of Object.values(groupedBlocks)) {
                const blockLayout = blockSettings['o:layout'] ?? blockSettings['layout'] ?? null;
                if (blockLayout) {
                    const groupedBlock = lastExistingBlock
                        ? lastExistingBlock.find(`~ .block.value[data-block-layout="${blockLayout}"]`).first()
                        : $(`#blocks > .block.value[data-block-layout="${blockLayout}"]`).first();
                    if (groupedBlock.length) {
                        // Manage block Group separately to avoid issue with bad
                        // or missing span in the config.
                        if (blockLayout === 'blockGroup') {
                            // Finalize previous block group if any.
                            if (previousBlockGroup) {
                                // Update the block Group with the real number
                                // of attached blocks, whatever the config was.
                                updateBlock(previousBlockGroup, {
                                    'o:data': { 'span': previousBlockGroup.find('.block-group-blocks > .block.value[data-block-layout]').length },
                                });
                            }
                            let blockSettingsData = blockSettings['o:data'] ?? blockSettings['data'] ?? {};
                            previousBlockGroup = groupedBlock;
                            previousBlockGroupSpan = 0;
                            previousBlockGroupSpanPredefined = blockSettingsData.span ?? 0;
                        }
                        // Move to right place: it is simpler to move it to the end.
                        $(groupedBlock).detach().appendTo($('#blocks'));
                        // Fill the configured settings.
                        updateBlock(groupedBlock, blockSettings);
                        // Move the block if it is nested in a block Group.
                        if (previousBlockGroup && blockLayout !== 'blockGroup') {
                            blockGroupBlocks = previousBlockGroup.find('.block-group-blocks').first();
                            $(groupedBlock).detach().appendTo(blockGroupBlocks);
                            // Update the block Group with the real number of attached blocks.
                            updateBlock(previousBlockGroup, {
                                'o:data': { 'span': blockGroupBlocks.find('> .block.value[data-block-layout]').length },
                            });
                            // Update the number of nested blocks and check last one.
                            previousBlockGroupSpan = blockGroupBlocks.find('> .block.value[data-block-layout]').length;
                            if (previousBlockGroupSpanPredefined && previousBlockGroupSpan >= previousBlockGroupSpanPredefined) {
                                previousBlockGroup = null;
                                previousBlockGroupSpan = 0;
                                previousBlockGroupSpanPredefined = 0;
                            }
                        }
                    }
                }
            }

            // Avoid issue with a bad count of nested block for block group.
            if (previousBlockGroup) {
                updateBlock(previousBlockGroup, {
                    'o:data': { 'span': previousBlockGroup.find('.block-group-blocks > .block.value[data-block-layout]').length },
                });
            }

            // Finalize the process.
            addBlockGroupPlus
                .removeData('is-pending')
                .removeData('count-existing-div-blocks')
                .removeData('total-blocks');
            const buttonBlockGroup = $(`#block-group-layouts button[value="${blockGroupLayout}"]`);
            buttonBlockGroup.find('.spinner').removeClass('processing fas fa-sync fa-spin');
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
