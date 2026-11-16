@php(\App\Models\AedReportEntry::refreshLiveBalances($entries))
@extends('layouts.admin.base')
@section('title', __('aed.title'))
@section('css')
<style>
.aed-report { max-width: 1440px; margin-inline: auto; }
.aed-report [hidden] { display: none !important; }
.aed-report .card { border: 1px solid #e5e7eb; box-shadow: 0 2px 8px #22304706; border-radius: 12px; }
.aed-heading { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
.aed-heading h4 { margin: 0; }
.aed-picker summary { list-style: none; cursor: pointer; display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; background: var(--bs-paper-bg, #fff); }
.aed-picker summary::-webkit-details-marker { display: none; }
.aed-picker[open] summary { border-color: #666cff; border-radius: 8px 8px 0 0; }
.aed-picker-panel { border: 1px solid #cbd5e1; border-top: 0; border-radius: 0 0 8px 8px; padding: 12px; background: var(--bs-paper-bg, #fff); }
.aed-picker-tools { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; gap: 12px; }
.aed-options { max-height: 280px; overflow-y: auto; }
.aed-account-option { display: flex; align-items: center; gap: 12px; padding: 11px 10px; margin: 0; border-radius: 6px; cursor: pointer; }
.aed-account-option:hover, .aed-account-option:has(input:checked) { background: #666cff0d; }
.aed-account-option input { flex-shrink: 0; margin: 0; }
.aed-account-option small { display: block; color: #8591a3; }
.aed-summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-block: 20px; }
.aed-summary .card-body { padding: 20px 24px; }
.aed-summary strong { font-size: 26px; font-variant-numeric: tabular-nums; margin-top: 6px; }
.aed-report .table th { background: #f6f7fb; padding: 14px 16px; white-space: nowrap; }
.aed-report .table td { padding: 12px 16px; vertical-align: middle; }
.aed-report .table td[dir=ltr] { font-variant-numeric: tabular-nums; white-space: nowrap; }
.aed-report .table tfoot td { background: #f6f7fb; }
@media(max-width: 576px) { .aed-summary { grid-template-columns: 1fr; gap: 10px; } .aed-summary strong { font-size: 22px; } }
</style>
@endsection
@section('content')
<div class="container-fluid py-3 aed-report">
    <div class="aed-heading">
        <h4>{{ __('aed.title') }}</h4>
        <div class="d-flex gap-2 align-items-center">
        <a class="btn btn-outline-primary" href="{{ route('admin.reports.aed.pdf') }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf me-2"></i>{{ __('aed.pdf') }}</a>
        @if($currency)<button type="button" id="aed-form-toggle" class="btn btn-primary" aria-controls="aed-add-form" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}"><i class="bi bi-plus-lg me-2"></i>{{ __('aed.add_accounts') }}</button>@endif
        </div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if(!$currency)
        <div class="alert alert-warning">{{ __('aed.missing_currency') }}</div>
    @else
        <div id="aed-add-form" class="card mb-3" @if(!$errors->any()) hidden @endif><div class="card-body">
            <form method="POST" action="{{ route('admin.reports.aed.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-10">
                    <label id="aed-accounts-label" class="form-label">{{ __('aed.customers') }}</label>
                    <details class="aed-picker" id="aed-picker">
                        <summary aria-labelledby="aed-accounts-label"><span>{{ __('aed.customers') }}</span><i class="bi bi-chevron-down"></i></summary>
                        <div id="aed-account-options" class="aed-picker-panel">
                            <input id="aed-search" type="search" class="form-control mb-3" placeholder="{{ __('aed.search_accounts') }}" aria-label="{{ __('aed.search_accounts') }}">
                            <div class="aed-picker-tools">
                                <label class="mb-0"><input type="checkbox" id="aed-select-all" class="form-check-input me-2">{{ __('aed.select_all') }}</label>
                                <small class="text-muted">{{ __('aed.available', ['count' => $customers->count()]) }}</small>
                            </div>
                            <div class="aed-options">
                                @foreach($customers as $customer)
                                    <label class="aed-account-option">
                                        <input type="checkbox" class="form-check-input" name="account_ids[]" value="{{ $customer->id }}" @checked(in_array($customer->id, old('account_ids', [])))>
                                        <span>
                                            {{ $customer->name }} ({{ $customer->code }})
                                            @if($customer->subCategory)
                                                <small>{{ __('accounts.types')[$customer->subCategory->name] ?? $customer->subCategory->name }}</small>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                                <div id="aed-no-results" class="text-muted p-3 text-center" @if($customers->isNotEmpty()) hidden @endif>{{ __('aed.no_accounts') }}</div>
                            </div>
                        </div>
                    </details>
                    <small class="text-muted d-block mt-2" id="aed-selection-status" aria-live="polite">{{ __('aed.available', ['count' => $customers->count()]) }}</small>
                </div>
                <div class="col-md-2"><button id="aed-store" class="btn btn-primary" disabled>{{ __('aed.store') }}</button></div>
            </form>
        </div></div>
    @endif
    <div class="aed-summary">
        <div><div class="card card-body"><span>{{ __('aed.total_aed') }}</span><strong class="aed-balance-total">{{ number_format($entries->sum('aed_balance'), 2) }} AED</strong></div></div>
        <div><div class="card card-body"><span>{{ __('aed.total_usd') }}</span><strong class="aed-converted-total">{{ number_format($entries->sum('converted_amount'), 2) }} USD</strong></div></div>
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>{{ __('aed.customer') }}</th><th>{{ __('aed.date') }}</th><th>{{ __('aed.balance') }}</th><th>{{ __('aed.rate') }}</th><th>{{ __('aed.converted') }}</th><th>{{ __('aed.actions') }}</th></tr></thead>
        <tbody>@forelse($entries as $entry)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $entry->account->name }} ({{ $entry->account->code }})</td><td>{{ now()->format('Y-m-d') }}</td><td dir="ltr">{{ number_format($entry->aed_balance, 2) }}</td><td class="aed-rate-cell" dir="ltr">{{ $entry->calculated_exchange_rate === null ? '—' : rtrim(rtrim(number_format($entry->calculated_exchange_rate, 8, '.', ''), '0'), '.') }}</td><td class="aed-converted" dir="ltr">{{ number_format($entry->converted_amount, 2) }}</td><td><form method="POST" action="{{ route('admin.reports.aed.destroy', $entry) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">{{ __('aed.remove') }}</button></form></td></tr>
        @empty<tr><td colspan="7" class="text-center py-4">{{ __('aed.empty') }}</td></tr>@endforelse</tbody>
        <tfoot><tr class="fw-bold"><td colspan="3">{{ __('aed.grand_total') }}</td><td class="aed-balance-total" dir="ltr">{{ number_format($entries->sum('aed_balance'), 2) }} AED</td><td></td><td class="aed-converted-total" dir="ltr">{{ number_format($entries->sum('converted_amount'), 2) }} USD</td><td></td></tr></tfoot>
    </table></div></div>
</div>
@endsection
@section('js')
<script>
(() => {
    const formToggle = document.getElementById('aed-form-toggle');
    const addForm = document.getElementById('aed-add-form');
    const picker = document.getElementById('aed-picker');
    if (!picker) return;
    const options = document.getElementById('aed-account-options');
    const rows = Array.from(options.querySelectorAll('.aed-account-option'));
    const selectAll = document.getElementById('aed-select-all');
    const availableText = @json(__('aed.available', ['count' => $customers->count()]));
    const selectedText = @json(__('aed.selected', ['count' => ':count']));
    formToggle.addEventListener('click', () => {
        addForm.hidden = !addForm.hidden;
        formToggle.setAttribute('aria-expanded', String(!addForm.hidden));
        picker.open = !addForm.hidden;
    });
    function updateSelection() {
        const count = rows.filter(row => row.querySelector('input').checked).length;
        document.getElementById('aed-selection-status').textContent = count ? selectedText.replace(':count', count) : availableText;
        document.getElementById('aed-store').disabled = count === 0;
        const visible = rows.filter(row => !row.hidden);
        const checked = visible.filter(row => row.querySelector('input').checked).length;
        selectAll.checked = visible.length > 0 && checked === visible.length;
        selectAll.indeterminate = checked > 0 && checked < visible.length;
        selectAll.disabled = visible.length === 0;
    }
    selectAll.addEventListener('change', () => {
        rows.filter(row => !row.hidden).forEach(row => { row.querySelector('input').checked = selectAll.checked; });
        updateSelection();
    });
    options.addEventListener('change', updateSelection);
    document.getElementById('aed-search').addEventListener('input', function () {
        const search = this.value.trim().toLocaleLowerCase();
        rows.forEach(row => { row.hidden = !row.textContent.toLocaleLowerCase().includes(search); });
        document.getElementById('aed-no-results').hidden = rows.some(row => !row.hidden);
        updateSelection();
    });
    picker.addEventListener('keydown', event => {
        if (event.key === 'Escape') { picker.open = false; picker.querySelector('summary').focus(); }
    });
    updateSelection();
})();
</script>
@endsection
