// Run with jsdom available on NODE_PATH. Uses the application's bundled jQuery.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const { JSDOM } = require('jsdom');
const dom = new JSDOM('<body><input type="number" name="amount" value="12345.00"><input name="phone" value="001234"><input name="rate" inputmode="decimal"></body>', { runScripts: 'outside-only' });
const { window } = dom;
window.eval(fs.readFileSync('public/js/vendor/jquery.min.js', 'utf8'));
const $ = window.jQuery;
window.eval(fs.readFileSync('public/assets/js/numeric-inputs.js', 'utf8'));
const amount = window.document.querySelector('[name="amount"]');
assert.equal(amount.type, 'text');
assert.equal(amount.value, '12,345');
assert.equal(window.document.querySelector('[name="phone"]').value, '001234');
for (const [raw, formatted] of [['1234.50', '1,234.50'], ['۱٬۲۳۴٫۵', '1,234.5'], ['٩٨٧٦', '9,876'], ['-12345.25', '-12,345.25'], ['12345678901234567890.123456', '12,345,678,901,234,567,890.123456'], ['1000.', '1,000.'], ['letters123', '123']]) {
    assert.equal(window.formatNumericInput(raw), formatted);
}
assert.equal(window.parseFormattedNumber('1,234.50'), 1234.5);
amount.value = '';
for (const char of '123450.50') {
    const position = amount.selectionStart;
    amount.setRangeText(char, position, position, 'end');
    amount.dispatchEvent(new window.Event('input', { bubbles: true }));
}
assert.equal(amount.value, '123,450.50');
assert.equal(amount.selectionStart, 10);
assert.equal(amount.name, 'amount');
$(amount).val('12345.0000');
assert.equal(amount.value, '12,345');
$(amount).val('12345.123456');
assert.equal(amount.value, '12,345.123456');
$('input[name="rate"]').val('1234.5');
assert.equal(window.parseFormattedNumber($('input[name="rate"]').val()) * 2, 2469);
// Run the actual expense calculation functions against formatted input values.
window.$ = $;
const expenseSource = fs.readFileSync('resources/views/admin/expenses/index.blade.php', 'utf8');
const start = expenseSource.indexOf('function calculateAmountFromPayment()');
const end = expenseSource.indexOf('// Main behavior', start);
window.document.body.insertAdjacentHTML('beforeend', '<input id="payment_amount" inputmode="decimal"><input id="amount" inputmode="decimal"><input id="rate" inputmode="decimal"><select id="operation_type"><option value="multiply">multiply</option><option value="divide">divide</option></select>');
window.eval('let isUpdating = false; ' + expenseSource.slice(start, end));
$('#payment_amount').val('1,000'); $('#rate').val('2,000');
window.calculateAmountFromPayment();
assert.equal($('#amount').val(), '2,000,000');
$('#amount').val('3,000,000'); window.calculatePaymentFromAmount();
assert.equal($('#payment_amount').val(), '1,500');
$('#operation_type').val('divide'); $('#rate').val('2'); $('#payment_amount').val('1,000.50');
window.calculateAmountFromPayment();
assert.equal($('#amount').val(), '500.25');
const dynamic = window.document.createElement('input');
dynamic.setAttribute('inputmode', 'decimal');
dynamic.name = 'entries[1][amount]';
dynamic.value = '12345.000';
window.document.body.append(dynamic);
setTimeout(() => {
    assert.equal(dynamic.value, '12,345');
    assert.equal(dynamic.name, 'entries[1][amount]');
    console.log('Numeric input DOM checks passed');
    window.close();
}, 0);
