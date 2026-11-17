@extends('layouts.admin.base')
@section('title', __('Transactions report'))
@section('modern', '1')

{{-- The transactions report: visible lines with credit and debit, totals by currency, and the Excel file of the same filters. --}}
@php $amountText = fn ($v) => format_amount((float) $v, 2); @endphp

@section('content')
<x-page-header :title="__('Transactions report')" icon="file-lines" :subtitle="__('Every transaction of a period, with its totals.')">
    <a class="btn btn-outline-soft" href="{{ $excel }}" data-no-prefetch id="trExcel"><x-icon name="file-excel" class="me-1" /> {{ __('Excel') }}</a>
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
            <select class="form-select" name="transaction_type" aria-label="{{ __('ui.type') }}" data-plain>
                <option value="">{{ __('In and out') }}</option>
                <option value="credit" @selected($filters['transaction_type'] === 'credit')>{{ __('ui.credit') }} (+)</option>
                <option value="debit" @selected($filters['transaction_type'] === 'debit')>{{ __('ui.debit') }} (−)</option>
            </select>
            <input type="date" class="form-control" name="from_date" value="{{ $filters['from_date'] }}" aria-label="{{ __('From date') }}" title="{{ __('From date') }}">
            <input type="date" class="form-control" name="to_date" value="{{ $filters['to_date'] }}" aria-label="{{ __('To date') }}" title="{{ __('To date') }}">
            <a class="btn btn-light-secondary" href="{{ url()->current() }}" data-live-clear title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
        </form>

@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}" data-excel="{{ $excel }}">
        @if ($totals->isNotEmpty())
            <div class="table-responsive mb-3">
                <table class="table w-100 mb-0 keep-table acc-totals">
                    <thead><tr><th scope="col">{{ __('ui.currency') }}</th><th scope="col" class="text-end">{{ __('ui.credit') }}</th><th scope="col" class="text-end">{{ __('ui.debit') }}</th><th scope="col" class="text-end">{{ __('Net') }}</th></tr></thead>
                    <tbody>
                        @foreach ($totals as $t)
                            @php $net = (float) $t->credit - (float) $t->debit; @endphp
                            <tr><th scope="row"><span class="chip-code">{{ $t->code }}</span></th><td class="text-end is-owing" dir="ltr">{{ $amountText($t->credit) }}</td><td class="text-end is-owed" dir="ltr">{{ $amountText($t->debit) }}</td><td class="text-end fw-semibold {{ $net < 0 ? 'is-owed' : 'is-owing' }}" dir="ltr">{{ $net < 0 ? '−' : '' }}{{ $amountText(abs($net)) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if ($rows->isEmpty())
            <div class="empty-state"><div class="es-icon"><x-icon name="file-lines" /></div><h2 class="h3">{{ __('Nothing found') }}</h2></div>
        @else
            <p class="list-legend small text-muted mb-2"><span class="ms-auto">{{ __(':from–:to of :total', ['from' => number_format($rows->firstItem()), 'to' => number_format($rows->lastItem()), 'total' => number_format($rows->total())]) }}</span></p>
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead><tr><th scope="col">{{ __('ui.date') }}</th><th scope="col">{{ __('ui.account') }}</th><th scope="col">{{ __('ui.note') }}</th><th scope="col" class="is-num">{{ __('ui.credit') }}</th><th scope="col" class="is-num">{{ __('ui.debit') }}</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td class="text-nowrap">{{ optional($r->created_at)->format('Y-m-d') }}<div class="row-meta">{{ optional($r->created_at)->format('H:i') }}</div></td>
                                <td>@if ($r->account)<a class="row-name" href="{{ route('admin.accounts.show', $r->account_id) }}">{{ $r->account->name }}</a><div class="row-meta" dir="ltr">{{ $r->account->code }}</div>@else — @endif</td>
                                <td class="tx-note"><span class="d-block text-break">{{ \Illuminate\Support\Str::limit((string) $r->note, 120) ?: '—' }}</span></td>
                                <td class="is-num amount is-credit" dir="ltr">@if ($r->transaction_type === 'credit'){{ $amountText($r->amount) }} <small>{{ $r->currency->code ?? '' }}</small>@endif</td>
                                <td class="is-num amount is-debit" dir="ltr">@if ($r->transaction_type === 'debit'){{ $amountText($r->amount) }} <small>{{ $r->currency->code ?? '' }}</small>@endif</td>
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
<script>
document.addEventListener('erp:live', function () {
    var part = document.querySelector('[data-live-part="list"]'), a = document.getElementById('trExcel');
    if (part && a && part.dataset.excel) a.href = part.dataset.excel;
});
</script>
@endpush
