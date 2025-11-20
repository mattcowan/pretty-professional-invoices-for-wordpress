/**
 * Single Invoice Frontend JavaScript
 * Pretty Professional Invoices
 */

(function() {
    'use strict';

    /**
     * Download invoice as PDF
     * Uses browser's print-to-PDF functionality
     */
    window.downloadPDF = function() {
        window.print();
    };

    /**
     * Auto-print if URL contains ?print=1 parameter
     */
    if (window.location.search.indexOf('print=1') !== -1) {
        // Wait for page to fully load, then print
        if (document.readyState === 'complete') {
            window.print();
        } else {
            window.addEventListener('load', function() {
                window.print();
            });
        }
    }

})();
