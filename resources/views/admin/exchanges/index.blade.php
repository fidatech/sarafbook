@extends('layouts.admin.base')
@section('title', __('ui.exchanges'))
@section('modern', '1')

{{-- Exchanges in the fida-sarafi style: filtered on the server, with count, volume and profit of what is shown. --}}
@php
    $me = auth()->user();
    $canCreate = $me->can('create exchanges');
    $canUpdate = $me->can('update exchanges');
    $canDelete = $me->can('delete exchanges');
    $amountText = fn ($v, $d = 2) => format_amount((float) $v, $d);
    $rateText = fn ($v) => rtrim(rtrim(number_format((float) $v, 6, '.', ','), '0'), '.');
    $codes = $currencies->pluck('code', 'id');
@endphp

@section('content')
<x-page-header :title="__('ui.exchanges')" icon="coins" :subtitle="__('Every currency exchange, newest first.')">
    @can('view exchange rates')<a class="btn btn-outline-soft" href="{{ route('admin.exchange-rates.index') }}"><x-icon name="arrow-right-arrow-left" class="me-1" /> {{ __('ui.exchange_rates') }}</a>@endcan
    @if ($canCreate)<a class="btn btn-primary" href="{{ route('admin.exchanges.create') }}"><x-icon name="plus" class="me-1" /> {{ __('New exchange') }}</a>@endif
</x-page-header>

<div class="card">
    <div class="card-body">
        <form method="GET" class="list-tools" role="search" data-live="list,totals">
            <input type="search" class="form-control grow" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Account or note…') }}" aria-label="{{ __('ui.search') }}">
            <select class="form-select" name="type" aria-label="{{ __('ui.type') }}" data-plain>
                <option value="">{{ __('Buy and sell') }}</option>
                <option value="sell" @selected($filters['type'] === 'sell')>{{ __('Sold dollars') }}</option>
                <option value="purchase" @selected($filters['type'] === 'purchase')>{{ __('Bought dollars') }}</option>
            </select>
            <select class="form-select" name="mode" aria-label="{{ __('With') }}" data-plain>
                <option value="">{{ __('Cash and accounts') }}</option>
                <option value="cash" @selected($filters['mode'] === 'cash')>{{ __('Walk-in (cash)') }}</option>
                <option value="account" @selected($filters['mode'] === 'account')>{{ __('With an account') }}</option>
            </select>
            <select class="form-select" name="currency_id" aria-label="{{ __('ui.currency') }}" data-plain>
                <option value="">{{ __('All currencies') }}</option>
                @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected($filters['currency_id'] === $c->id)>{{ $c->code }}</option>@endforeach
            </select>
            <input type="date" class="form-control" name="from" value="{{ $filters['from'] }}" aria-label="{{ __('From date') }}" title="{{ __('From date') }}">
            <input type="date" class="form-control" name="to" value="{{ $filters['to'] }}" aria-label="{{ __('To date') }}" title="{{ __('To date') }}">
            <a class="btn btn-light-secondary" href="{{ route('admin.exchanges.index') }}" data-live-clear title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
        </form>

@fragment('totals')
<div data-live-part="totals">
        <div class="tx-totals mb-3">
            <span class="tx-totals-label">{{ $filtered ? __('What is shown') : __('Today') }}</span>
            <div class="tx-total"><span class="text-muted">{{ __('Exchanges') }}</span><strong>{{ number_format((int) ($summary->n ?? 0)) }}</strong></div>
            <div class="tx-total"><span class="text-muted">{{ __('Volume') }}</span><strong dir="ltr">{{ $amountText($summary->usd ?? 0, 0) }} USD</strong></div>
            @foreach ($profits as $cid => $total)
                <div class="tx-total"><span class="text-muted">{{ __('Profit') }}</span><strong dir="ltr" class="{{ $total < 0 ? 'is-owed' : 'is-owing' }}">{{ $total < 0 ? '−' : '+' }}{{ $amountText(abs($total)) }} {{ $codes[$cid] ?? '' }}</strong></div>
            @endforeach
        </div>
