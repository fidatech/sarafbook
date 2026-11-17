@extends('layouts.admin.base')
@section('title', $exchange ? __('Change exchange') : __('New exchange'))
@section('modern', '1')

{{--
    The exchange form in the fida-sarafi style. With whom (walk-in cash, a customer or a Saraf),
    what they give and what they get, the rate and how it works (× or ÷). The rate of the day is
    filled in, and what they get and the profit are shown while typing; the server works both out
    again when saving. For an account the money can stay inside the account, go through the safe,
    or go to a second account.
--}}
@php
    $x = $exchange;
    $again = old('_form') === 'ex';
    $presetAccount = $x?->account_id ?? (request()->integer('account_id') ?: null);
    $acct = $presetAccount ? $accounts->firstWhere('id', $presetAccount) : null;
    $savedWho = $x && $x->mode === 'cash' ? 'cash' : ($acct ? ((int) $acct->account_sub_category_id === 2 ? 'saraf' : 'customer') : 'customer');
    $savedHow = $x && in_array($x->mode, ['no_track', 'treasure', 'track'], true) ? $x->mode : 'no_track';
    $val = fn ($key, $default = null) => $again ? old($key, $default) : $default;
    $who = $val('mode', $savedWho);
    $how = $val('exchange_mode', $savedHow);
    $from = $val('from_currency_id', $x?->from_currency_id ?? $usdId);
    $to = $val('to_currency_id', $x?->to_currency_id ?? $currencies->firstWhere('id', '!=', $usdId)?->id);
    $num = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 6, '.', ''), '0'), '.');
@endphp

@section('content')
<x-page-header :title="$x ? __('Change exchange #:id', ['id' => $x->id]) : __('New exchange')" icon="coins" :crumbs="[[__('ui.exchanges'), route('admin.exchanges.index')], [$x ? '#'.$x->id : __('New')]]" />

