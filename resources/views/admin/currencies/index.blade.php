@extends('layouts.admin.base')
@section('title', __('administration.currencies'))
@section('modern', '1')

{{--
    Currencies in the fida-sarafi style: one card with the list and one pop-up for adding and
    changing. The rate is how much of the currency one US dollar buys. A currency money was
    recorded in can be switched off, not deleted.
--}}
@php
    $canCreate = auth()->user()->can('create currencies');
    $canUpdate = auth()->user()->can('update currencies');
    $canDelete = auth()->user()->can('delete currencies');
    $known = require resource_path('data/currencies.php');
@endphp

@section('content')
<x-page-header :title="__('administration.currencies')" icon="coins" :subtitle="__('The currencies you trade and their rate to the US dollar.')">
    @if ($canCreate)
        <button class="btn btn-primary" type="button" data-modal-form="#currencyForm"><x-icon name="plus" class="me-1" /> {{ __('ui.add_currency') }}</button>
    @endif
</x-page-header>

<div class="card">
    <div class="card-body">
        @if ($currencies->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="coins" /></div>
                <h2 class="h3">{{ __('No currency yet') }}</h2>
                <p>{{ __('Add US dollar first; every other rate is counted against it.') }}</p>
            </div>
        @else
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ui.currency') }}</th>
                            <th scope="col">{{ __('Country') }}</th>
                            <th scope="col" class="is-num">{{ __('1 USD =') }}</th>
                            <th scope="col">{{ __('ui.status') }}</th>
                            <th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($currencies as $currency)
                            @php
                                $inUse = in_array($currency->id, $used, true);
                                $fill = json_encode(['name' => $currency->name, 'code' => $currency->code, 'symbol' => $currency->symbol, 'country' => $currency->country,
                                    'exchange_rate' => rtrim(rtrim(number_format((float) $currency->exchange_rate, 6, '.', ''), '0'), '.'),
                                    'is_default' => (bool) $currency->is_default, 'is_active' => (bool) $currency->is_active], JSON_UNESCAPED_UNICODE);
                            @endphp
                            <tr>
                                <td>
                                    <div class="row-who">
                                        <span @class(['coin', 'is-off' => ! $currency->is_active]) aria-hidden="true">{{ $currency->symbol ?: mb_substr($currency->code, 0, 1) }}</span>
                                        <div class="min-w-0">
                                            <span class="row-name">{{ $currency->name }}</span>
                                            <div class="row-meta"><span class="chip-code">{{ $currency->code }}</span></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted">{{ $currency->country ?: '—' }}</td>
                                <td class="is-num fw-semibold" dir="ltr">{{ $currency->code === 'USD' ? '1' : rtrim(rtrim(number_format((float) $currency->exchange_rate, 4), '0'), '.') }}</td>
                                <td>
                                    @if ($currency->is_active)
                                        <span class="badge-soft success"><span class="dot"></span>{{ __('administration.active') }}</span>
                                    @else
                                        <span class="badge-soft secondary"><span class="dot"></span>{{ __('administration.inactive') }}</span>
                                    @endif
                                    @if ($currency->is_default)
                                        <span class="badge-soft warning">{{ __('administration.default') }}</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="act-group">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-act is-amber" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }} {{ $currency->code }}"
                                                    data-modal-form="#currencyForm" data-fill="{{ $fill }}" data-action="{{ route('admin.currencies.update', $currency->id) }}" data-method="PUT">{!! ico('pen') !!}</button>
                                        @endif
                                        @if ($canDelete && ! $inUse && ! $currency->is_default)
                                            <form method="POST" action="{{ route('admin.currencies.destroy', $currency->id) }}" class="d-inline"
                                                  data-confirm="{{ __('Delete :name?', ['name' => $currency->code]) }}" data-confirm-ok="{{ __('Delete') }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-act is-red" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }} {{ $currency->code }}">{!! ico('trash') !!}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-3 mb-0">{{ __('A currency that has records cannot be deleted; switch it off to hide it from new entries.') }}</p>
        @endif
    </div>
</div>

@if ($canCreate || $canUpdate)
<x-form-modal id="currencyForm" :title="__('ui.add_currency')" :title-edit="__('Edit currency')" :action="route('admin.currencies.store')">
    <div class="row g-3">
        <x-field col="col-6" modal="currencyForm" name="code" :label="__('Code')" required list="currencyCodes" maxlength="10" dir="ltr" autocomplete="off" style="text-transform:uppercase" />
        <x-field col="col-6" modal="currencyForm" name="symbol" :label="__('ui.symbol')" maxlength="10" />
        <x-field col="col-12" modal="currencyForm" name="name" :label="__('ui.name')" required maxlength="100" />
        <x-field col="col-6" modal="currencyForm" name="country" :label="__('Country')" maxlength="100" />
        <x-field col="col-6" modal="currencyForm" name="exchange_rate" type="number" step="any" min="0" :label="__('1 USD =')" required dir="ltr" :hint="__('US dollar is always 1.')" />
        <x-field col="col-6" modal="currencyForm" name="is_active" type="switch" :label="__('administration.active')" :value="true" />
        <x-field col="col-6" modal="currencyForm" name="is_default" type="switch" :label="__('administration.default')" />
    </div>
    <datalist id="currencyCodes">
        @foreach ($known as $code => $meta)<option value="{{ $code }}">{{ $meta['country'] }}</option>@endforeach
    </datalist>
</x-form-modal>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('#currencyForm form');
    if (!form) return;
    var known = @json($known);
    var code = form.elements.code, rate = form.elements.exchange_rate;
    var fit = function () {
        code.value = code.value.toUpperCase().replace(/\s+/g, '');
        var meta = known[code.value];
        if (meta) {
            if (!form.elements.symbol.value) form.elements.symbol.value = meta.symbol;
            if (!form.elements.country.value) form.elements.country.value = meta.country;
        }
        var usd = code.value === 'USD';
        if (usd) rate.value = 1;
        rate.readOnly = usd;
    };
    code.addEventListener('input', fit);
    form.addEventListener('erp:form-open', fit);
});
</script>
@endpush