</div>
@endfragment

@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}">
        @if ($rows->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="coins" /></div>
                <h2 class="h3">{{ $filtered ? __('Nothing found') : __('No exchange yet') }}</h2>
                @if (! $filtered && $canCreate)<a class="btn btn-primary" href="{{ route('admin.exchanges.create') }}"><x-icon name="plus" class="me-1" /> {{ __('New exchange') }}</a>@endif
            </div>
        @else
            <p class="list-legend small text-muted mb-2"><span class="ms-auto">{{ __(':from–:to of :total', ['from' => number_format($rows->firstItem()), 'to' => number_format($rows->lastItem()), 'total' => number_format($rows->total())]) }}</span></p>
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ui.date') }}</th>
                            <th scope="col">{{ __('With') }}</th>
                            <th scope="col" class="is-num">{{ __('Gave') }}</th>
                            <th scope="col" class="is-num">{{ __('ui.rate') }}</th>
                            <th scope="col" class="is-num">{{ __('Got') }}</th>
                            <th scope="col" class="is-num">{{ __('Profit') }}</th>
                            <th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $x)
                            @php $cash = $x->mode === 'cash'; @endphp
                            <tr>
                                <td class="text-nowrap"><a href="{{ route('admin.exchanges.show', $x->id) }}">{{ \Illuminate\Support\Carbon::parse($x->date)->format('Y-m-d') }}</a><div class="row-meta">#{{ $x->id }}</div></td>
                                <td>
                                    @if ($cash || ! $x->account)
                                        <span class="row-name">{{ __('Walk-in (cash)') }}</span>
                                    @else
                                        <a class="row-name" href="{{ route('admin.accounts.show', $x->account_id) }}">{{ $x->account->name }}</a>
                                        <div class="row-meta" dir="ltr">{{ $x->account->code }}</div>
                                    @endif
                                </td>
                                <td class="is-num"><span class="amount is-debit" dir="ltr">{{ $amountText($x->from_amount) }} <small>{{ $x->fromCurrency->code ?? '' }}</small></span></td>
                                <td class="is-num text-muted" dir="ltr">{{ ($x->operation_type ?? 'multiply') === 'divide' ? '÷' : '×' }} {{ $rateText($x->rate) }}</td>
                                <td class="is-num"><span class="amount is-credit" dir="ltr">{{ $amountText($x->to_amount) }} <small>{{ $x->toCurrency->code ?? '' }}</small></span></td>
                                <td class="is-num">
                                    @if ((float) $x->profit != 0)
                                        <span dir="ltr" class="{{ $x->profit < 0 ? 'is-owed' : 'is-owing' }} fw-semibold">{{ $x->profit < 0 ? '−' : '+' }}{{ $amountText(abs($x->profit)) }} <small>{{ $x->profitCurrency->code ?? '' }}</small></span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="act-group">
                                        <a class="btn-act is-blue" href="{{ route('admin.exchanges.show', $x->id) }}" title="{{ __('View') }}" aria-label="{{ __('View') }}">{!! ico('eye') !!}</a>
                                        @if ($canUpdate)<a class="btn-act is-amber" href="{{ route('admin.exchanges.edit', $x->id) }}" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">{!! ico('pen') !!}</a>@endif
                                        @if ($canDelete)
                                            <form method="POST" action="{{ route('admin.exchanges.destroy', $x->id) }}" class="d-inline" data-confirm="{{ __('Delete this exchange?') }}" data-confirm-text="{{ __('Its lines in the accounts and the safe are deleted too, and the balances change.') }}" data-confirm-ok="{{ __('Delete') }}">
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
            {{ $rows->links('partials.pagination') }}
        @endif
</div>
@endfragment
    </div>
</div>
@endsection
