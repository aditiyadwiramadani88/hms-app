/**
 * RupiahFormatter - Global Rupiah dot thousand separator formatter & parser
 * Handles real-time typing masking, initial display formatting, and unmasking on submit.
 */
(function() {
    'use strict';

    /**
     * Format a numeric string or number into Indonesian Rupiah format with dots (e.g. 20000 -> 20.000)
     */
    function formatRupiah(val) {
        if (val === null || val === undefined || val === '') return '';
        const clean = val.toString().replace(/[^0-9]/g, '');
        if (!clean) return '';
        return clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    /**
     * Parse a Rupiah formatted string into a plain integer (e.g. "20.000" -> 20000)
     */
    function parseRupiah(val) {
        if (val === null || val === undefined || val === '') return 0;
        const clean = val.toString().replace(/[^0-9]/g, '');
        return clean ? parseInt(clean, 10) : 0;
    }

    // Expose helpers globally
    window.formatRupiah = formatRupiah;
    window.parseRupiah = parseRupiah;

    /**
     * Handle input event for rupiah inputs while preserving cursor position accurately.
     */
    function handleRupiahInput(input) {
        const cursor = input.selectionStart || 0;
        const original = input.value;

        // Count numeric digits before cursor
        const digitsBeforeCursor = original.slice(0, cursor).replace(/[^0-9]/g, '').length;

        // Extract clean numbers
        const clean = original.replace(/[^0-9]/g, '');
        if (!clean) {
            input.value = '';
            return;
        }

        const formatted = clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        input.value = formatted;

        // Re-position cursor based on digit count
        let newCursor = 0;
        let countedDigits = 0;
        for (let i = 0; i < formatted.length; i++) {
            if (countedDigits === digitsBeforeCursor) {
                newCursor = i;
                break;
            }
            if (formatted[i] >= '0' && formatted[i] <= '9') {
                countedDigits++;
            }
            newCursor = i + 1;
        }
        input.setSelectionRange(newCursor, newCursor);
    }

    /**
     * Format all rupiah inputs in a given root container
     */
    function formatAllRupiahInputs(root) {
        const container = root || document;
        if (!container || !container.querySelectorAll) return;
        const inputs = container.querySelectorAll('.rupiah-input, [data-type="currency"]');
        inputs.forEach(input => {
            if (input.value) {
                input.value = formatRupiah(input.value);
            }
        });
    }

    window.initRupiahInputs = formatAllRupiahInputs;

    // Listen on input events across the document via event delegation
    document.addEventListener('input', function(e) {
        const target = e.target;
        if (target && target.matches && target.matches('.rupiah-input, [data-type="currency"]')) {
            handleRupiahInput(target);
        }
    });

    // Auto format on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            formatAllRupiahInputs(document);
        });
    } else {
        formatAllRupiahInputs(document);
    }

    // Auto format whenever a Bootstrap modal is shown
    document.addEventListener('shown.bs.modal', function(e) {
        formatAllRupiahInputs(e.target);
    });

    // Auto unmask on standard (non-AJAX) form submission
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form && !form.matches('[data-ajax="true"]')) {
            form.querySelectorAll('.rupiah-input, [data-type="currency"]').forEach(input => {
                input.value = input.value.replace(/[^0-9]/g, '');
            });
        }
    }, true);

})();
