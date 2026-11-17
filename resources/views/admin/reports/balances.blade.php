@extends('layouts.admin.base')
@php
    $titles = ['customers' => __('Customer balances'), 'creditors' => __('Creditors'), 'debtors' => __('Debtors')];
    $subs = ['customers' => __('Every account with a balance, in each currency.'), 'creditors' => __('Accounts we owe money to.'), 'debtors' => __('Accounts that owe us money.')];
    $icons = ['customers' => 'users', 'creditors' => 'hand-holding-dollar', 'debtors' => 'money-bill-trend-up'];
    $amountText = fn ($v) => format_amount((float) $v, 2);
    $typeName = fn ($id) => (__('accounts.types')[optional($subCategories->firstWhere('id', $id))->name] ?? optional($subCategories->firstWhere('id', $id))->name) ?: '—';
@endphp
@section('title', $titles[$kind])
@section('modern', '1')

{{--
    Customer balances, creditors and debtors: accounts with their balance in each currency, from
    the stored balances, biggest first. Shared by the three report pages (ReportsController::balanceReport).
--}}

@section('content')
<x-page-header :title="$titles[$kind]" :icon="$icons[$kind]" :subtitle="$subs[$kind]">
    <a class="btn btn-outline-soft" href="{{ $print }}" target="_blank" rel="noopener" data-no-prefetch><x-icon name="print" class="me-1" /> {{ __('Print') }}</a>
</x-page-header>

<nav class="rm-tabs mb-3" aria-label="{{ __('Balance reports') }}">
    @foreach (['customers' => route('admin.reports.customers'), 'debtors' => route('admin.reports.debtors'), 'creditors' => route('admin.reports.creditors')] as $k => $url)
        <a href="{{ $url }}" @class(['rm-tab', 'is-active' => $k === $kind]) @if ($k === $kind) aria-current="page" @endif>{!! ico($icons[$k], 'me-1') !!} {{ $titles[$k] }}</a>
    @endforeach
</nav>

<div class="card">
    <div class="card-body">
        <form method="GET" class="list-tools" role="search" data-live="list">
            <input type="search" class="form-control grow" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Name or code…') }}" aria-label="{{ __('ui.search') }}">
            <select class="form-select" name="sub_category_id" aria-label="{{ __('ui.type') }}" data-plain>
                <option value="">{{ __('Every type') }}</option>
                @foreach ($subCategories as $sub)<option value="{{ $sub->id }}" @selected($filters['sub_category_id'] === $sub->id)>{{ __('accounts.types_plural')[$sub->name] ?? $sub->name }}</option>@endforeach
            </select>
            <select class="form-select" name="currency_id" aria-label="{{ __('ui.currency') }}" data-plain>
                <option value="">{{ __('All currencies') }}</option>
                @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected($filters['currency_id'] === $c->id)>{{ $c->code }}</option>@endforeach
            </select>
            <a class="btn btn-light-secondary" href="{{ url()->current() }}" data-live-clear title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
        </form>

@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}">
        @if ($accounts->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon :name="$icons[$kind]" /></div>
                <h2 class="h3">{{ $filtered ? __('Nothing found') : __('No balance here') }}</h2>
            </div>
        @else
            <div class="tx-totals mb-3">
                <span class="tx-totals-label">{{ __('Total') }}</span>
                @foreach ($columns as $c)
                    @php $t = $totals[$c->id]; @endphp
                    <div class="tx-total">
                        <span class="chip-code">{{ $c->code }}</span>
                        @if ($kind !== 'creditors' && $t->neg > 0)<span class="is-owed fw-semibold" dir="ltr" title="{{ __('They owe us') }}">−{{ $amountText($t->neg) }}</span>@endif
                        @if ($kind !== 'debtors' && $t->pos > 0)<span class="is-owing fw-semibold" dir="ltr" title="{{ __('We owe them') }}">{{ $amountText($t->pos) }}</span>@endif
                        <span class="text-muted small">· {{ $t->n }}</span>
                    </div>
                @endforeach
            </div>
            <p class="list-legend small text-muted mb-2">
                <span><i class="legend-dot is-debit" aria-hidden="true"></i> <b class="is-owed" dir="ltr">−</b> {{ __('They owe us') }}</span>
                <span><i class="legend-dot is-credit" aria-hidden="true"></i> {{ __('We owe them') }}</span>
                <span class="ms-auto">{{ __(':from–:to of :total', ['from' => number_format($accounts->firstItem()), 'to' => number_format($accounts->lastItem()), 'total' => number_format($accounts->total())]) }}</span>
            </p>
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ui.account') }}</th>
                            @foreach ($columns as $c)<th scope="col" class="is-num">{{ $c->code }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts as $a)
                            @php $mine = $cells->get($a->id, collect()); @endphp
                            <tr>
                                <td>
                                    <a class="row-name" href="{{ route('admin.accounts.show', $a->id) }}">{{ $a->name }}</a>
                                    <div class="row-meta"><span dir="ltr">{{ $a->code }}</span><span class="badge-soft secondary">{{ $typeName($a->account_sub_category_id) }}</span></div>
                                </td>
                                @foreach ($columns as $c)
                                    @php $v = (float) ($mine[$c->id] ?? 0); @endphp
                                    @if (abs($v) < 0.005)
                                        <td class="is-num text-muted">—</td>
                                    @else
                                        <td class="is-num amount {{ $v < 0 ? 'is-debit' : 'is-credit' }}" dir="ltr">{{ $v < 0 ? '−' : '' }}{{ $amountText(abs($v)) }}</td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $accounts->links('partials.pagination') }}
        @endif
</div>
@endfragment
    </div>
</div>
@endsection
