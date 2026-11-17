@extends('layouts.admin.base')
@section('title', __('Profit and loss'))
@section('modern', '1')

{{-- Profit and loss by currency: commission and exchange profit in, expenses and exchange losses out. --}}
@php $amountText = fn ($v) => format_amount((float) $v, 2); @endphp

@section('content')
<x-page-header :title="__('Profit and loss')" icon="chart-line" :subtitle="__('What the business earned and spent, by currency.')">
    <button type="button" class="btn btn-outline-soft" onclick="window.print()"><x-icon name="print" class="me-1" /> {{ __('Print') }}</button>
</x-page-header>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="list-tools mb-0" data-live="list">
            <label class="small text-muted" for="plFrom">{{ __('From month') }}</label>
            <input type="month" class="form-control" id="plFrom" name="from_month" value="{{ $filters['from_month'] }}">
            <label class="small text-muted" for="plTo">{{ __('To month') }}</label>
            <input type="month" class="form-control" id="plTo" name="to_month" value="{{ $filters['to_month'] }}">
            <select class="form-select" name="currency_id" aria-label="{{ __('ui.currency') }}" data-plain>
                <option value="">{{ __('All currencies') }}</option>
                @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected($filters['currency_id'] === $c->id)>{{ $c->code }}</option>@endforeach
            </select>
            <a class="btn btn-light-secondary" href="{{ url()->current() }}" data-live-clear title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
        </form>
    </div>
</div>

@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}">
    <p class="text-muted small">{{ $data['period'] ?? '' }}</p>
    @if (empty($data['reports']))
        <div class="card"><div class="card-body"><div class="empty-state"><div class="es-icon"><x-icon name="chart-line" /></div><h2 class="h3">{{ __('Nothing earned or spent in this period') }}</h2></div></div></div>
    @else
        <div class="row g-3">
            @foreach ($data['reports'] as $r)
                <div class="col-12 col-xl-6">
                    <section class="card h-100">
                        <div class="card-header d-flex align-items-center">
                            <h2 class="h5 card-title mb-0 me-auto"><span class="chip-code">{{ $r['currency'] }}</span></h2>
                            <span class="text-muted small me-2">{{ $r['net_profit'] < 0 ? __('Loss') : __('Profit') }}</span>
                            <strong dir="ltr" class="{{ $r['net_profit'] < 0 ? 'is-owed' : 'is-owing' }} fs-5">{{ $r['net_profit'] < 0 ? '−' : '' }}{{ $amountText(abs($r['net_profit'])) }}</strong>
                        </div>
                        <div class="card-body">
                            <div class="pl-cols">
                                <div>
                                    <h3 class="h6 text-muted">{{ __('Income') }}</h3>
                                    @forelse ($r['income'] as $line)<div class="exp-cat"><span>{{ $line['name'] }}</span><span dir="ltr" class="is-owing fw-semibold">{{ $amountText($line['amount']) }}</span></div>@empty<p class="text-muted small">—</p>@endforelse
                                    <div class="exp-cat pl-total"><span>{{ __('Total') }}</span><span dir="ltr">{{ $amountText($r['total_income']) }}</span></div>
                                </div>
                                <div>
                                    <h3 class="h6 text-muted">{{ __('Expense') }}</h3>
                                    @forelse ($r['expenses'] as $line)<div class="exp-cat"><span>{{ $line['name'] }}</span><span dir="ltr" class="is-owed fw-semibold">{{ $amountText($line['amount']) }}</span></div>@empty<p class="text-muted small">—</p>@endforelse
                                    <div class="exp-cat pl-total"><span>{{ __('Total') }}</span><span dir="ltr">{{ $amountText($r['total_expense']) }}</span></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endfragment
@endsection
