@extends('layouts.admin.base')
@section('title', __('aed.title'))
@section('modern', '1')

{{-- The AED report: chosen customers and Sarafs with their dirham balance and its value in US dollars. --}}
@php
    $old = (array) old('account_ids', []);
    $totalAed = $entries->sum('aed_balance');
    $totalUsd = $entries->sum('converted_amount');
@endphp

@section('content')
<x-page-header :title="__('aed.title')" icon="money-bill-wave" :subtitle="__('Dirham balances of chosen accounts, and their value in dollars.')">
    <a class="btn btn-outline-soft" href="{{ route('admin.reports.aed.pdf') }}" target="_blank" rel="noopener" data-no-prefetch><x-icon name="file-pdf" class="me-1" /> {{ __('aed.pdf') }}</a>
    @if ($currency)<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#aedAdd"><x-icon name="plus" class="me-1" /> {{ __('aed.add_accounts') }}</button>@endif
</x-page-header>

@if (! $currency)
    <div class="alert alert-warning">{{ __('aed.missing_currency') }}</div>
@else
    <div class="row g-3 mb-3">
        <div class="col-6"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('aed.total_aed') }}</div><div class="safe-now {{ $totalAed < 0 ? 'is-owed' : '' }}" dir="ltr">{{ number_format($totalAed, 2) }}</div><div class="text-muted small">AED</div></div></div></div>
        <div class="col-6"><div class="card h-100 bl-final"><div class="card-body"><div class="text-muted small">{{ __('aed.total_usd') }}</div><div class="safe-now {{ $totalUsd < 0 ? 'is-owed' : '' }}" dir="ltr">{{ number_format($totalUsd, 2) }}</div><div class="text-muted small">USD</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($entries->isEmpty())
                <div class="empty-state">
                    <div class="es-icon"><x-icon name="money-bill-wave" /></div>
                    <h2 class="h3">{{ __('aed.empty') }}</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#aedAdd"><x-icon name="plus" class="me-1" /> {{ __('aed.add_accounts') }}</button>
                </div>
            @else
                <div class="table-responsive data-wrap">
                    <table class="table w-100 mb-0 data-table">
                        <thead><tr><th scope="col">{{ __('aed.customer') }}</th><th scope="col" class="is-num">{{ __('aed.balance') }}</th><th scope="col" class="is-num">{{ __('aed.rate') }}</th><th scope="col" class="is-num">{{ __('aed.converted') }}</th><th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th></tr></thead>
                        <tbody>
                            @foreach ($entries as $entry)
                                <tr>
                                    <td><a class="row-name" href="{{ route('admin.accounts.show', $entry->account_id) }}">{{ $entry->account->name }}</a><div class="row-meta" dir="ltr">{{ $entry->account->code }}</div></td>
                                    <td class="is-num fw-semibold {{ $entry->aed_balance < 0 ? 'is-owed' : 'is-owing' }}" dir="ltr">{{ number_format($entry->aed_balance, 2) }} <small class="text-muted">AED</small></td>
                                    <td class="is-num text-muted" dir="ltr">{{ $entry->calculated_exchange_rate === null ? '—' : rtrim(rtrim(number_format($entry->calculated_exchange_rate, 8, '.', ''), '0'), '.') }}</td>
                                    <td class="is-num fw-semibold" dir="ltr">{{ number_format($entry->converted_amount, 2) }} <small class="text-muted">USD</small></td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.reports.aed.destroy', $entry) }}" class="d-inline" data-confirm="{{ __('Remove :name from this report?', ['name' => $entry->account->name]) }}" data-confirm-ok="{{ __('aed.remove') }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-act is-red" title="{{ __('aed.remove') }}" aria-label="{{ __('aed.remove') }}">{!! ico('xmark') !!}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr class="fw-bold"><td>{{ __('aed.grand_total') }}</td><td class="is-num" dir="ltr">{{ number_format($totalAed, 2) }} AED</td><td></td><td class="is-num" dir="ltr">{{ number_format($totalUsd, 2) }} USD</td><td></td></tr></tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="aedAdd" tabindex="-1" aria-labelledby="aedAddTitle" aria-hidden="true" @if ($errors->has('account_ids') || $errors->has('account_ids.*')) data-reopen @endif>
        <div class="modal-dialog modal-dialog-scrollable">
            <form method="POST" action="{{ route('admin.reports.aed.store') }}" class="modal-content" id="aedForm">
                @csrf
                <div class="modal-header"><h2 class="modal-title h5" id="aedAddTitle">{{ __('aed.add_accounts') }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button></div>
                <div class="modal-body">
                    @if ($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                    <input type="search" class="form-control mb-2" id="aedSearch" placeholder="{{ __('aed.search_accounts') }}" aria-label="{{ __('aed.search_accounts') }}">
                    <label class="d-flex align-items-center gap-2 mb-2 small"><input type="checkbox" class="form-check-input" id="aedAll"> {{ __('aed.select_all') }}</label>
                    <div class="aed-list">
                        @forelse ($customers as $customer)
                            <label class="aed-option"><input type="checkbox" class="form-check-input" name="account_ids[]" value="{{ $customer->id }}" @checked(in_array($customer->id, $old))>
                                <span>{{ $customer->name }} <small class="text-muted" dir="ltr">{{ $customer->code }}</small></span></label>
                        @empty
                            <p class="text-muted mb-0">{{ __('aed.no_accounts') }}</p>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer"><span class="text-muted small me-auto" id="aedCount" aria-live="polite"></span><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button type="submit" class="btn btn-primary" id="aedSave" disabled>{{ __('aed.store') }}</button></div>
            </form>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('aedForm'); if (!form) return;
    var rows = Array.prototype.slice.call(form.querySelectorAll('.aed-option')), all = document.getElementById('aedAll');
    var text = @json(__('aed.selected', ['count' => ':count']));
    var sync = function () {
        var n = rows.filter(function (r) { return r.querySelector('input').checked; }).length;
        document.getElementById('aedCount').textContent = n ? text.replace(':count', n) : '';
        document.getElementById('aedSave').disabled = n === 0;
        var visible = rows.filter(function (r) { return !r.hidden; }), on = visible.filter(function (r) { return r.querySelector('input').checked; }).length;
        all.checked = visible.length > 0 && on === visible.length; all.indeterminate = on > 0 && on < visible.length;
    };
    all.addEventListener('change', function () { rows.forEach(function (r) { if (!r.hidden) r.querySelector('input').checked = all.checked; }); sync(); });
    form.addEventListener('change', sync);
    document.getElementById('aedSearch').addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        rows.forEach(function (r) { r.hidden = q && r.textContent.toLowerCase().indexOf(q) === -1; });
        sync();
    });
    sync();
});
</script>
@endpush
