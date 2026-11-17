@extends('layouts.admin.base')
@section('title', __('ui.exchange_rates'))
@section('modern', '1')

{{--
    Exchange rates in the fida-sarafi style: the form to set the rate of a pair on a day, and the
    rates of the chosen day beside it. Setting a pair again on the same day replaces its rate.
    A US dollar rate also becomes the currency's rate on the Currencies page.
--}}
@php
    $canCreate = auth()->user()->can('create exchange rates');
    $canDelete = auth()->user()->can('delete exchange rates');
    $usd = $currencies->firstWhere('code', 'USD');
    $rateText = fn ($v) => rtrim(rtrim(number_format((float) $v, 6, '.', ','), '0'), '.');
@endphp

@section('content')
<x-page-header :title="__('ui.exchange_rates')" icon="arrow-right-arrow-left" :subtitle="__('The rate of each currency pair, day by day.')">
    <a class="btn btn-outline-soft" href="{{ route('admin.currencies.index') }}"><x-icon name="coins" class="me-1" /> {{ __('administration.currencies') }}</a>
</x-page-header>

<div class="row g-3">
    @if ($canCreate)
    <div class="col-12 col-lg-4">
        <section class="card" aria-labelledby="rateFormTitle">
            <div class="card-header"><h2 class="h5 card-title mb-0" id="rateFormTitle">{{ __('Set a rate') }}</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.exchange-rates.store') }}" id="rateForm" novalidate>
                    @csrf
                    <x-field name="date" type="date" :label="__('ui.date')" :value="$date" required />
                    <div class="d-flex align-items-end gap-2 mb-3">
                        <x-field col="flex-fill mb-0" name="from_currency_id" type="select" :label="__('From')" :options="$currencies->pluck('code', 'id')->all()" :value="$usd?->id" required />
                        <button type="button" class="btn btn-light-secondary mb-0" id="rateSwap" title="{{ __('Swap') }}" aria-label="{{ __('Swap the currencies') }}"><x-icon name="arrow-right-arrow-left" /></button>
                        <x-field col="flex-fill mb-0" name="to_currency_id" type="select" :label="__('To')" :options="$currencies->pluck('code', 'id')->all()" :value="$currencies->firstWhere('code', '!=', 'USD')?->id" required />
                    </div>
                    <x-field name="rate" type="number" step="any" min="0" :label="__('ui.rate')" required dir="ltr" inputmode="decimal" placeholder="0.0000" />
                    <p class="small text-muted" id="rateSay" aria-live="polite"></p>
                    <button type="submit" class="btn btn-primary w-100"><x-icon name="check" class="me-1" /> {{ __('Save the rate') }}</button>
                </form>
            </div>
        </section>
    </div>
    @endif

    <div @class(['col-12', 'col-lg-8' => $canCreate])>
        <section class="card" aria-labelledby="ratesTitle">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <h2 class="h5 card-title mb-0 me-auto" id="ratesTitle">{{ __('Rates of the day') }}</h2>
                <form method="GET" data-live="list" class="d-flex gap-2" role="search">
                    <input type="date" class="form-control" name="date" value="{{ $date }}" aria-label="{{ __('ui.date') }}" max="{{ now()->toDateString() }}">
                </form>
            </div>
            <div class="card-body">
