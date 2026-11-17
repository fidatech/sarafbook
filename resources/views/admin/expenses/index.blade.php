@extends('layouts.admin.base')
@section('title', __('ui.expenses'))
@section('modern', '1')

{{--
    Expenses in the fida-sarafi style: the list, the totals by category of what is shown, and one
    pop-up to add or change an expense. An expense takes the money out of the account it is paid
    from (the safe or an expense account) in its own currency; "paid in another currency" only
    records how it was paid.
--}}
@php
    $me = auth()->user();
    $canCreate = $me->can('create expenses');
    $canUpdate = $me->can('update expenses');
    $canDelete = $me->can('delete expenses');
    $types = ['company' => __('Company'), 'personal' => __('Personal'), 'project' => __('Project')];
    $amountText = fn ($v) => format_amount((float) $v, 2);
    $currencyOptions = $currencies->where('is_active', true)->pluck('code', 'id')->all();
    $defaultCurrency = $currencies->firstWhere('is_default', true)?->id ?? $currencies->first()?->id;
@endphp

@section('content')
<x-page-header :title="__('ui.expenses')" icon="receipt" :subtitle="__('What the business spends, by category.')">
    @can('view expense categories')<a class="btn btn-outline-soft" href="{{ route('admin.expense-categories.index') }}"><x-icon name="tags" class="me-1" /> {{ __('ui.expense_categories') }}</a>@endcan
    @if ($canCreate)
        <button class="btn btn-primary" type="button" data-modal-form="#expenseForm"><x-icon name="plus" class="me-1" /> {{ __('Add expense') }}</button>
    @endif
</x-page-header>

<div class="card">
    <div class="card-body">
        <form method="GET" class="list-tools" role="search" data-live="list,totals">
            <input type="search" class="form-control grow" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Search the descriptions…') }}" aria-label="{{ __('ui.search') }}">
            <select class="form-select" name="expense_category_id" aria-label="{{ __('ui.category') }}" data-plain>
                <option value="">{{ __('All categories') }}</option>
                @foreach ($expenseCategories as $cat)<option value="{{ $cat->id }}" @selected($filters['expense_category_id'] === $cat->id)>{{ $cat->name }}</option>@endforeach
            </select>
            <select class="form-select" name="expense_type" aria-label="{{ __('ui.type') }}" data-plain>
                <option value="">{{ __('Every kind') }}</option>
                @foreach ($types as $k => $t)<option value="{{ $k }}" @selected($filters['expense_type'] === $k)>{{ $t }}</option>@endforeach
            </select>
            <select class="form-select" name="currency_id" aria-label="{{ __('ui.currency') }}" data-plain>
                <option value="">{{ __('All currencies') }}</option>
                @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected($filters['currency_id'] === $c->id)>{{ $c->code }}</option>@endforeach
            </select>
            <input type="date" class="form-control" name="from" value="{{ $filters['from'] }}" aria-label="{{ __('From date') }}" title="{{ __('From date') }}">
            <input type="date" class="form-control" name="to" value="{{ $filters['to'] }}" aria-label="{{ __('To date') }}" title="{{ __('To date') }}">
            <a class="btn btn-light-secondary" href="{{ route('admin.expenses.index') }}" data-live-clear title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
        </form>

@fragment('totals')
<div data-live-part="totals">
        @if ($byCategory->isNotEmpty())
            @php $sum = $byCategory->groupBy('code')->map(fn ($g) => $g->sum('total')); @endphp
            <div class="tx-totals mb-3">
                <span class="tx-totals-label">{{ $filtered ? __('What is shown') : __('All expenses') }}</span>
                @foreach ($sum as $code => $total)
                    <div class="tx-total"><span class="chip-code">{{ $code }}</span><strong dir="ltr" class="is-owed">{{ $amountText($total) }}</strong></div>
                @endforeach
            </div>
            <details class="exp-cats mb-3">
                <summary>{{ __('By category') }}</summary>
                <div class="exp-cat-list">
                    @foreach ($byCategory->take(24) as $line)
                        <div class="exp-cat"><span>{{ $line->category }}</span><span dir="ltr" class="fw-semibold">{{ $amountText($line->total) }} <small class="text-muted">{{ $line->code }}</small></span></div>
                    @endforeach
                </div>
            </details>
        @endif
