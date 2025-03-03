'use strict';

(function() {
    document.addEventListener('DOMContentLoaded', function() {

        Grid.init({
            showVisitButton: false,
        });

        // TODO Fill items from Omeka api.
        const addItemsEl = document.getElementById('og-additems');
        if (addItemsEl) {
            addItemsEl.addEventListener('click', function() {
                // Add items html here.
                var itemsHtml = '';
                var tempDiv = document.createElement('div');
                tempDiv.innerHTML = itemsHtml;
                var items = tempDiv.children;
                var grid = document.getElementById('og-grid');
                while (items.length > 0) {
                    grid.appendChild(items[0]);
                }
                Grid.addItems(grid.children);
                return false;
            });
        }

    });
})();
