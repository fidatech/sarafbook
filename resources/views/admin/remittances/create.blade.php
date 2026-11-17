@extends('layouts.admin.base')
@section('title', __('New remittance'))
@section('modern', '1')

{{--
    The remittance form in the fida-sarafi style. Outgoing: we receive the money here and a Saraf
    pays it there. Incoming: a Saraf sends it to us and we pay it here. How it is settled:
    through the safe, left unpaid until the receiver comes (incoming only), or with a second
    account. The partner amount is what the other Saraf records; it follows the amount until it
    is changed. Hawala numbers are suggested from the last one of that Saraf.
--}}
@php
    $again = old('_form') === 'rm';
    $val = fn ($key, $default = null) => $again ? old($key, $default) : $default;
    $type = $val('type', request('type') === 'incoming' ? 'incoming' : 'outgoing');
    // opened from an account: a Saraf is the other side, a customer is the second account
    $preset = request()->integer('account_id') ?: null;
    $presetSaraf = $preset && $sarafs->contains('id', $preset) ? $preset : null;
    $presetTrack = $preset && ! $presetSaraf ? $preset : null;
    $mode = $val('remittance_mode', $presetTrack ? 'track' : ($type === 'incoming' ? 'no_track' : 'treasure'));
    $commission = $val('commission_mode', 'none');
    $defaultCurrency = $currencies->firstWhere('is_default', true)?->id ?? $currencies->first()?->id;
    $currencyOptions = $currencies->pluck('code', 'id');
    $sarafOption = fn ($s, $field) => '<option value="'.$s->id.'"'.((string) $val($field, $presetSaraf) === (string) $s->id ? ' selected' : '').'>'.e($s->code.' · '.$s->name).'</option>';
@endphp

@section('content')
<x-page-header :title="__('New remittance')" icon="paper-plane" :crumbs="[[__('Remittances'), route('admin.remittances.outgoing')], [__('New')]]">
    <a class="btn btn-outline-soft" href="{{ route('admin.remittances.outgoing') }}"><x-icon name="list" class="me-1" /> {{ __('ui.outgoing_remittances') }}</a>
    <a class="btn btn-outline-soft" href="{{ route('admin.remittances.pendingIncoming') }}"><x-icon name="hourglass-half" class="me-1" /> {{ __('ui.pending_incoming_remittances') }}</a>
</x-page-header>

