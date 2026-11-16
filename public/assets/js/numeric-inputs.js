(function () {
    'use strict';
    const selector = 'input[type="number"], input[inputmode="decimal"], input.numeric-input, input.remittance-amount-input';
    function normalize(value) {
        return String(value ?? '')
            .replace(/[۰-۹]/g, d => String(d.charCodeAt(0) - 1776))
            .replace(/[٠-٩]/g, d => String(d.charCodeAt(0) - 1632))
            .replace(/٫/g, '.').replace(/[٬،]/g, ',');
    }
    function format(value, trimFraction = false) {
        let raw = normalize(value).replace(/,/g, '').replace(/[^\d.\-]/g, '');
        const sign = raw.startsWith('-') ? '-' : '';
        raw = raw.replace(/-/g, '');
        const dot = raw.indexOf('.');
        let integer = (dot < 0 ? raw : raw.slice(0, dot)).replace(/^0+(?=\d)/, '');
        let fraction = dot < 0 ? '' : raw.slice(dot + 1).replace(/\./g, '');
        if (trimFraction) fraction = fraction.replace(/0+$/, '');
        const decimal = dot >= 0 && (!trimFraction || fraction) ? '.' + fraction : '';
        if (!integer && decimal) integer = '0';
        return sign + integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + decimal;
    }
    window.convertToEnglishNumbers = normalize;
    window.formatNumericInput = format;
    window.parseFormattedNumber = value => Number.parseFloat(normalize(value).replace(/,/g, ''));
    window.formatRemittanceAmount = format;

    function prepare(input) {
        if (!input.matches(selector)) return;
        if (input.type === 'number') input.type = 'text';
        input.inputMode = 'decimal';
        input.autocomplete = 'off';
        input.spellcheck = false;
        input.value = format(input.value, true);
    }
    function scan(root) {
        if (root.nodeType !== 1 && root !== document) return;
        if (root.matches?.(selector)) prepare(root);
        root.querySelectorAll(selector).forEach(prepare);
    }
    document.addEventListener('input', event => {
        const input = event.target;
        if (!input.matches(selector)) return;
        const prefix = normalize(input.value.slice(0, input.selectionStart)).replace(/,/g, '');
        const wanted = prefix.replace(/[^\d.\-]/g, '').length;
        input.value = format(input.value);
        let position = 0, count = 0;
        while (position < input.value.length && count < wanted) {
            if (input.value[position] !== ',') count++;
            position++;
        }
        input.setSelectionRange(position, position);
    }, true);
    document.addEventListener('focusin', event => {
        if (event.target.matches(selector)) prepare(event.target);
    });
    document.addEventListener('change', event => {
        if (event.target.matches(selector)) event.target.value = format(event.target.value);
    }, true);
    // Format values filled by edit modals and calculation code without changing field names.
    const previous = jQuery.valHooks.text?.set;
    jQuery.valHooks.text = Object.assign({}, jQuery.valHooks.text, {
        set(input, value) {
            if (input.matches(selector)) {
                input.value = format(value, true);
                return value;
            }
            return previous?.(input, value);
        }
    });
    scan(document);
    new MutationObserver(records => records.forEach(record => {
        if (record.type === 'attributes') prepare(record.target);
        else record.addedNodes.forEach(scan);
    })).observe(document.body, {childList: true, subtree: true, attributes: true, attributeFilter: ['value']});
})();