@fragment('list')
<div data-live-part="list" data-filtered="0">
                @if ($days->count() > 1)
                    <nav class="d-flex flex-wrap gap-1 mb-3" aria-label="{{ __('Recent days') }}">
                        @foreach ($days as $day)
                            <a href="{{ route('admin.exchange-rates.index', ['date' => $day]) }}" data-live-link @class(['btn btn-sm', 'btn-primary' => $day === $date, 'btn-light-secondary' => $day !== $date]) @if ($day === $date) aria-current="date" @endif>{{ \Illuminate\Support\Carbon::parse($day)->isToday() ? __('Today') : $day }}</a>
                        @endforeach
                    </nav>
                @endif
                @if ($rates->isEmpty())
                    <div class="empty-state">
                        <div class="es-icon"><x-icon name="arrow-right-arrow-left" /></div>
                        <h2 class="h3">{{ __('No rate on this day') }}</h2>
                        @if ($canCreate)<p>{{ __('Set one with the form.') }}</p>@endif
                    </div>
                @else
                    <div class="table-responsive data-wrap">
                        <table class="table w-100 mb-0 data-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('ui.pair') }}</th>
                                    <th scope="col" class="is-num">{{ __('ui.rate') }}</th>
                                    <th scope="col" class="is-num">{{ __('Back') }}</th>
                                    <th scope="col">{{ __('By') }}</th>
                                    <th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rates as $rate)
                                    <tr>
                                        <td><span class="chip-code">{{ $rate->fromCurrency->code ?? '?' }}</span> {!! ico('arrow-right', 'mx-1 text-muted') !!} <span class="chip-code">{{ $rate->toCurrency->code ?? '?' }}</span></td>
                                        <td class="is-num fw-semibold" dir="ltr">1 {{ $rate->fromCurrency->code ?? '' }} = {{ $rateText($rate->rate) }} {{ $rate->toCurrency->code ?? '' }}</td>
                                        <td class="is-num text-muted" dir="ltr">{{ (float) $rate->rate > 0 ? $rateText(1 / (float) $rate->rate) : '—' }}</td>
                                        <td class="text-muted">{{ $rate->creator->username ?? '—' }} <small>{{ optional($rate->updated_at)->format('H:i') }}</small></td>
                                        <td class="text-end text-nowrap">
                                            <div class="act-group">
                                                @if ($canCreate)
                                                    <button type="button" class="btn-act is-amber" title="{{ __('Change') }}" aria-label="{{ __('Change') }} {{ $rate->fromCurrency->code ?? '' }} {{ $rate->toCurrency->code ?? '' }}"
                                                            data-rate-fill="{{ json_encode(['date' => $date, 'from_currency_id' => $rate->from_currency_id, 'to_currency_id' => $rate->to_currency_id, 'rate' => (float) $rate->rate]) }}">{!! ico('pen') !!}</button>
                                                @endif
                                                @if ($canDelete)
                                                    <form method="POST" action="{{ route('admin.exchange-rates.destroy', $rate->id) }}" class="d-inline" data-confirm="{{ __('Delete this rate?') }}" data-confirm-ok="{{ __('Delete') }}">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn-act is-red" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">{!! ico('trash') !!}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
</div>
@endfragment
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('rateForm');
    if (!form) return;
    var from = form.elements.from_currency_id, to = form.elements.to_currency_id, rate = form.elements.rate, say = document.getElementById('rateSay');
    var code = function (sel) { return sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : ''; };
    var nice = function (n) { return (Math.round(n * 1e6) / 1e6).toLocaleString('en-US', { maximumFractionDigits: 6 }); };
    var explain = function () {
        var r = parseFloat(ERP.latinDigits ? ERP.latinDigits(rate.value) : rate.value);
        say.textContent = r > 0 ? ('1 ' + code(from) + ' = ' + nice(r) + ' ' + code(to) + ' · 1 ' + code(to) + ' = ' + nice(1 / r) + ' ' + code(from)) : '';
    };
    ['input', 'change'].forEach(function (ev) { form.addEventListener(ev, explain); });
    document.getElementById('rateSwap').addEventListener('click', function () {
        var f = from.value; from.value = to.value; to.value = f;
        var r = parseFloat(rate.value); if (r > 0) rate.value = String(Math.round(1e6 / r) / 1e6);
        explain();
    });
    document.addEventListener('click', function (e) {
        var b = e.target.closest && e.target.closest('[data-rate-fill]');
        if (!b) return;
        var d = JSON.parse(b.getAttribute('data-rate-fill'));
        form.elements.date.value = d.date; from.value = d.from_currency_id; to.value = d.to_currency_id; rate.value = d.rate;
        explain(); rate.focus(); rate.select();
    });
    explain();
});
</script>
@endpush