</div>
@endfragment

@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}">
        @if ($rows->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="receipt" /></div>
                <h2 class="h3">{{ $filtered ? __('Nothing found') : __('No expense yet') }}</h2>
                @if (! $filtered && $canCreate)<button class="btn btn-primary" type="button" data-modal-form="#expenseForm"><x-icon name="plus" class="me-1" /> {{ __('Add expense') }}</button>@endif
            </div>
        @else
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ui.date') }}</th>
                            <th scope="col">{{ __('ui.description') }}</th>
                            <th scope="col">{{ __('ui.category') }}</th>
                            <th scope="col">{{ __('Paid from') }}</th>
                            <th scope="col" class="is-num">{{ __('ui.amount') }}</th>
                            <th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $e)
                            @php
                                $date = $e->expense_date instanceof \DateTimeInterface ? $e->expense_date->format('Y-m-d') : substr((string) $e->expense_date, 0, 10);
                                $fill = json_encode([
                                    'account_id' => $e->paid_from_account_id, 'expense_type' => $e->expense_type, 'expense_category_id' => $e->expense_category_id,
                                    'expense_date' => $date, 'currency_id' => $e->currency_id,
                                    'amount' => rtrim(rtrim(number_format((float) $e->amount, 2, '.', ''), '0'), '.'), 'payment_currency_id' => $e->payment_currency_id,
                                    'payment_amount' => $e->payment_amount !== null ? (float) $e->payment_amount : '', 'rate' => $e->rate !== null ? (float) $e->rate : '',
                                    'operation_type' => $e->operation_type ?: 'multiply', 'description' => $e->description,
                                ], JSON_UNESCAPED_UNICODE);
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ $date }}</td>
                                <td class="tx-note"><span class="d-block text-break">{{ $e->description ?: '—' }}</span><span class="badge-soft secondary">{{ $types[$e->expense_type] ?? $e->expense_type }}</span></td>
                                <td>{{ $e->expenseCategory->name ?? '—' }}</td>
                                <td>{{ $e->paidFromAccount->name ?? '—' }}</td>
                                <td class="is-num">
                                    <span class="amount is-debit" dir="ltr">{{ $amountText($e->amount) }} <small>{{ $e->currency->code ?? '' }}</small></span>
                                    @if ($e->payment_currency_id && $e->payment_currency_id !== $e->currency_id && $e->payment_amount)
                                        <div class="row-meta justify-content-end" dir="ltr">{{ __('paid') }} {{ $amountText($e->payment_amount) }} {{ $e->paymentCurrency->code ?? '' }}</div>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="act-group">
                                        @if ($e->attachment_path)
                                            <a class="btn-act is-teal" href="{{ asset('assets/attachments/'.$e->attachment_path) }}" target="_blank" rel="noopener" title="{{ __('Attachment') }}" aria-label="{{ __('Attachment') }}">{!! ico('paperclip') !!}</a>
                                        @endif
                                        @if ($canUpdate)
                                            <button type="button" class="btn-act is-amber" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}" data-modal-form="#expenseForm" data-fill="{{ $fill }}" data-action="{{ route('admin.expenses.update', $e->id) }}" data-method="PUT">{!! ico('pen') !!}</button>
                                        @endif
                                        @if ($canDelete)
                                            <form method="POST" action="{{ route('admin.expenses.destroy', $e->id) }}" class="d-inline" data-confirm="{{ __('Delete this expense?') }}" data-confirm-text="{{ __('Its money goes back to the account it was paid from.') }}" data-confirm-ok="{{ __('Delete') }}">
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

