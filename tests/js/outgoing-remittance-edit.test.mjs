import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/views/admin/accounts/show.blade.php', import.meta.url), 'utf8');
const start = source.indexOf('        function fillOutgoingRemittanceModal(');
const code = source.slice(start, source.indexOf('        function fillIncomingRemittanceModal(', start));

for (const hiddenFields of [false, true]) {
for (const mode of ['track', 'no_track', 'treasure']) {
    test(`outgoing ${mode} restores all saved fields (hidden fields: ${hiddenFields})`, () => {
        const values = new Map();
        const callbacks = [];
        const field = selector => ({
            val(value) { if (!arguments.length) return values.get(selector); values.set(selector, value); return this; },
            html() { return this; }, trigger() { return this; },
            toggle() { return this; }, show() { return this; },
        });
        const modal = { find: field, data() {}, modal() {} };
        const form = { length: 1, 0: { reset() {
            values.clear();
            values.set('input[name="from_account_id"]', 99);
        } } };
        const context = vm.createContext({
            HIDE_REMITTANCE_FIELDS: hiddenFields, incomingEditMode: false, outgoingEditMode: false, outgoingManual: false, outgoingTrackManual: false,
            $: selector => selector === '#outgoingRemittanceForm' ? form : selector === '#outgoingRemittanceModal' ? modal : field(selector),
            resetModalEditState() {}, initSelect2Inside() {}, setRadioValueProper() {},
            getDateOnly: value => value,
            setSelectValueProper: (_, selector, value) => {
                values.set(selector, value);
                context.syncModalCurrencies('outgoing');
            },
            setInputValueDirect: (_, selector, value) => {
                values.set(selector, value);
                context.syncModalAmounts('outgoing');
            },
            setTimeout: callback => callbacks.push(callback),
        });
        context.window = context;
        const syncStart = source.indexOf('        window.syncModalCurrencies =');
        vm.runInContext(source.slice(syncStart, source.indexOf('        window.toggleCommissionFields =', syncStart)), context);
        context.handleOutgoingModal = () => {
            context.syncModalCurrencies('outgoing');
            context.syncModalAmounts('outgoing');
        };
        vm.runInContext(code, context);
        context.fillOutgoingRemittanceModal({
            id: 5, type: 'outgoing', remittance_mode: mode,
            from_account_id: 11, to_account_id: 42, track_account_id: 7,
            currency_id: 2, partner_currency_id: 3, actual_currency_id: 4,
            amount: '1200.25', partner_amount: '17.50', actual_amount: '18.75',
            commission_mode: 'split', commission_currency_id: 5,
            commission_kabul_amount: '0.50', commission_partner_amount: '0.25',
            sender: 'Sender', receiver: 'Receiver', description: 'Saved note',
            date: '2026-09-22', hawala_number: '0012', track_hawala_number: '0034',
        });
        while (callbacks.length) callbacks.shift()();
        // Mode/account changes must not overwrite the independently saved pairs.
        context.handleOutgoingModal();
        for (const [selector, expected] of Object.entries({
            '#outgoing_currency_id': 3, '#outgoing_partner_currency_id': 2,
            '#outgoing_actual_currency_id': 4, '#outgoing_amount': '1200.25',
            '#outgoing_partner_amount': '17.50', '#outgoing_actual_amount': '18.75',
            '#outgoing_commission_currency_id': 5,
            '#outgoing_commission_kabul_amount': '0.50', '#outgoing_commission_partner_amount': '0.25',
            'input[name="sender"]': 'Sender', 'input[name="receiver"]': 'Receiver',
            'input[name="description"]': 'Saved note', 'input[name="date"]': '2026-09-22',
            'input[name="hawala_number"]': '0012', 'input[name="track_hawala_number"]': '0034',
            'input[name="to_account_id"]': 42,
            'input[type="hidden"][name="remittance_mode"]': mode,
        })) assert.equal(values.get(selector), expected, selector);
        assert.equal(values.get('input[name="from_account_id"]'), 11);
        if (mode === 'track') {
            assert.equal(values.get('#outgoing_track_account_id'), 7);
            assert.equal(values.get('input[name="to_account_id"]'), 42);
        }
    });
}
}
