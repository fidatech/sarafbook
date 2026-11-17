@extends('layouts.admin.base')
@section('title', __('Exchange report'))
@section('modern', '1')

{{-- Exchanges of a period (this month at first): totals, the currency pairs, and the exchanges 50 to a page. --}}
@php
    $amountText = fn ($v) => format_amount((float) $v, 2);
    $rateText = fn ($v) => rtrim(rtrim(number_format((float) $v, 6, '.', ','), '0'), '.');
    $s = $data['summary'] ?? [];
@endphp

@section('content')
<x-page-header :title="__('Exchange report')" icon="coins" :subtitle="__('The exchanges of a period, with their totals and pairs.')">
    <a class="btn btn-outline-soft" href="{{ route('admin.reports.currency-exchange-report') }}"><x-icon name="layer-group" class="me-1" /> {{ __('By currency') }}</a>
    <a class="btn btn-outline-soft" href="{{ $print }}" target="_blank" rel="noopener" data-no-prefetch id="cxPrint"><x-icon name="print" class="me-1" /> {{ __('Print') }}</a>
</x-page-header>

<div class="card">
    <div class="card-body">
        <form method="GET" class="list-tools" role="search" data-live="list">
            <select class="form-select" name="account_id" aria-label="{{ __('ui.account') }}" data-search>
                <option value="">{{ __('Every account') }}</option>
                @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected($filters['account_id'] === $a->id)>{{ $a->code }} · {{ $a->name }}</option>@endforeach
            </select>
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

@fragment('list')
<div data-live-part="list" data-filtered="1" data-print="{{ $print }}">
        <div class="tx-totals mb-3">
            <div class="tx-total"><span class="text-muted">{{ __('Exchanges') }}</span><strong>{{ number_format((int) ($s['total_records'] ?? 0)) }}</strong></div>
            <div class="tx-total"><span class="text-muted">{{ __('Sold dollars') }}</span><strong>{{ number_format((int) ($s['sell_count'] ?? 0)) }}</strong></div>
            <div class="tx-total"><span class="text-muted">{{ __('Bought dollars') }}</span><strong>{{ number_format((int) ($s['purchase_count'] ?? 0)) }}</strong></div>
            <div class="tx-total"><span class="text-muted">{{ __('Volume') }}</span><strong dir="ltr">{{ $amountText($s['total_usd_amount'] ?? 0) }} USD</strong></div>
            <div class="tx-total"><span class="text-muted">{{ __('Profit') }}</span><strong dir="ltr" class="{{ ($s['total_profit'] ?? 0) < 0 ? 'is-owed' : 'is-owing' }}">{{ $amountText($s['total_profit'] ?? 0) }}</strong></div>
        </div>
        @if (! empty($data['currency_pair_totals']))
            <div class="table-responsive mb-3">
                <table class="table w-100 mb-0 keep-table acc-totals">
                    <thead><tr><th scope="col">{{ __('ui.pair') }}</th><th scope="col" class="text-end">{{ __('Gave') }}</th><th scope="col" class="text-end">{{ __('Average rate') }}</th><th scope="col" class="text-end">{{ __('Got') }}</th><th scope="col" class="text-end">{{ __('Exchanges') }}</th></tr></thead>
                    <tbody>
                        @foreach ($data['currency_pair_totals'] as $p)
                            <tr><th scope="row"><span class="chip-code">{{ $p['from_currency'] }}</span> → <span class="chip-code">{{ $p['to_currency'] }}</span></th><td class="text-end" dir="ltr">{{ $amountText($p['from_total']) }}</td><td class="text-end text-muted" dir="ltr">{{ $rateText($p['average_rate']) }}</td><td class="text-end" dir="ltr">{{ $amountText($p['to_total']) }}</td><td class="text-end">{{ number_format($p['records']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if ($rows->isEmpty())
            <div class="empty-state"><div class="es-icon"><x-icon name="coins" /></div><h2 class="h3">{{ __('No exchange in this period') }}</h2></div>
        @else
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead><tr><th scope="col">{{ __('ui.date') }}</th><th scope="col">{{ __('With') }}</th><th scope="col" class="is-num">{{ __('Gave') }}</th><th scope="col" class="is-num">{{ __('ui.rate') }}</th><th scope="col" class="is-num">{{ __('Got') }}</th><th scope="col" class="is-num">{{ __('Profit') }}</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $x)
                            <tr>
                                <td class="text-nowrap"><a href="{{ route('admin.exchanges.show', $x->id) }}">{{ \Illuminate\Support\Carbon::parse($x->date)->format('Y-m-d') }}</a></td>
                                <td>{{ $x->mode === 'cash' || ! $x->account ? __('Walk-in (cash)') : $x->account->name }}</td>
                                <td class="is-num" dir="ltr">{{ $amountText($x->from_amount) }} <small class="text-muted">{{ $x->fromCurrency->code ?? '' }}</small></td>
                                <td class="is-num text-muted" dir="ltr">{{ $rateText($x->rate) }}</td>
                                <td class="is-num" dir="ltr">{{ $amountText($x->to_amount) }} <small class="text-muted">{{ $x->toCurrency->code ?? '' }}</small></td>
                                <td class="is-num {{ $x->profit < 0 ? 'is-owed' : 'is-owing' }}" dir="ltr">{{ (float) $x->profit == 0 ? '—' : $amountText($x->profit).' '.($x->profitCurrency->code ?? '') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $rows->links('partials.pagination') }}
        @endif
</div>
@endfragment
    </div>
</div>
@endsection

@push('scripts')
<script>document.addEventListener('erp:live', function () { var p = document.querySelector('[data-live-part="list"]'), a = document.getElementById('cxPrint'); if (p && a) a.href = p.dataset.print; });</script>
@endpush