@if ($canCreate || $canUpdate)
<x-form-modal id="expenseForm" :title="__('Add expense')" :title-edit="__('Edit expense')" :action="route('admin.expenses.store')" size="lg" :files="true">
    <div class="row g-3">
        <x-field col="col-12 col-md-6" modal="expenseForm" name="account_id" type="select" :label="__('Paid from')" :options="$cashAccounts->mapWithKeys(fn ($a) => [$a->id => $a->name.' · '.$a->code])->all()" :value="$cashAccounts->first()?->id" required />
        <x-field col="col-6 col-md-3" modal="expenseForm" name="expense_date" type="date" :label="__('ui.date')" :value="now()->toDateString()" required />
        <x-field col="col-6 col-md-3" modal="expenseForm" name="expense_type" type="select" :label="__('ui.type')" :options="$types" value="company" required />
        <x-field col="col-12 col-md-6" modal="expenseForm" name="expense_category_id" type="select" :label="__('ui.category')" :options="$expenseCategories->pluck('name', 'id')->all()" :empty="__('No category')" />
        <x-field col="col-5 col-md-2" modal="expenseForm" name="currency_id" type="select" :label="__('ui.currency')" :options="$currencyOptions" :value="$defaultCurrency" required data-plain />
        <x-field col="col-7 col-md-4" modal="expenseForm" name="amount" :label="__('ui.amount')" inputmode="decimal" dir="ltr" autocomplete="off" required />
        <div class="col-12">
            <details class="exp-other" id="expOther">
                <summary>{{ __('Paid in another currency') }}</summary>
                <div class="row g-3 mt-1">
                    <x-field col="col-6 col-md-3" modal="expenseForm" name="payment_currency_id" type="select" :label="__('Paid in')" :options="$currencyOptions" :empty="__('Same')" data-plain />
                    <x-field col="col-6 col-md-3" modal="expenseForm" name="operation_type" type="select" :label="__('Rate works by')" :options="['multiply' => __('Multiply'), 'divide' => __('Divide')]" value="multiply" data-plain />
                    <x-field col="col-6 col-md-3" modal="expenseForm" name="rate" :label="__('ui.rate')" inputmode="decimal" dir="ltr" autocomplete="off" />
                    <x-field col="col-6 col-md-3" modal="expenseForm" name="payment_amount" :label="__('Amount paid')" inputmode="decimal" dir="ltr" autocomplete="off" />
                </div>
            </details>
        </div>
        <x-field col="col-12" modal="expenseForm" name="description" type="textarea" :label="__('ui.description')" />
        <x-field col="col-12" modal="expenseForm" name="attachment" type="file" :label="__('Attachment')" accept="image/jpeg,image/png,application/pdf" :hint="__('A picture or PDF of the bill, up to 4 MB. A new file replaces the old one.')" />
    </div>
</x-form-modal>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('#expenseForm form');
    if (!form) return;
    var num = function (el) { var v = ERP.latinDigits ? ERP.latinDigits(el.value) : el.value; return parseFloat(String(v).replace(/,/g, '')) || 0; };
    // the amount paid follows the amount and the rate
    var work = function () {
        var amount = num(form.elements.amount), rate = num(form.elements.rate);
        if (!amount || !rate || !form.elements.payment_currency_id.value) return;
        var paid = form.elements.operation_type.value === 'divide' ? amount / rate : amount * rate;
        form.elements.payment_amount.value = Math.round(paid * 100) / 100;
    };
    ['amount', 'rate', 'operation_type', 'payment_currency_id'].forEach(function (n) { form.elements[n].addEventListener(n === 'amount' || n === 'rate' ? 'input' : 'change', work); });
    form.addEventListener('erp:form-open', function (e) {
        var d = e.detail.data || {};
        document.getElementById('expOther').open = !!(d.payment_currency_id && d.payment_currency_id !== d.currency_id);
        // the account it was paid from is fixed once saved
        form.elements.account_id.disabled = !!e.detail.editing;
    });
});
</script>
@endpush
