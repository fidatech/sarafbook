@extends('layouts.admin.base')
@section('title', __('ui.safe'))
@section('modern', '1')

{{--
    The safe in the fida-sarafi style: for the chosen period, what was in it at the start, what
    came in and went out, and what is in it at the end, by currency; then its ledger, newest
    first, with the other side of each line and the balance after it.
--}}
@php
    $amountText = fn ($v) => format_amount((float) $v, 2);
    $ranges = ['today' => __('Today'), 'yesterday' => __('Yesterday'), 'this_week' => __('This week'), 'this_month' => __('This month'), 'last_month' => __('Last month'), 'this_year' => __('This year'), 'all' => __('Everything')];
    $printParams = $range === 'custom'
        ? ['range_type' => 'custom', 'date_range' => optional($start)->toDateString().' - '.optional($end)->toDateString()]
        : ['range_type' => $range];
    $periodText = $start ? ($start->toDateString() === optional($end)->toDateString() ? $start->toDateString() : $start->toDateString().' — '.optional($end)->toDateString()) : __('Everything');
    $filtered = $range !== 'today' || $filters['type'] !== '' || $filters['currency_id'];
@endphp

@section('content')
<x-page-header :title="__('ui.safe')" icon="vault" :subtitle="$cashAccount ? $cashAccount->name.' · '.$cashAccount->code : null">
    <a class="btn btn-outline-soft" href="{{ route('admin.safe.ledger.print', $printParams) }}" target="_blank" rel="noopener" data-no-prefetch id="safePrint"><x-icon name="print" class="me-1" /> {{ __('Print') }}</a>
    @can('view transactions')<a class="btn btn-primary" href="{{ route('admin.transactions.index') }}#txForm"><x-icon name="plus" class="me-1" /> {{ __('New transaction') }}</a>@endcan
</x-page-header>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="list-tools mb-0" data-live="cards,list" role="search">
            <div class="safe-ranges" role="radiogroup" aria-label="{{ __('Period') }}">
                @foreach ($ranges as $key => $label)
                    <label class="safe-range"><input type="radio" name="range" value="{{ $key }}" @checked($range === $key) @if ($key === 'today') data-default="today" @endif><span>{{ $label }}</span></label>
                @endforeach
            </div>
            <input type="date" class="form-control" name="from" value="{{ $filters['from'] }}" aria-label="{{ __('From date') }}" title="{{ __('From date') }}">
            <input type="date" class="form-control" name="to" value="{{ $filters['to'] }}" aria-label="{{ __('To date') }}" title="{{ __('To date') }}">
        </form>
    </div>
</div>

@fragment('cards')
<div data-live-part="cards">
    <p class="text-muted small mb-2">{{ $periodText }}</p>
    @if (! $cards)
        <div class="card mb-3"><div class="card-body text-muted">{{ __('The safe is empty and nothing moved in this period.') }}</div></div>
    @else
        <div class="row g-3 mb-3">
            @foreach ($cards as $card)
                <div class="col-12 col-md-6 col-xl-4">
                    <section class="card h-100 safe-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="chip-code">{{ $card['code'] }}</span>
                                <span class="text-muted small ms-auto">{{ __(':count lines', ['count' => $card['n']]) }}</span>
                            </div>
                            <div class="safe-now {{ $card['closing'] < 0 ? 'is-owed' : '' }}" dir="ltr">{{ $card['closing'] < 0 ? '−' : '' }}{{ $amountText(abs($card['closing'])) }}</div>
                            <div class="text-muted small mb-3">{{ __('In the safe at the end') }}</div>
                            <dl class="safe-flow">
                                <div><dt>{{ __('At the start') }}</dt><dd dir="ltr">{{ $card['opening'] < 0 ? '−' : '' }}{{ $amountText(abs($card['opening'])) }}</dd></div>
                                <div><dt>{{ __('Came in') }}</dt><dd dir="ltr" class="is-owing">+{{ $amountText($card['in']) }}</dd></div>
                                <div><dt>{{ __('Went out') }}</dt><dd dir="ltr" class="is-owed">−{{ $amountText($card['out']) }}</dd></div>
                            </dl>
                        </div>
                    </section>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endfragment