<form method="POST" action="{{ $x ? route('admin.exchanges.update', $x->id) : route('admin.exchanges.store') }}" class="card ex-form" id="exForm" novalidate
      data-rate-url="{{ route('admin.exchanges.smart-rate') }}" data-balances="{{ route('admin.transactions.account-balances') }}" data-usd="{{ $usdId }}">
    @csrf
    @if ($x) @method('PUT') @endif
    <input type="hidden" name="_form" value="ex">
    @if (request('back'))<input type="hidden" name="back" value="{{ request('back') }}">@endif
    <div class="card-body">
        @if ($again && $errors->any())
            <div class="alert alert-danger py-2" role="alert"><ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="row g-3">
            <div class="col-12 col-md-3">
                <label class="form-label" for="exDate">{{ __('ui.date') }} <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="exDate" name="date" value="{{ $val('date', $x ? \Illuminate\Support\Carbon::parse($x->date)->toDateString() : now()->toDateString()) }}" required>
            </div>
            <div class="col-12 col-md-9">
                <fieldset>
                    <legend class="form-label">{{ __('With whom') }}</legend>
                    <div class="tx-modes">
                        @foreach (['cash' => ['user', __('Walk-in (cash)'), __('Paid and taken in cash at the counter.')], 'customer' => ['user-tie', __('A customer'), __('On the account of a customer.')], 'saraf' => ['building-columns', __('A Saraf'), __('On the account of another Saraf.')]] as $value => [$icon, $label, $hint])
                            <label class="tx-mode"><input type="radio" name="mode" value="{{ $value }}" @checked($who === $value)><span class="tx-mode-body"><span class="coin">{!! ico($icon) !!}</span><span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span></span></label>
                        @endforeach
                    </div>
                </fieldset>
            </div>

            <div class="col-12 col-md-6" data-for-account>
                <label class="form-label" for="exAccount">{{ __('ui.account') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="exAccount" name="account_id" data-search>
                    <option value="">{{ __('Type a name or code…') }}</option>
                    @foreach ($accounts as $a)<option value="{{ $a->id }}" data-kind="{{ (int) $a->account_sub_category_id === 2 ? 'saraf' : 'customer' }}" @selected((string) $val('account_id', $presetAccount) === (string) $a->id)>{{ $a->code }} · {{ $a->name }}</option>@endforeach
                </select>
                <div class="tx-balances" id="exBalances" aria-live="polite"></div>
            </div>
            <div class="col-12 col-md-6" data-for-account>
                <label class="form-label" for="exHow">{{ __('Where the money goes') }}</label>
                <select class="form-select" id="exHow" name="exchange_mode" data-plain>
                    <option value="no_track" @selected($how === 'no_track')>{{ __('Inside the account (both currencies on it)') }}</option>
                    <option value="treasure" @selected($how === 'treasure')>{{ __('Through the safe (cash in and out)') }}</option>
                    <option value="track" @selected($how === 'track')>{{ __('To a second account') }}</option>
                </select>
            </div>
            <div class="col-12 col-md-6" id="exTrackBox" data-for-track>
                <label class="form-label" for="exTrack">{{ __('The other account') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="exTrack" name="track_account_id" data-search>
                    <option value="">{{ __('Type a name or code…') }}</option>
                    @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected((string) $val('track_account_id', $trackId) === (string) $a->id)>{{ $a->code }} · {{ $a->name }}</option>@endforeach
                </select>
            </div>

            <div class="col-12">
                <div class="ex-calc">
                    <div class="ex-side ex-give">
                        <div class="ex-side-title">{{ __('They give us') }}</div>
                        <select class="form-select" name="from_currency_id" aria-label="{{ __('The currency they give') }}" data-plain required>
                            @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected((string) $from === (string) $c->id)>{{ $c->code }}</option>@endforeach
                        </select>
                        <input type="text" class="form-control ex-big" name="from_amount" value="{{ $val('from_amount', $num($x?->from_amount)) }}" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="0" required aria-label="{{ __('The amount they give') }}">
                    </div>
                    <div class="ex-mid">
                        <div class="ex-op" role="radiogroup" aria-label="{{ __('How the rate works') }}">
                            @php $op = $val('operation_type', $x->operation_type ?? 'multiply'); @endphp
                            <label><input type="radio" name="operation_type" value="multiply" @checked($op !== 'divide')><span title="{{ __('Multiply') }}">×</span></label>
                            <label><input type="radio" name="operation_type" value="divide" @checked($op === 'divide')><span title="{{ __('Divide') }}">÷</span></label>
                        </div>
                        <input type="text" class="form-control ex-rate" name="rate" value="{{ $val('rate', $num($x?->rate)) }}" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="{{ __('ui.rate') }}" required aria-label="{{ __('ui.rate') }}">
                        <div class="ex-hint" id="exHint" aria-live="polite"></div>
                    </div>
                    <div class="ex-side ex-get">
                        <div class="ex-side-title">{{ __('We give them') }}</div>
                        <select class="form-select" name="to_currency_id" aria-label="{{ __('The currency they get') }}" data-plain required>
                            @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected((string) $to === (string) $c->id)>{{ $c->code }}</option>@endforeach
                        </select>
                        <input type="text" class="form-control ex-big" id="exTo" value="{{ $num($x?->to_amount) }}" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="0" aria-label="{{ __('The amount they get') }}">
                    </div>
                </div>
                <div class="ex-result" id="exResult" hidden>
                    <div><small>{{ __('Rate of the day') }}</small><strong id="exBase">—</strong></div>
                    <div><small>{{ __('Profit') }}</small><strong id="exProfit">—</strong></div>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label" for="exNote">{{ __('ui.note') }}</label>
                <input type="text" class="form-control" id="exNote" name="note" value="{{ $val('note', $x?->note) }}" maxlength="500" placeholder="{{ __('Written for you when empty') }}">
            </div>
        </div>
    </div>
    <div class="card-footer d-flex flex-wrap gap-2 justify-content-end">
        <a class="btn btn-light-secondary me-auto" href="{{ back_to($x ? route('admin.exchanges.show', $x->id) : route('admin.exchanges.index')) }}">{{ __('Cancel') }}</a>
        @unless ($x)<button type="submit" class="btn btn-outline-soft" name="again" value="1">{{ __('Save and add another') }}</button>@endunless
        <button type="submit" class="btn btn-primary px-4"><x-icon name="check" class="me-1" /> {{ $x ? __('Save the change') : __('Save') }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('exForm');
    if (!form) return;
    var el = form.elements, usd = parseInt(form.dataset.usd || '0', 10);
    var hint = document.getElementById('exHint'), toBox = document.getElementById('exTo'), result = document.getElementById('exResult');
    var account = el.account_id, rateTouched = !!el.rate.value, baseRate = 0;
    var num = function (v) { v = ERP.latinDigits ? ERP.latinDigits(String(v || '')) : String(v || ''); return parseFloat(v.replace(/,/g, '')) || 0; };
    var code = function (sel) { return sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : ''; };
    var nice = function (n, d) { return (Math.round(n * 100) / 100).toLocaleString('en-US', { maximumFractionDigits: d == null ? 2 : d }); };
    var op = function () { return (form.querySelector('input[name=operation_type]:checked') || {}).value || 'multiply'; };
    var who = function () { return (form.querySelector('input[name=mode]:checked') || {}).value || 'customer'; };

    // the account list shows customers or Sarafs; a walk-in needs no account
    var syncWho = function () {
        var w = who();
        form.querySelectorAll('[data-for-account]').forEach(function (b) { b.hidden = w === 'cash'; });
        Array.prototype.forEach.call(account.options, function (o) { if (o.value) o.hidden = o.dataset.kind !== w; });
        var chosen = account.options[account.selectedIndex];
        if (w !== 'cash' && chosen && chosen.value && chosen.dataset.kind !== w) { account.value = ''; if (account._ssSync) account._ssSync(); }
        syncHow();
    };
    var syncHow = function () { document.getElementById('exTrackBox').hidden = who() === 'cash' || el.exchange_mode.value !== 'track'; };

    // x → y: what they get follows what they give and the rate (and the other way round)
    var work = function (source) {
        var r = num(el.rate.value);
        if (r <= 0) { hint.textContent = ''; return; }
        if (source === 'to') {
            var t = num(toBox.value); if (t > 0) el.from_amount.value = Math.round((op() === 'multiply' ? t / r : t * r) * 100) / 100;
        } else {
            var f = num(el.from_amount.value); toBox.value = f > 0 ? Math.round((op() === 'multiply' ? f * r : f / r) * 100) / 100 : '';
        }
        hint.textContent = op() === 'multiply' ? ('1 ' + code(el.from_currency_id) + ' = ' + nice(r, 6) + ' ' + code(el.to_currency_id)) : ('1 ' + code(el.to_currency_id) + ' = ' + nice(r, 6) + ' ' + code(el.from_currency_id));
        profit();
    };
    // profit against the rate of the day, the way the server counts it
    var profit = function () {
        var f = num(el.from_amount.value), t = num(toBox.value);
        if (!baseRate || !f || !t) { result.hidden = true; return; }
        var p = t - f * baseRate;
        if (who() !== 'cash') p = -p;
        result.hidden = false;
        document.getElementById('exBase').textContent = '1 ' + code(el.from_currency_id) + ' = ' + nice(baseRate, 6) + ' ' + code(el.to_currency_id);
        var box = document.getElementById('exProfit');
        box.textContent = (p < 0 ? '−' : '+') + nice(Math.abs(p)) + ' ' + code(el.to_currency_id);
        box.className = p < 0 ? 'is-owed' : 'is-owing';
    };
    // the rate of the day, and whether it multiplies or divides
    var fetchRate = function () {
        var f = parseInt(el.from_currency_id.value, 10), t = parseInt(el.to_currency_id.value, 10);
        if (!f || !t || f === t) return;
        if (!rateTouched) {
            var div = usd && t === usd && f !== usd;
            form.querySelector('input[name=operation_type][value=' + (div ? 'divide' : 'multiply') + ']').checked = true;
        }
        fetch(form.dataset.rateUrl + '?from_currency_id=' + f + '&to_currency_id=' + t, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                baseRate = num(d.base_rate || d.rate);
                if (!rateTouched && d.rate) el.rate.value = d.rate;
                work('from');
            }).catch(function () {});
    };
    var balances = document.getElementById('exBalances');
    var showBalances = function () {
        balances.innerHTML = '';
        if (!account.value) return;
        fetch(form.dataset.balances + '?account_id=' + encodeURIComponent(account.value), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); }).then(function (d) {
                var html = '';
                Object.keys(d.balances || {}).forEach(function (c) {
                    var b = parseFloat(d.balances[c].balance) || 0; if (Math.abs(b) < 0.005) return;
                    html += '<span class="tx-balance ' + (b < 0 ? 'is-owed' : 'is-owing') + '" dir="ltr"><b>' + ERP.escape(c) + '</b> ' + (b < 0 ? '−' : '') + nice(Math.abs(b)) + '</span>';
                });
                balances.innerHTML = html;
            }).catch(function () {});
    };

    form.addEventListener('change', function (e) {
        var n = e.target.name;
        if (n === 'mode') syncWho();
        if (n === 'exchange_mode') syncHow();
        if (n === 'from_currency_id' || n === 'to_currency_id') {
            if (el.from_currency_id.value === el.to_currency_id.value) {
                var other = Array.prototype.find.call(el.to_currency_id.options, function (o) { return o.value !== el.from_currency_id.value; });
                if (n === 'from_currency_id' && other) el.to_currency_id.value = other.value;
                else Array.prototype.some.call(el.from_currency_id.options, function (o) { if (o.value !== el.to_currency_id.value) { el.from_currency_id.value = o.value; return true; } });
            }
            rateTouched = false; fetchRate();
        }
        if (n === 'operation_type') work('from');
        if (n === 'account_id') { showBalances(); profit(); }
    });
    el.from_amount.addEventListener('input', function () { work('from'); });
    toBox.addEventListener('input', function () { work('to'); });
    el.rate.addEventListener('input', function () { rateTouched = true; work('from'); });

    syncWho();
    if (account.value) showBalances();
    fetchRate();
    work('from');
});
</script>
@endpush
