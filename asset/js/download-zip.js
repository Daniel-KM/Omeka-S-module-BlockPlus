'use strict';

/**
 * Requires common-dialog.js.
 */

/**
 * Download zip link handler with confirmation dialog.
 *
 * This script intercepts clicks on .download-zip-link elements and shows a
 * confirmation dialog with file size information before starting the download.
 */
var DownloadZip = (function() {

    var self = {};

    /**
     * Handle click on download link.
     */
    self.handleClick = function(event) {
        const link = event.target.closest('.download-zip-link');
        if (!link) {
            return;
        }

        // Check if dialog confirmation is required.
        if (!link.dataset.dialogConfirm) {
            return;
        }

        event.preventDefault();

        const message = link.dataset.dialogMessage || '';
        const filename = link.dataset.filename || '';

        CommonDialog.dialogConfirm({
            heading: Omeka.jsTranslate('Confirm download'),
            message: message,
            textOk: Omeka.jsTranslate('Download'),
            textCancel: Omeka.jsTranslate('Cancel'),
        }).then(function(confirmed) {
            if (confirmed) {
                self.startDownload(link.href, filename);
            }
        });
    };

    /**
     * Start the file download.
     *
     * Creates a temporary link to trigger the download, which allows the
     * download to proceed even with the 'download' attribute.
     */
    self.startDownload = function(url, filename) {
        const tempLink = document.createElement('a');
        tempLink.href = url;
        tempLink.download = filename;
        tempLink.style.display = 'none';
        document.body.appendChild(tempLink);
        tempLink.click();
        document.body.removeChild(tempLink);
    };

    /**
     * Initialize event listeners.
     */
    self.init = function() {
        document.addEventListener('click', self.handleClick);
        return self;
    };

    return self;

})();

document.addEventListener('DOMContentLoaded', function() {
    DownloadZip.init();
});