<section class="card" aria-labelledby="safeLedgerTitle">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <h2 class="h5 card-title mb-0 me-auto" id="safeLedgerTitle">{{ __('Safe ledger') }}</h2>
        <select class="form-select form-select-sm w-auto" name="currency_id" form="safeFilters2" aria-label="{{ __('ui.currency') }}" data-plain data-safe-extra>
            <option value="">{{ __('All currencies') }}</option>
            @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected($filters['currency_id'] === $c->id)>{{ $c->code }}</option>@endforeach
        </select>
        <select class="form-select form-select-sm w-auto" name="type" aria-label="{{ __('ui.type') }}" data-plain data-safe-extra>
            <option value="">{{ __('In and out') }}</option>
            <option value="credit" @selected($filters['type'] === 'credit')>{{ __('Came in') }}</option>
            <option value="debit" @selected($filters['type'] === 'debit')>{{ __('Went out') }}</option>
        </select>
    </div>
    <div class="card-body">
@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}">
        @if ($rows->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="vault" /></div>
                <h2 class="h3">{{ __('Nothing moved in this period') }}</h2>
            </div>
        @else
            <p class="list-legend small text-muted mb-2"><span class="ms-auto">{{ __(':from–:to of :total', ['from' => number_format($rows->firstItem()), 'to' => number_format($rows->lastItem()), 'total' => number_format($rows->total())]) }}</span></p>
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ui.date') }}</th>
                            <th scope="col">{{ __('From or to') }}</th>
                            <th scope="col">{{ __('ui.note') }}</th>
                            <th scope="col" class="is-num">{{ __('ui.amount') }}</th>
                            @if ($after)<th scope="col" class="is-num">{{ __('Balance after') }}</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php $in = $row->transaction_type === 'credit'; $who = $row->linked_id ? ($counter[$row->linked_id]->account ?? null) : null; @endphp
                            <tr>
                                <td class="text-nowrap">{{ optional($row->created_at)->format('Y-m-d') }}<div class="row-meta">{{ optional($row->created_at)->format('H:i') }}</div></td>
                                <td>
                                    @if ($who)
                                        <a class="row-name" href="{{ route('admin.accounts.show', $who->id) }}">{{ $who->name }}</a>
                                        <div class="row-meta" dir="ltr">{{ $who->code }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="tx-note"><span class="d-block text-break">{{ \Illuminate\Support\Str::limit((string) $row->note, 140) ?: '—' }}</span></td>
                                <td class="is-num"><span class="amount {{ $in ? 'is-credit' : 'is-debit' }}" dir="ltr">{{ $in ? '+' : '−' }}{{ $amountText($row->amount) }} <small>{{ $row->currency->code ?? '' }}</small></span></td>
                                @if ($after)
                                    @php $b = $after[$row->id] ?? 0; @endphp
                                    <td class="is-num text-muted" dir="ltr">{{ $b < 0 ? '−' : '' }}{{ $amountText(abs($b)) }} <small>{{ $row->currency->code ?? '' }}</small></td>
                                @endif
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
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form[data-live]');
    if (!form) return;
    // the two lists above the ledger belong to the same filters
    document.querySelectorAll('[data-safe-extra]').forEach(function (sel) {
        sel.removeAttribute('form');
        var hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = sel.name; hidden.value = sel.value;
        form.appendChild(hidden);
        sel.removeAttribute('name');
        sel.addEventListener('change', function () { hidden.value = sel.value; form.requestSubmit(); });
    });
    // choosing dates replaces the quick choices, and a quick choice clears the dates
    form.addEventListener('change', function (e) {
        if (e.target.name === 'range') { form.elements.from.value = ''; form.elements.to.value = ''; }
        if (e.target.name === 'from' || e.target.name === 'to') form.querySelectorAll('input[name=range]').forEach(function (r) { r.checked = false; });
    }, true);
});
document.addEventListener('erp:live', function () {
    var a = document.getElementById('safePrint'); if (!a) return;
    var q = new URLSearchParams(location.search), out = new URL(a.href);
    out.search = '';
    if (q.get('from') || q.get('to')) { out.searchParams.set('range_type', 'custom'); out.searchParams.set('date_range', (q.get('from') || '2000-01-01') + ' - ' + (q.get('to') || new Date().toISOString().slice(0, 10))); }
    else out.searchParams.set('range_type', q.get('range') || 'today');
    a.href = out.toString();
});
</script>
@endpush
