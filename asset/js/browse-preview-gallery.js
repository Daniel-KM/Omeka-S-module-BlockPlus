'use strict';

document.addEventListener('DOMContentLoaded', function() {

    Grid.init({
        showVisitButton: false,
    });

    // TODO Fill items from Omeka api.
    const addItemsEl = document.getElementById('og-additems');
    if (addItemsEl) {
        addItemsEl.addEventListener('click', function(e) {
            e.preventDefault();
            // Add items html here (to be implemented).
            const itemsHtml = '';
            if (!itemsHtml) {
                return;
            }
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = itemsHtml;
            const items = tempDiv.children;
            const grid = document.getElementById('og-grid');
            while (items.length > 0) {
                grid.appendChild(items[0]);
            }
            Grid.addItems(grid.children);
        });
    }

});