<form method="POST" action="{{ route('admin.remittances.store') }}" class="card" id="rmForm" novalidate
      data-next="{{ route('admin.remittances.nextHawala') }}" data-balances="{{ route('admin.remittances.account-balances') }}">
    @csrf
    <input type="hidden" name="_form" value="rm">
    <input type="hidden" name="page" value="remittance">
    @if (request('back'))<input type="hidden" name="back" value="{{ request('back') }}">@endif
    <div class="card-body">
        @if ($again && $errors->any())
            <div class="alert alert-danger py-2" role="alert"><ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="row g-3">
            <div class="col-12">
                <div class="tx-modes rm-types">
                    <label class="tx-mode"><input type="radio" name="type" value="outgoing" @checked($type === 'outgoing')><span class="tx-mode-body"><span class="coin">{!! ico('arrow-up-right-from-square') !!}</span><span><strong>{{ __('ui.outgoing') }}</strong><small>{{ __('We take the money here; a Saraf pays it there.') }}</small></span></span></label>
                    <label class="tx-mode"><input type="radio" name="type" value="incoming" @checked($type === 'incoming')><span class="tx-mode-body"><span class="coin">{!! ico('download') !!}</span><span><strong>{{ __('ui.incoming') }}</strong><small>{{ __('A Saraf sends it; we pay it here.') }}</small></span></span></label>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label" for="rmDate">{{ __('ui.date') }} <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="rmDate" name="date" value="{{ $val('date', now()->toDateString()) }}" required>
            </div>
            <div class="col-12 col-md-6" data-type="outgoing">
                <label class="form-label" for="rmTo">{{ __('The Saraf who pays it') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="rmTo" name="to_account_id" data-search data-side>
                    <option value="">{{ __('Type a name or code…') }}</option>
                    @foreach ($sarafs as $s){!! $sarafOption($s, 'to_account_id') !!}@endforeach
                </select>
            </div>
            <div class="col-12 col-md-6" data-type="incoming">
                <label class="form-label" for="rmFrom">{{ __('The Saraf who sent it') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="rmFrom" name="from_account_id" data-search data-side>
                    <option value="">{{ __('Type a name or code…') }}</option>
                    @foreach ($sarafs as $s){!! $sarafOption($s, 'from_account_id') !!}@endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="rmNo">{{ __('ui.hawala_no') }}</label>
                <input type="text" class="form-control" id="rmNo" name="hawala_number" value="{{ $val('hawala_number') }}" dir="ltr" autocomplete="off">
            </div>

            <div class="col-12">
                <fieldset>
                    <legend class="form-label">{{ __('How it is settled') }}</legend>
                    <div class="tx-modes">
                        <label class="tx-mode"><input type="radio" name="remittance_mode" value="treasure" @checked($mode === 'treasure')><span class="tx-mode-body"><span class="coin">{!! ico('vault') !!}</span><span><strong>{{ __('Through the safe') }}</strong><small>{{ __('Paid or taken in cash now.') }}</small></span></span></label>
                        <label class="tx-mode" data-type="incoming"><input type="radio" name="remittance_mode" value="no_track" @checked($mode === 'no_track')><span class="tx-mode-body"><span class="coin">{!! ico('hourglass-half') !!}</span><span><strong>{{ __('Unpaid for now') }}</strong><small>{{ __('Paid when the receiver comes, from the pending list.') }}</small></span></span></label>
                        <label class="tx-mode"><input type="radio" name="remittance_mode" value="track" @checked($mode === 'track')><span class="tx-mode-body"><span class="coin">{!! ico('arrow-right-arrow-left') !!}</span><span><strong>{{ __('With a second account') }}</strong><small>{{ __('A customer or Saraf account takes the other side.') }}</small></span></span></label>
                    </div>
                </fieldset>
            </div>
            <div class="col-12 col-md-6" data-mode="track">
                <label class="form-label" for="rmTrack">{{ __('ui.track_account') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="rmTrack" name="track_account_id" data-search>
                    <option value="">{{ __('Type a name or code…') }}</option>
                    @foreach ($accounts as $a)<option value="{{ $a->id }}" data-kind="{{ $a->account_sub_category_id }}" @selected((string) $val('track_account_id', $presetTrack) === (string) $a->id)>{{ $a->code }} · {{ $a->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-3" data-mode="track" id="rmTrackNoBox">
                <label class="form-label" for="rmTrackNo">{{ __('remittances.track_hawala_no') }}</label>
                <input type="text" class="form-control" id="rmTrackNo" name="track_hawala_number" value="{{ $val('track_hawala_number') }}" dir="ltr" autocomplete="off">
            </div>
            <div class="col-12"><div class="tx-balances" id="rmBalances" aria-live="polite"></div></div>

            <div class="col-12"><div class="tx-section">{{ __('Amounts') }}</div></div>
            <div class="col-5 col-md-2">
                <label class="form-label" for="rmCur">{{ __('ui.currency') }}</label>
                <select class="form-select" id="rmCur" name="currency_id" required data-plain>@foreach ($currencyOptions as $id => $code)<option value="{{ $id }}" @selected((string) $val('currency_id', $defaultCurrency) === (string) $id)>{{ $code }}</option>@endforeach</select>
            </div>
            <div class="col-7 col-md-4">
                <label class="form-label" for="rmAmount"><span data-type="outgoing">{{ __('We receive') }}</span><span data-type="incoming">{{ __('We pay') }}</span> <span class="text-danger">*</span></label>
                <input type="text" class="form-control ex-big" id="rmAmount" name="amount" value="{{ $val('amount') }}" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="0" required>
            </div>
            <div class="col-5 col-md-2">
                <label class="form-label" for="rmPCur">{{ __('Partner currency') }}</label>
                <select class="form-select" id="rmPCur" name="partner_currency_id" data-plain>@foreach ($currencyOptions as $id => $code)<option value="{{ $id }}" @selected((string) $val('partner_currency_id', $defaultCurrency) === (string) $id)>{{ $code }}</option>@endforeach</select>
            </div>
            <div class="col-7 col-md-4">
                <label class="form-label" for="rmPAmount">{{ __('The partner records') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="rmPAmount" name="partner_amount" value="{{ $val('partner_amount') }}" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="0">
                <div class="form-text">{{ __('What the other Saraf writes for it; the same as the amount unless changed.') }}</div>
            </div>
            <input type="hidden" name="actual_currency_id" value="{{ $val('actual_currency_id', $defaultCurrency) }}">
            <input type="hidden" name="actual_amount" value="{{ $val('actual_amount') }}">

            <div class="col-12 col-md-6">
                <label class="form-label" for="rmSender">{{ __('ui.sender') }}</label>
                <input type="text" class="form-control" id="rmSender" name="sender" value="{{ $val('sender') }}" maxlength="255" autocomplete="off">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="rmReceiver">{{ __('ui.receiver') }}</label>
                <input type="text" class="form-control" id="rmReceiver" name="receiver" value="{{ $val('receiver') }}" maxlength="255" autocomplete="off">
            </div>

            <div class="col-12"><div class="tx-section">{{ __('Commission') }}</div></div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="rmCom">{{ __('ui.commission_mode') }}</label>
                <select class="form-select" id="rmCom" name="commission_mode" data-plain>
                    <option value="none" @selected($commission === 'none')>{{ __('ui.no_commission') }}</option>
                    <option value="company" @selected($commission === 'company')>{{ __('ui.company_commission') }}</option>
                    <option value="split" @selected($commission === 'split')>{{ __('ui.split_commission') }}</option>
                    <option value="pay" @selected($commission === 'pay')>{{ __('ui.pay_commission') }}</option>
                </select>
            </div>
            <div class="col-4 col-md-2" data-com="company split pay">
                <label class="form-label" for="rmComCur">{{ __('ui.currency') }}</label>
                <select class="form-select" id="rmComCur" name="commission_currency_id" data-plain>@foreach ($currencyOptions as $id => $code)<option value="{{ $id }}" @selected((string) $val('commission_currency_id', $defaultCurrency) === (string) $id)>{{ $code }}</option>@endforeach</select>
            </div>
            <div class="col-8 col-md-3" data-com="company split">
                <label class="form-label" for="rmComOur">{{ __('ui.commission_kabul') }}</label>
                <input type="text" class="form-control" id="rmComOur" name="commission_kabul_amount" value="{{ $val('commission_kabul_amount') }}" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="0">
            </div>
            <div class="col-8 col-md-3" data-com="split pay">
                <label class="form-label" for="rmComThem">{{ __('ui.commission_partner') }}</label>
                <input type="text" class="form-control" id="rmComThem" name="commission_partner_amount" value="{{ $val('commission_partner_amount') }}" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="0">
            </div>

            <div class="col-12">
                <label class="form-label" for="rmDesc">{{ __('ui.description') }}</label>
                <input type="text" class="form-control" id="rmDesc" name="description" value="{{ $val('description') }}" maxlength="1000">
            </div>
        </div>
    </div>
    <div class="card-footer d-flex flex-wrap gap-2 justify-content-end">
        <button type="submit" class="btn btn-outline-soft" name="again" value="1">{{ __('Save and add another') }}</button>
        <button type="submit" class="btn btn-primary px-4"><x-icon name="check" class="me-1" /> {{ __('Save') }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('rmForm');
    if (!form) return;
    var el = form.elements, touched = { no: !!el.hawala_number.value, trackNo: !!el.track_hawala_number.value, partner: !!el.partner_amount.value };
    var type = function () { return (form.querySelector('input[name=type]:checked') || {}).value || 'outgoing'; };
    var mode = function () { return (form.querySelector('input[name=remittance_mode]:checked') || {}).value || 'treasure'; };
    var side = function () { return type() === 'incoming' ? el.from_account_id : el.to_account_id; };
    var trackKind = function () { var o = el.track_account_id.options[el.track_account_id.selectedIndex]; return o ? parseInt(o.dataset.kind || '0', 10) : 0; };

    var sync = function () {
        var t = type(), m = mode();
        // "unpaid for now" is for incoming only
        if (t === 'outgoing' && m === 'no_track') { form.querySelector('input[name=remittance_mode][value=treasure]').checked = true; m = 'treasure'; }
        form.querySelectorAll('[data-type]').forEach(function (b) { b.hidden = b.getAttribute('data-type') !== t; });
        form.querySelectorAll('[data-mode]').forEach(function (b) { b.hidden = b.getAttribute('data-mode') !== m; });
        // a customer as second account has no hawala number of its own
        document.getElementById('rmTrackNoBox').hidden = m !== 'track' || trackKind() === 1;
        var c = el.commission_mode.value;
        form.querySelectorAll('[data-com]').forEach(function (b) { b.hidden = b.getAttribute('data-com').split(' ').indexOf(c) === -1; });
        // the second account is never one of the two Sarafs
        Array.prototype.forEach.call(el.track_account_id.options, function (o) { if (o.value) o.hidden = o.value === side().value; });
    };
    // the next hawala number of the chosen Saraf, unless one was typed
    var next = function (accountId, column, input, key) {
        if (!accountId || touched[key]) return;
        fetch(form.dataset.next + '?account_id=' + accountId + '&column=' + column + '&type=' + type(), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); }).then(function (d) { if (!touched[key] && d.number) input.value = d.number; }).catch(function () {});
    };
    var numbers = function () {
        next(side().value, 'hawala_number', el.hawala_number, 'no');
        if (mode() === 'track' && trackKind() !== 1) next(el.track_account_id.value, 'track_hawala_number', el.track_hawala_number, 'trackNo');
    };
    var nice = function (n) { return (Math.round(n * 100) / 100).toLocaleString('en-US', { maximumFractionDigits: 2 }); };
    var box = document.getElementById('rmBalances');
    var balances = function () {
        var list = [[side().value, type() === 'incoming' ? @json(__('The Saraf who sent it')) : @json(__('The Saraf who pays it'))]];
        if (mode() === 'track' && el.track_account_id.value) list.push([el.track_account_id.value, @json(__('ui.track_account'))]);
        list = list.filter(function (x) { return x[0]; });
        box.innerHTML = '';
        list.forEach(function (x) {
            fetch(form.dataset.balances + '?account_id=' + x[0], { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
                var html = '<span class="text-muted small">' + ERP.escape(x[1]) + ':</span>';
                Object.keys(d.balances || {}).forEach(function (c) {
                    var b = parseFloat(d.balances[c].balance) || 0; if (Math.abs(b) < 0.005) return;
                    html += '<span class="tx-balance ' + (b < 0 ? 'is-owed' : 'is-owing') + '" dir="ltr"><b>' + ERP.escape(c) + '</b> ' + (b < 0 ? '−' : '') + nice(Math.abs(b)) + '</span>';
                });
                var row = document.createElement('div'); row.className = 'tx-balances'; row.innerHTML = html; box.appendChild(row);
            }).catch(function () {});
        });
    };

    form.addEventListener('change', function (e) {
        var n = e.target.name;
        if (n === 'type') { touched.no = false; el.hawala_number.value = ''; if (type() === 'incoming') form.querySelector('input[name=remittance_mode][value=no_track]').checked = true; }
        if (n === 'currency_id') { el.partner_currency_id.value = el.currency_id.value; el.actual_currency_id.value = el.currency_id.value; el.commission_currency_id.value = el.currency_id.value; }
        if (n === 'from_account_id' || n === 'to_account_id') { touched.no = false; }
        if (n === 'track_account_id') { touched.trackNo = false; el.track_hawala_number.value = ''; }
        if (n === 'commission_mode' && el.commission_mode.value === 'pay') el.commission_kabul_amount.value = '';
        sync();
        if (['type', 'from_account_id', 'to_account_id', 'track_account_id', 'remittance_mode'].indexOf(n) !== -1) { numbers(); balances(); }
    });
    el.hawala_number.addEventListener('input', function () { touched.no = true; });
    el.track_hawala_number.addEventListener('input', function () { touched.trackNo = true; });
    el.partner_amount.addEventListener('input', function () { touched.partner = true; el.actual_amount.value = el.partner_amount.value; });
    el.amount.addEventListener('input', function () {
        if (!touched.partner) el.partner_amount.value = el.amount.value;
        el.actual_amount.value = el.partner_amount.value || el.amount.value;
    });
    form.addEventListener('submit', function () {
        if (!el.partner_amount.value) el.partner_amount.value = el.amount.value;
        if (!el.actual_amount.value) el.actual_amount.value = el.partner_amount.value;
    });
    sync(); numbers(); balances();
});
</script>
@endpush
