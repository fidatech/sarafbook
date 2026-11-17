@extends('layouts.admin.base')
@section('title', __('Balance report'))
@section('modern', '1')

{{--
    The balance report in the fida-sarafi style: everything the Sarafi holds, turned into one
    currency with the rates of the day. The rates are suggested from the exchange rates and can be
    changed; the report, its print and the day closing all use the rates on the screen. The
    numbers come from ReportsController (balanceRates, balanceData, balancePrint, closeDay).
--}}
@php
    $canClose = auth()->user()->can('update settings') || auth()->user()->hasRole('admin');
@endphp

@section('content')
<x-page-header :title="__('Balance report')" icon="scale-balanced" :subtitle="__('Everything the Sarafi holds, in one currency.')">
    <button type="button" class="btn btn-outline-soft" id="blPrint" disabled><x-icon name="print" class="me-1" /> {{ __('Print') }}</button>
    @if ($canClose)<button type="button" class="btn btn-primary" id="blCloseBtn" disabled><x-icon name="lock" class="me-1" /> {{ __('Close the day') }}</button>@endif
</x-page-header>

<form class="card mb-3" id="blForm" novalidate data-rates="{{ route('admin.reports.balance.rates') }}" data-data="{{ route('admin.reports.balance.data') }}">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label" for="blTarget">{{ __('Show in') }}</label>
                <select class="form-select" id="blTarget" name="target_currency_id" data-plain>
                    @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected($c->id === $defaultCurrency?->id)>{{ $c->code }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="blDate">{{ __('ui.date') }}</label>
                <input type="date" class="form-control" id="blDate" name="date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="blType">{{ __('Rates of') }}</label>
                <select class="form-select" id="blType" name="type" data-plain>
                    <option value="">{{ __('Any rate') }}</option>
                    <option value="purchase">{{ __('Buying') }}</option>
                    <option value="sell">{{ __('Selling') }}</option>
                </select>
            </div>
            <div class="col-6 col-md-3 d-grid">
                <button type="submit" class="btn btn-primary"><x-icon name="calculator" class="me-1" /> {{ __('Show the report') }}</button>
            </div>
        </div>
        <details class="mt-3" id="blRatesBox" open>
            <summary class="fw-semibold text-primary">{{ __('Rates used') }}</summary>
            <div class="bl-rates mt-2" id="blRates" aria-live="polite"><span class="text-muted small">{{ __('Loading…') }}</span></div>
        </details>
    </div>
</form>

<div id="blOut" aria-live="polite">
    <div class="card"><div class="card-body"><div class="empty-state"><div class="es-icon"><x-icon name="scale-balanced" /></div><h2 class="h3">{{ __('Choose the currency and press "Show the report".') }}</h2></div></div></div>
</div>

@if ($canClose)
<div class="modal fade" id="blCloseModal" tabindex="-1" aria-labelledby="blCloseTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="blCloseForm" novalidate>
            <div class="modal-header"><h2 class="modal-title h5" id="blCloseTitle">{{ __('Close the day') }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button></div>
            <div class="modal-body">
                <p class="text-muted small">{{ __('The balances of the day are saved with the rates on the screen. A day is closed only once.') }}</p>
                <div class="mb-3">
                    <label class="form-label" for="blCarry">{{ __('Carry the balance to') }} <span class="text-danger">*</span></label>
                    <select class="form-select" id="blCarry" name="carry_forward_account_id" required data-search>
                        <option value="">{{ __('Type a name or code…') }}</option>
                        @foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} · {{ $a->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="blCarryPl">{{ __('Carry the profit and loss to') }} <span class="text-danger">*</span></label>
                    <select class="form-select" id="blCarryPl" name="profit_loss_carry_account_id" required data-search>
                        <option value="">{{ __('Type a name or code…') }}</option>
                        @foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} · {{ $a->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="blRemarks">{{ __('Remarks') }}</label>
                    <textarea class="form-control" id="blRemarks" name="remarks" rows="2" maxlength="1000"></textarea>
                </div>
                <div class="alert alert-danger py-2 mt-3 mb-0" id="blCloseError" hidden role="alert"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('Close the day') }}</button></div>
        </form>
    </div>
</div>
@endif

<form method="POST" action="{{ route('admin.reports.balance.print') }}" target="_blank" id="blPrintForm" hidden>@csrf</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('blForm'), out = document.getElementById('blOut'), ratesBox = document.getElementById('blRates');
    var token = (document.querySelector('meta[name=csrf-token]') || {}).content;
    var codes = {!! json_encode($currencies->pluck('code', 'id'), JSON_HEX_TAG) !!};
    var T = {!! json_encode([
        'rate' => __('ui.rate'), 'same' => __('Same currency'), 'missing' => __('No rate — not counted'), 'mul' => __('Multiply'), 'div' => __('Divide'),
        'cash' => __('Balances'), 'pl' => __('Profit and loss'), 'unpaid' => __('Unpaid remittances'), 'final' => __('Final total'),
        'currency' => __('ui.currency'), 'credit' => __('ui.credit'), 'debit' => __('ui.debit'), 'balance' => __('Balance'), 'converted' => __('In :code'),
        'income' => __('Income'), 'expense' => __('Expense'), 'previous' => __('Last closing'), 'closed' => __('This day is closed'), 'by' => __('By'),
        'failed' => __('It could not be loaded. Try again.'), 'none' => __('Nothing here'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var last = null;
    var nice = function (n, d) { n = Number(n) || 0; return (n < 0 ? '−' : '') + Math.abs(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: d == null ? 2 : d }); };
    var esc = ERP.escape;
    var target = function () { return form.elements.target_currency_id.value; };

    // the rate of every currency to the chosen one, changeable
    var loadRates = function () {
        ratesBox.innerHTML = '<span class="text-muted small">…</span>';
        var q = new URLSearchParams({ target_currency_id: target(), date: form.elements.date.value, type: form.elements.type.value });
        return fetch(form.dataset.rates + '?' + q, { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
            var html = '';
            Object.keys(d.rates || {}).forEach(function (cid) {
                var r = d.rates[cid], same = String(cid) === String(target());
                html += '<div class="bl-rate"><span class="chip-code">' + esc(codes[cid] || cid) + '</span>';
                if (same) { html += '<span class="text-muted small">' + esc(T.same) + '</span><input type="hidden" data-cid="' + cid + '" data-op value="same"><input type="hidden" data-cid="' + cid + '" data-rate value="1">'; }
                else {
                    html += '<select class="form-select form-select-sm" data-cid="' + cid + '" data-op data-plain aria-label="' + esc(codes[cid] || '') + '">'
                        + ['multiply', 'divide', 'missing'].map(function (o) { return '<option value="' + o + '"' + (r.operator === o ? ' selected' : '') + '>' + esc(o === 'multiply' ? '× ' + T.mul : o === 'divide' ? '÷ ' + T.div : T.missing) + '</option>'; }).join('')
                        + '</select><input type="text" class="form-control form-control-sm" data-cid="' + cid + '" data-rate inputmode="decimal" dir="ltr" value="' + (r.rate || '') + '" aria-label="' + esc(T.rate) + ' ' + esc(codes[cid] || '') + '">';
                }
                html += '</div>';
            });
            ratesBox.innerHTML = html || '<span class="text-muted small">' + esc(T.none) + '</span>';
        }).catch(function () { ratesBox.innerHTML = '<span class="text-danger small">' + esc(T.failed) + '</span>'; });
    };
    var rates = function () {
        var o = {};
        ratesBox.querySelectorAll('[data-op]').forEach(function (el) { var cid = el.dataset.cid; o[cid] = o[cid] || {}; o[cid].operator = el.value; });
        ratesBox.querySelectorAll('[data-rate]').forEach(function (el) { var cid = el.dataset.cid; o[cid] = o[cid] || {}; var v = ERP.latinDigits ? ERP.latinDigits(el.value) : el.value; o[cid].rate = v === '' ? null : parseFloat(String(v).replace(/,/g, '')); if (o[cid].operator === 'missing') o[cid].rate = null; });
        return o;
    };
    var params = function () {
        var q = new URLSearchParams({ target_currency_id: target(), date: form.elements.date.value });
        if (form.elements.type.value) q.set('type', form.elements.type.value);
        var r = rates();
        Object.keys(r).forEach(function (cid) { q.set('rates[' + cid + '][operator]', r[cid].operator); if (r[cid].rate != null && !isNaN(r[cid].rate)) q.set('rates[' + cid + '][rate]', r[cid].rate); });
        return q;
    };

    var table = function (title, rows, cols, total, code) {
        if (!rows || !rows.length) return '';
        var h = '<section class="card mb-3"><div class="card-header d-flex align-items-center"><h2 class="h5 card-title mb-0 me-auto">' + esc(title) + '</h2><strong dir="ltr" class="' + (total < 0 ? 'is-owed' : 'is-owing') + '">' + nice(total) + ' ' + esc(code) + '</strong></div><div class="card-body"><div class="table-responsive data-wrap"><table class="table w-100 mb-0 data-table keep-table"><thead><tr>';
        cols.forEach(function (c) { h += '<th scope="col"' + (c[2] ? ' class="is-num"' : '') + '>' + esc(c[0]) + '</th>'; });
        h += '</tr></thead><tbody>';
        rows.forEach(function (r) {
            h += '<tr>';
            cols.forEach(function (c) {
                var v = r[c[1]];
                if (c[1] === 'currency_code') h += '<td><span class="chip-code">' + esc(v) + '</span></td>';
                else if (c[1] === 'rate') h += '<td class="is-num text-muted" dir="ltr">' + (r.operator === 'same' ? '—' : r.operator === 'missing' ? esc(T.missing) : (r.operator === 'divide' ? '÷ ' : '× ') + esc(String(v))) + '</td>';
                else h += '<td class="is-num' + (c[3] ? ' fw-semibold' : '') + '" dir="ltr">' + nice(v) + '</td>';
            });
            h += '</tr>';
        });
        return h + '</tbody></table></div></div></section>';
    };
    var show = function (e) {
        if (e) e.preventDefault();
        out.querySelectorAll('.card').forEach(function (c) { c.classList.add('is-loading'); });
        fetch(form.dataset.data + '?' + params(), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json().then(function (d) { if (!r.ok) throw new Error(d.message || T.failed); return d; }); })
            .then(function (d) {
                last = d;
                var code = d.target_currency.code, s = d.summary, conv = T.converted.replace(':code', code);
                var html = '<div class="row g-3 mb-3">';
                [[T.cash, s.main_balance_total], [T.pl, s.profit_loss_total], [T.unpaid, s.unpaid_total], [T.final, s.final_total]].forEach(function (c, i) {
                    html += '<div class="col-6 col-xl-3"><div class="card h-100' + (i === 3 ? ' bl-final' : '') + '"><div class="card-body"><div class="text-muted small">' + esc(c[0]) + '</div><div class="safe-now ' + (c[1] < 0 ? 'is-owed' : '') + '" dir="ltr">' + nice(c[1]) + '</div><div class="text-muted small">' + esc(code) + '</div></div></div></div>';
                });
                html += '</div>';
                if (d.closing && d.closing.is_closed) html += '<div class="alert alert-success">' + esc(T.closed) + ' · ' + esc(T.by) + ' ' + esc(d.closing.closed_by || '—') + ' · ' + esc(d.closing.closed_at || '') + '</div>';
                if (d.previous_closing) html += '<p class="text-muted small">' + esc(T.previous) + ': ' + esc(d.previous_closing.closing_date) + ' · <span dir="ltr">' + nice(d.previous_closing.final_total) + ' ' + esc(code) + '</span></p>';
                html += table(T.cash, d.main_rows, [[T.currency, 'currency_code'], [T.credit, 'total_credit', 1], [T.debit, 'total_debit', 1], [T.balance, 'balance', 1, 1], [T.rate, 'rate', 1], [conv, 'converted_amount', 1, 1]], s.main_balance_total, code);
                html += table(T.pl, d.profit_loss_rows, [[T.currency, 'currency_code'], [T.income, 'total_income', 1], [T.expense, 'total_expense', 1], [T.balance, 'profit_loss', 1, 1], [T.rate, 'rate', 1], [conv, 'converted_amount', 1, 1]], s.profit_loss_total, code);
                html += table(T.unpaid, d.unpaid_rows, [[T.currency, 'currency_code'], [T.credit, 'total_credit', 1], [T.debit, 'total_debit', 1], [T.balance, 'balance', 1, 1], [T.rate, 'rate', 1], [conv, 'converted_amount', 1, 1]], s.unpaid_total, code);
                out.innerHTML = html;
                document.getElementById('blPrint').disabled = false;
                var cb = document.getElementById('blCloseBtn'); if (cb) cb.disabled = !!(d.closing && d.closing.is_closed);
            }).catch(function (err) {
                out.innerHTML = '<div class="alert alert-danger">' + esc(err.message || T.failed) + '</div>';
            });
    };
    form.addEventListener('submit', show);
    form.addEventListener('change', function (e) {
        if (['target_currency_id', 'date', 'type'].indexOf(e.target.name) !== -1) loadRates().then(show);
    });

    // print: the same numbers on paper
    document.getElementById('blPrint').addEventListener('click', function () {
        var pf = document.getElementById('blPrintForm');
        pf.querySelectorAll('input:not([name=_token])').forEach(function (i) { i.remove(); });
        params().forEach(function (v, k) { var i = document.createElement('input'); i.type = 'hidden'; i.name = k; i.value = v; pf.appendChild(i); });
        pf.submit();
    });

    // closing the day
    var closeBtn = document.getElementById('blCloseBtn');
    if (closeBtn) {
        var modal = document.getElementById('blCloseModal'), cf = document.getElementById('blCloseForm'), err = document.getElementById('blCloseError');
        closeBtn.addEventListener('click', function () { err.hidden = true; ERP.showModal(modal); });
        cf.addEventListener('submit', function (e) {
            e.preventDefault();
            var body = params();
            body.set('closing_date', form.elements.date.value);
            ['carry_forward_account_id', 'profit_loss_carry_account_id', 'remarks'].forEach(function (k) { body.set(k, cf.elements[k].value); });
            fetch(@json(route('admin.reports.balance.close')), { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: body })
                .then(function (r) { return r.json().then(function (d) { if (!r.ok) throw new Error(d.message || Object.values(d.errors || {}).flat().join(' ') || T.failed); return d; }); })
                .then(function (d) { ERP.hideModal(modal); if (ERP.toast) ERP.toast(d.message || '', 'success'); show(); })
                .catch(function (x) { err.textContent = x.message; err.hidden = false; });
        });
    }
    loadRates().then(show);
});
</script>
@endpush
