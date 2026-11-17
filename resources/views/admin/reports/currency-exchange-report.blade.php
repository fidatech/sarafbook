@extends('layouts.admin.base')
@section('title', __('Exchanges by currency'))
@section('modern', '1')

{{-- Exchanges grouped by the currency given: for each, the counts, profit, pairs and the newest exchanges. --}}
@php
    $amountText = fn ($v) => format_amount((float) $v, 2);
    $rateText = fn ($v) => rtrim(rtrim(number_format((float) $v, 6, '.', ','), '0'), '.');
    $tabs = collect($data['tabs'] ?? [])->filter(fn ($t) => ($t['summary']['records'] ?? 0) > 0)->values();
@endphp

@section('content')
<x-page-header :title="__('Exchanges by currency')" icon="layer-group" :subtitle="__('For each currency given, what it was exchanged for.')">
    <a class="btn btn-outline-soft" href="{{ route('admin.reports.customer-exchange-report') }}"><x-icon name="coins" class="me-1" /> {{ __('Exchange report') }}</a>
    <a class="btn btn-outline-soft" href="{{ $print }}" target="_blank" rel="noopener" data-no-prefetch id="cxPrint"><x-icon name="print" class="me-1" /> {{ __('Print') }}</a>
</x-page-header>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="list-tools mb-0" data-live="list">
            <select class="form-select" name="currency_id" aria-label="{{ __('ui.currency') }}" data-plain>
                <option value="">{{ __('All currencies') }}</option>
                @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected($filters['currency_id'] === $c->id)>{{ $c->code }}</option>@endforeach
            </select>
            <select class="form-select" name="type" aria-label="{{ __('ui.type') }}" data-plain>
                <option value="">{{ __('Buy and sell') }}</option>
                <option value="sell" @selected($filters['type'] === 'sell')>{{ __('Sold dollars') }}</option>
                <option value="purchase" @selected($filters['type'] === 'purchase')>{{ __('Bought dollars') }}</option>
            </select>
            <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] }}" aria-label="{{ __('From date') }}" title="{{ __('From date') }}">
            <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] }}" aria-label="{{ __('To date') }}" title="{{ __('To date') }}">
        </form>
    </div>
</div>

@fragment('list')
<div data-live-part="list" data-filtered="1" data-print="{{ $print }}">
    @if ($tabs->isEmpty())
        <div class="card"><div class="card-body"><div class="empty-state"><div class="es-icon"><x-icon name="layer-group" /></div><h2 class="h3">{{ __('No exchange in this period') }}</h2></div></div></div>
    @else
        @foreach ($tabs as $tab)
            @php $s = $tab['summary']; @endphp
            <section class="card mb-3">
                <div class="card-header d-flex flex-wrap align-items-center gap-2">
                    <h2 class="h5 card-title mb-0 me-auto">{{ __(':code given', ['code' => $tab['currency_code']]) }}</h2>
                    <span class="text-muted small">{{ __(':count exchanges', ['count' => number_format($s['records'])]) }}</span>
                    <strong dir="ltr" class="{{ $s['profit'] < 0 ? 'is-owed' : 'is-owing' }}">{{ __('Profit') }} {{ $amountText($s['profit']) }}</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive mb-3">
                        <table class="table w-100 mb-0 keep-table acc-totals">
                            <thead><tr><th scope="col">{{ __('ui.pair') }}</th><th scope="col" class="text-end">{{ __('Gave') }}</th><th scope="col" class="text-end">{{ __('Average rate') }}</th><th scope="col" class="text-end">{{ __('Got') }}</th><th scope="col" class="text-end">{{ __('Exchanges') }}</th></tr></thead>
                            <tbody>
                                @foreach ($tab['totals'] as $p)
                                    <tr><th scope="row"><span class="chip-code">{{ $p['from_currency'] }}</span> → <span class="chip-code">{{ $p['to_currency'] }}</span></th><td class="text-end" dir="ltr">{{ $amountText($p['from_total']) }}</td><td class="text-end text-muted" dir="ltr">{{ $rateText($p['average_rate']) }}</td><td class="text-end" dir="ltr">{{ $amountText($p['to_total']) }}</td><td class="text-end">{{ number_format($p['records']) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <details>
                        <summary class="fw-semibold text-primary">{{ __('The newest exchanges') }}</summary>
                        <div class="table-responsive data-wrap mt-2">
                            <table class="table w-100 mb-0 data-table keep-table">
                                <tbody>
                                    @foreach (array_slice($tab['rows'], 0, 25) as $r)
                                        <tr>
                                            <td class="text-nowrap">{{ $r['date_time'] }}</td>
                                            <td>{{ $r['customer_name'] }}</td>
                                            <td class="is-num" dir="ltr">{{ $amountText($r['from_amount']) }} {{ $r['from_currency'] }}</td>
                                            <td class="is-num text-muted" dir="ltr">{{ $rateText($r['rate']) }}</td>
                                            <td class="is-num" dir="ltr">{{ $amountText($r['to_amount']) }} {{ $r['to_currency'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                </div>
            </section>
        @endforeach
    @endif
</div>
@endfragment
@endsection

@push('scripts')
<script>document.addEventListener('erp:live', function () { var p = document.querySelector('[data-live-part="list"]'), a = document.getElementById('cxPrint'); if (p && a) a.href = p.dataset.print; });</script>
@endpush
