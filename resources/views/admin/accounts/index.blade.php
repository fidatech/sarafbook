@extends('layouts.admin.base')
@section('title', __('ui.accounts'))
@section('modern', '1')

{{--
    Accounts, in the design of Fida Sarafi: page header, totals by currency, one card with the
    filters and the list, and one pop-up for adding or changing an account. Everything is drawn
    on the server; the filters are an ordinary GET form, so a link keeps them.
--}}
@php
    $user = auth()->user();
    $canCreate = $user->can('create customers');
    $canUpdate = $user->can('update customers');
    $canDelete = $user->can('delete customers');
    $canMessage = $user->can('send-whatsapp customers');
    $typeName = fn ($sub) => __('accounts.types')[$sub?->name ?? ''] ?? ($sub?->name ?? '—');
    $typePlural = fn ($sub) => __('accounts.types_plural')[$sub->name] ?? $sub->name;
    $filtered = $filters['q'] !== '' || $filters['type'] || $filters['currency'] || $filters['balance'];
    $shownType = $filters['type'] ? $subCategories->firstWhere('id', $filters['type']) : null;
    $modalOld = old('_modal');
    // icons inside the rows are plain markup: a Blade component per icon costs time on long pages
    $sprite = asset_v('icons.svg');
    $ico = fn (string $name, string $class = '') => '<i class="fi'.($class ? ' '.$class : '').'"><svg aria-hidden="true" focusable="false"><use href="'.$sprite.'#s-'.$name.'"/></svg></i>';
@endphp

@section('content')
<x-page-header :title="__('ui.accounts')" icon="address-book" :subtitle="__('Every balance, in every currency.')">
    <button class="btn btn-outline-soft acc-toggle" type="button" id="summaryToggle" aria-controls="accSummary" aria-expanded="true">
        <x-icon name="chart-simple" class="me-1" /> <span data-when="open">{{ __('Hide totals') }}</span><span data-when="closed">{{ __('Show totals') }}</span>
    </button>
    @if ($canCreate)
        <button class="btn btn-outline-soft" type="button" data-bs-toggle="modal" data-bs-target="#importModal"><x-icon name="file-import" class="me-1" /> {{ __('ui.bulk_import') }}</button>
        <button class="btn btn-primary" type="button" data-account-new><x-icon name="plus" class="me-1" /> {{ __('ui.create_account') }}</button>
    @endif
</x-page-header>

{{-- totals of the accounts the filters show; the toggle above hides them, and the choice is remembered --}}
<section class="card mb-3 acc-summary" id="accSummary" aria-labelledby="accSummaryTitle">
@fragment('summary')
<div id="accSummaryBody">
    <div class="card-header">
        <div>
            <h2 class="h5 card-title mb-0" id="accSummaryTitle">{{ __('Balances by currency') }}</h2>
            <span class="text-muted small">{{ $shownType ? $typePlural($shownType) : __('Customers and Sarafs, without the cash accounts') }}</span>
        </div>
        <div class="acc-counts">
            <span><strong dir="ltr">{{ number_format($accountCount) }}</strong> {{ $shownType ? $typePlural($shownType) : __('Customers and Sarafs') }}</span>
            @if ($cashCount)
                <span><strong dir="ltr">{{ number_format($cashCount) }}</strong> {{ __('Cash accounts') }}</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if ($totals->isEmpty())
            <p class="text-muted mb-0">{{ __('No balance yet.') }}</p>
        @else
            <div class="table-responsive d-none d-md-block">
                <table class="table w-100 mb-0 keep-table acc-totals">
                    <thead><tr><th scope="col">{{ __('ui.currency') }}</th><th scope="col" class="text-end">{{ __('They owe us') }}</th><th scope="col" class="text-end">{{ __('We owe them') }}</th><th scope="col" class="text-end">{{ __('Net') }}</th></tr></thead>
                    <tbody>
                        @foreach ($totals as $row)
                            <tr>
                                <th scope="row"><span class="acc-code">{{ $row->code }}</span></th>
                                <td class="text-end text-nowrap" dir="ltr"><span class="acc-debit">{{ format_amount($row->owed_to_us, 2) }}</span> <small class="text-muted" title="{{ __('Accounts') }}">· {{ (int) $row->debtor_count }}</small></td>
                                <td class="text-end text-nowrap" dir="ltr"><span class="acc-credit">{{ format_amount($row->we_owe, 2) }}</span> <small class="text-muted" title="{{ __('Accounts') }}">· {{ (int) $row->creditor_count }}</small></td>
                                <td class="text-end text-nowrap fw-semibold" dir="ltr"><span class="{{ $row->net < 0 ? 'acc-debit' : 'acc-credit' }}">{{ $row->net < 0 ? '−' : '' }}{{ format_amount(abs($row->net), 2) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <ul class="acc-totals-list d-md-none">
                @foreach ($totals as $row)
                    <li>
                        <span class="acc-code">{{ $row->code }}</span>
                        <div>
                            <strong dir="ltr" class="{{ $row->net < 0 ? 'acc-debit' : 'acc-credit' }}">{{ $row->net < 0 ? '−' : '' }}{{ format_amount(abs($row->net), 2) }}</strong>
                            <small>{{ __('They owe us') }} <span dir="ltr">{{ format_amount($row->owed_to_us, 2) }}</span> · {{ __('We owe them') }} <span dir="ltr">{{ format_amount($row->we_owe, 2) }}</span></small>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endfragment
</section>
<script>try{if(localStorage.getItem('sb.accounts.totals')==='0'){document.getElementById('accSummary').hidden=true;document.getElementById('summaryToggle').setAttribute('aria-expanded','false')}}catch(e){}</script>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-2" role="search" id="accountFilters">
            <div class="col-12 col-md">
                <input type="search" class="form-control" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Name, code or phone…') }}" aria-label="{{ __('ui.search') }}">
            </div>
            <div class="col-6 col-md-auto">
                <select class="form-select" name="type" aria-label="{{ __('ui.type') }}" data-autosubmit>
                    <option value="">{{ __('All types') }}</option>
                    @foreach ($subCategories as $sub)
                        <option value="{{ $sub->id }}" @selected($filters['type'] === $sub->id)>{{ $typePlural($sub) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-auto">
                <select class="form-select" name="currency" aria-label="{{ __('ui.currency') }}" data-autosubmit>
                    <option value="">{{ __('All currencies') }}</option>
                    @foreach ($currencies->where('is_active', true) as $currency)
                        <option value="{{ $currency->id }}" @selected($filters['currency'] === $currency->id)>{{ $currency->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-8 col-md-auto">
                <select class="form-select" name="balance" aria-label="{{ __('ui.balance') }}" data-autosubmit>
                    <option value="">{{ __('Newest first') }}</option>
                    <option value="debtors" @selected($filters['balance'] === 'debtors')>{{ __('They owe us, largest first') }}</option>
                    <option value="creditors" @selected($filters['balance'] === 'creditors')>{{ __('We owe them, largest first') }}</option>
                    <option value="mixed" @selected($filters['balance'] === 'mixed')>{{ __('Both sides') }}</option>
                </select>
            </div>
            <div class="col-4 col-md-auto">
                <select class="form-select" name="per_page" aria-label="{{ __('Rows on a page') }}" data-autosubmit>
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-light-secondary" type="submit" title="{{ __('ui.search') }}" aria-label="{{ __('ui.search') }}"><x-icon name="magnifying-glass" /></button>
                <a class="btn btn-light-secondary" id="accClear" href="{{ route('admin.accounts.index') }}" title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
            </div>
        </form>

@fragment('results')
<div id="accResults" aria-live="polite" data-filtered="{{ $filtered ? 1 : 0 }}">
        <p class="acc-legend small text-muted mb-3">
            <span><i class="acc-dot is-debit" aria-hidden="true"></i> <b class="acc-debit" dir="ltr">−</b> {{ __('They owe us') }}</span>
            <span><i class="acc-dot is-credit" aria-hidden="true"></i> {{ __('We owe them') }}</span>
            @if ($accounts->total())
                <span class="ms-auto">{{ __(':from–:to of :total', ['from' => number_format($accounts->firstItem()), 'to' => number_format($accounts->lastItem()), 'total' => number_format($accounts->total())]) }}</span>
            @endif
        </p>

        @if ($accounts->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="address-book" /></div>
                <h2 class="h3">{{ $filtered ? __('Nothing found') : __('No account yet') }}</h2>
                @if ($filtered)
                    <p>{{ __('No account matches these filters.') }}</p>
                    <a class="btn btn-outline-soft" href="{{ route('admin.accounts.index') }}"><x-icon name="filter-circle-xmark" class="me-1" /> {{ __('Clear the filters') }}</a>
                @elseif ($canCreate)
                    <p>{{ __('Add the first customer or Saraf to start recording.') }}</p>
                    <button class="btn btn-primary" type="button" data-account-new><x-icon name="plus" class="me-1" /> {{ __('ui.create_account') }}</button>
                @endif
            </div>
        @else
            <div class="table-responsive acc-wrap">
            <table class="table w-100 mb-0 acc-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('ui.account_name') }}</th>
                        <th scope="col">{{ __('ui.contact') }}</th>
                        @foreach ($columns as $currency)
                            <th scope="col" class="text-end acc-cur-head">{{ $currency->code }}</th>
                        @endforeach
                        <th scope="col" class="text-end no-print"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @php [$tView, $tEdit, $tMore] = [__('View'), __('Edit'), __('More')]; @endphp
                    @foreach ($accounts as $account)
                        @php
                            $sub = $account->subCategory;
                            $isCash = $cashCategoryId && (int) $sub?->account_category_id === (int) $cashCategoryId;
                            $mine = $balances->get($account->id, collect());
                            $url = route('admin.accounts.show', $account->id);
                            $hasPhone = strlen(preg_replace('/\D/', '', (string) $account->contact)) >= 6;
                            $tone = ['Customer' => 'primary', 'Saraf' => 'info', 'Safe' => 'success', 'PnL' => 'warning', 'EXP' => 'secondary'][$sub?->name] ?? 'secondary';
                            $editJson = json_encode([
                                'id' => $account->id, 'name' => $account->name, 'code' => $account->code,
                                'type' => $account->account_sub_category_id, 'contact' => $account->contact,
                                'address' => $account->address, 'note' => $account->company,
                                'reports' => (bool) $account->include_in_reports,
                            ], JSON_UNESCAPED_UNICODE);
                        @endphp
                        @if ($groupCash && $isCash && ! ($shownCashHeading ?? false))
                            @php $shownCashHeading = true; @endphp
                            <tr class="acc-group"><th colspan="{{ 3 + $columns->count() }}" scope="rowgroup">{!! $ico('vault', 'me-1') !!} {{ __('Cash accounts') }} <small>{{ __('safe, profit and loss, expenses') }}</small></th></tr>
                        @endif
                        <tr @class(['is-cash' => $isCash])>
                            <td>
                                <div class="acc-who">
                                    <a href="{{ $url }}" class="avatar avatar-sm avatar-soft-{{ $tone }}" aria-hidden="true" tabindex="-1">{{ initial($account->name) }}</a>
                                    <div class="min-w-0">
                                        <a class="acc-name" href="{{ $url }}">{{ $account->name }}</a>
                                        <div class="acc-meta">
                                            <span dir="ltr">{{ $account->code }}</span>
                                            <span class="badge-soft {{ $tone }}">{{ $typeName($sub) }}</span>
                                            @if ($account->user)
                                                <span class="acc-flag" title="{{ __('Has a login for the customer') }}">{!! $ico('user-check') !!}</span>
                                            @endif
                                            @unless ($account->include_in_reports)
                                                <span class="acc-flag is-muted" title="{{ __('Not in the reports') }}">{!! $ico('eye-slash') !!}</span>
                                            @endunless
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="acc-contact">
                                @if ($hasPhone)
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $account->contact) }}" dir="ltr" class="text-nowrap">{{ $account->contact }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                                @if (filled($account->address))
                                    <small class="acc-address" title="{{ $account->address }}">{{ $account->address }}</small>
                                @endif
                            </td>
                            @foreach ($columns as $currency)
                                @php $value = (float) ($mine[$currency->id] ?? 0); @endphp
                                @if (abs($value) < 0.005)
                                    <td class="text-end acc-amount is-zero"><span aria-label="{{ __('No balance') }}">—</span></td>
                                @else
                                    <td class="text-end acc-amount {{ $value < 0 ? 'is-debit' : 'is-credit' }}" dir="ltr" title="{{ $value < 0 ? __('They owe us') : __('We owe them') }}">{{ $value < 0 ? '−' : '' }}{{ format_amount(abs($value), 2) }}<span class="visually-hidden"> {{ $currency->code }}, {{ $value < 0 ? __('they owe us') : __('we owe them') }}</span></td>
                                @endif
                            @endforeach
                            <td class="text-end text-nowrap no-print">
                                <div class="act-group">
                                    <a class="btn-act is-blue" href="{{ $url }}" title="{{ $tView }}" aria-label="{{ $tView }}">{!! $ico('eye') !!}</a>
                                    @if ($canUpdate)
                                        <button type="button" class="btn-act is-amber" data-account-edit="{{ $editJson }}" title="{{ $tEdit }}" aria-label="{{ $tEdit }}">{!! $ico('pen') !!}</button>
                                    @endif
                                    @if (($canMessage && $hasPhone) || ($canDelete && ! $isCash))
                                        <div class="dropdown d-inline-block">
                                            <button class="btn-act is-gray" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ $tMore }}" aria-label="{{ $tMore }}">{!! $ico('ellipsis-vertical') !!}</button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @if ($canMessage && $hasPhone)
                                                    <li><button class="dropdown-item" type="button" data-whatsapp="{{ route('admin.accounts.send-whatsapp', $account->id) }}">{!! $ico('comment-dots', 'me-2 text-success') !!} {{ __('Send the balance on WhatsApp') }}</button></li>
                                                @endif
                                                @if ($canDelete && ! $isCash)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('admin.accounts.destroy', $account->id) }}"
                                                              data-confirm="{{ __('Delete :name?', ['name' => $account->name]) }}"
                                                              data-confirm-text="{{ __('The account and every transaction on it are deleted. This cannot be undone.') }}"
                                                              data-confirm-ok="{{ __('Delete') }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="dropdown-item text-danger" type="submit">{!! $ico('trash', 'me-2') !!} {{ __('Delete') }}</button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </td>
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

@if ($canCreate || $canUpdate)
{{-- one pop-up for a new account and for changing one --}}
<div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <form class="modal-content" method="POST" id="accountForm" action="{{ route('admin.accounts.store') }}"
              data-store="{{ route('admin.accounts.store') }}" data-update="{{ url('admin/accounts') }}/" data-check="{{ route('admin.accounts.checkCode') }}">
            @csrf
            <input type="hidden" name="_method" value="POST" id="accountMethod">
            <input type="hidden" name="_modal" value="create" id="accountModalKind">
            <input type="hidden" name="_id" value="" id="accountId">
            <input type="hidden" name="code" value="" id="accountCode">
            <div class="modal-header">
                <h2 class="modal-title h5" id="accountModalTitle" data-new="{{ __('ui.add_new_account') }}" data-edit="{{ __('accounts.edit_account') }}">{{ __('ui.add_new_account') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                @if ($errors->any() && $modalOld)
                    <div class="alert alert-danger py-2" role="alert">{{ $errors->first() }}</div>
                @endif
                <div class="mb-3">
                    <label class="form-label" for="accountName">{{ __('ui.account_name') }}</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="accountName" name="name" value="{{ old('name') }}" maxlength="255" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="accountType">{{ __('ui.account_type') }}</label>
                        <select class="form-select @error('create_account_sub_category_id') is-invalid @enderror" id="accountType" name="create_account_sub_category_id" required>
                            <option value="" disabled @selected(! old('create_account_sub_category_id'))>{{ __('ui.select_account_type') }}</option>
                            @foreach ($subCategories as $sub)
                                <option value="{{ $sub->id }}" data-prefix="{{ strtoupper((string) $sub->accountCategory?->code_prefix) }}" @selected((string) old('create_account_sub_category_id') === (string) $sub->id)>{{ $typeName($sub) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="accountCodeSuffix">{{ __('ui.code') }}</label>
                        <div class="input-group" dir="ltr">
                            <span class="input-group-text" id="accountCodePrefix">#</span>
                            <input class="form-control @error('code') is-invalid @enderror" id="accountCodeSuffix" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="accountCodeHelp">
                        </div>
                        <div class="form-text" id="accountCodeHelp" aria-live="polite">{{ __('Leave empty for the next number.') }}</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="accountContact">{{ __('ui.contact') }}</label>
                    <input class="form-control" id="accountContact" name="contact" type="tel" dir="ltr" value="{{ old('contact', '+93') }}" maxlength="255" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="accountAddress">{{ __('ui.address') }}</label>
                    <textarea class="form-control" id="accountAddress" name="address" rows="2">{{ old('address') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="accountNote">{{ __('ui.note') }}</label>
                    <input class="form-control" id="accountNote" name="company" value="{{ old('company') }}" maxlength="255">
                </div>
                <input type="hidden" name="include_in_reports" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="accountReports" name="include_in_reports" value="1" @checked(old('include_in_reports', '1') === '1')>
                    <label class="form-check-label" for="accountReports">{{ __('accounts.include_reports') }}</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                <button type="submit" class="btn btn-primary" id="accountSubmit"><x-icon name="check" class="me-1" /> {{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

@if ($canCreate)
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin.accounts.bulk-import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title h5" id="importModalTitle">{{ __('ui.bulk_account_import') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <ol class="acc-steps">
                    <li>
                        <strong>{{ __('Download the template') }}</strong>
                        <a class="btn btn-sm btn-outline-soft ms-2" href="{{ route('admin.accounts.bulk-import.template') }}" data-no-prefetch><x-icon name="download" class="me-1" /> {{ __('ui.download_excel_template') }}</a>
                    </li>
                    <li>
                        <strong>{{ __('Fill it in') }}</strong>
                        <ul class="small text-muted mt-1 mb-0">
                            <li>{{ __('ui.import_instructions') }}</li>
                            <li>{{ __('ui.import_optional_fields_help') }}</li>
                            <li>{{ __('ui.import_balance_sign_help') }}</li>
                            <li>{{ __('ui.import_atomic_help') }}</li>
                        </ul>
                    </li>
                    <li>
                        <label class="form-label fw-semibold" for="importFile">{{ __('ui.csv_or_excel_file') }}</label>
                        <input class="form-control" id="importFile" name="file" type="file" accept=".csv,.xlsx,.xls" required aria-describedby="importHelp">
                        <div class="form-text" id="importHelp">{{ __('ui.import_file_help') }}</div>
                    </li>
                </ol>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                <button type="submit" class="btn btn-primary"><x-icon name="upload" class="me-1" /> {{ __('ui.import_accounts') }}</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var text = {
        checking: @json(__('ui.blade_checking')),
        free: @json(__('ui.blade_code_is_available')),
        taken: @json(__('ui.blade_code_already_exists')),
        empty: @json(__('Leave empty for the next number.')),
        needed: @json(__('An account that exists keeps a code; write one.')),
        sent: @json(__('The message is ready in WhatsApp.')),
        failed: @json(__('accounts.send_failed'))
    };

    // totals: hide or show, remembered on this device
    var toggle = document.getElementById('summaryToggle'), summary = document.getElementById('accSummary');
    if (toggle && summary) toggle.addEventListener('click', function () {
        var open = summary.hidden;
        summary.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        try { localStorage.setItem('sb.accounts.totals', open ? '1' : '0'); } catch (e) {}
    });

    // filters, search and pages: only the totals and the list are asked for and changed in place;
    // the address follows, so a link, Back and a reload show the same list
    (function () {
        var filters = document.getElementById('accountFilters');
        var results = document.getElementById('accResults');
        var summaryBody = document.getElementById('accSummaryBody');
        var clear = document.getElementById('accClear');
        var progress = document.getElementById('navProgress');
        if (!filters || !results || !summaryBody || !window.fetch || !window.DOMParser) return;
        var cache = {}, current = null, typing = null;
        var savedAt = function () { try { return JSON.parse(localStorage.getItem('sb.saved') || '0'); } catch (e) { return 0; } };
        var urlOf = function () {
            var params = new URLSearchParams(new FormData(filters));
            Array.from(params.keys()).forEach(function (k) { if (!params.get(k)) params.delete(k); });
            if (params.get('per_page') === '25') params.delete('per_page');
            var q = params.toString();
            return location.pathname + (q ? '?' + q : '');
        };
        var get = function (url) {
            var hit = cache[url];
            if (hit && hit.at > savedAt() && Date.now() - hit.at < 60000) return hit.p;
            var p = fetch(url, { headers: { 'X-Fragment': 'accounts', 'Accept': 'text/html' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok || r.redirected) throw new Error(r.status); return r.text(); });
            cache[url] = { at: Date.now(), p: p };
            p.catch(function () { delete cache[url]; });
            return p;
        };
        var show = function (url, push) {
            var mine = current = url, started = performance.now();
            results.classList.add('is-loading'); results.setAttribute('aria-busy', 'true');
            if (progress) progress.classList.add('is-active');
            get(url).then(function (html) {
                if (mine !== current) return;
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var nextResults = doc.getElementById('accResults'), nextSummary = doc.getElementById('accSummaryBody');
                if (!nextResults || !nextSummary) throw new Error('parts');
                results.replaceWith(nextResults); results = nextResults;
                summaryBody.replaceWith(nextSummary); summaryBody = nextSummary;
                if (clear) clear.hidden = results.dataset.filtered !== '1';
                if (push === 'push') history.pushState({ acc: 1 }, '', url);
                else if (push === 'replace') history.replaceState({ acc: 1 }, '', url);
                document.querySelectorAll('#accResults table.table').forEach(function (t) { if (ERP.cardify) ERP.cardify(t); });
                if (ERP.timing) ERP.timing('list', performance.now() - started);
            }).catch(function () {
                if (mine === current) location.href = url; // anything odd: load the page the ordinary way
            }).finally(function () {
                if (mine !== current) return;
                results.classList.remove('is-loading'); results.removeAttribute('aria-busy');
                if (progress) progress.classList.remove('is-active');
            });
        };
        var sync = function (url) {
            var params = new URL(url, location.href).searchParams;
            Array.from(filters.elements).forEach(function (el) {
                if (!el.name) return;
                el.value = params.get(el.name) || (el.name === 'per_page' ? '25' : '');
            });
        };
        history.replaceState({ acc: 1 }, '', location.href);
        // the list as it came with the page: going back to it (Back, Clear) needs no request
        var startUrl = location.pathname + location.search;
        cache[startUrl] = { at: Date.now(), p: Promise.resolve(summaryBody.outerHTML + results.outerHTML) };

        filters.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(typing); show(urlOf(), 'push'); });
        filters.querySelectorAll('[data-autosubmit]').forEach(function (select) {
            select.addEventListener('change', function () { show(urlOf(), 'push'); });
        });
        var search = filters.querySelector('input[name=q]');
        if (search) search.addEventListener('input', function () {
            clearTimeout(typing);
            typing = setTimeout(function () { show(urlOf(), 'replace'); }, 280);
        });
        if (clear) clear.addEventListener('click', function (e) {
            e.preventDefault(); filters.reset(); sync(clear.href); show(location.pathname, 'push');
        });
        // pages of the list: changed in place, and the page under the pointer is fetched ahead
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey) return;
            var a = e.target.closest && e.target.closest('#accResults .pagination a[href]');
            if (!a) return;
            e.preventDefault();
            show(a.pathname + a.search, 'push');
            filters.scrollIntoView({ block: 'start', behavior: 'smooth' });
        });
        document.addEventListener('pointerover', function (e) {
            var a = e.target.closest && e.target.closest('#accResults .pagination a[href]');
            if (a) get(a.pathname + a.search).catch(function () {});
        });
        window.addEventListener('popstate', function (e) {
            if (!e.state || !e.state.acc) return;
            sync(location.href);
            show(location.pathname + location.search, null);
        });
    })();

    var form = document.getElementById('accountForm');
    if (form) {
        var modalEl = document.getElementById('accountModal');
        var title = document.getElementById('accountModalTitle');
        var type = document.getElementById('accountType');
        var prefix = document.getElementById('accountCodePrefix');
        var suffix = document.getElementById('accountCodeSuffix');
        var code = document.getElementById('accountCode');
        var help = document.getElementById('accountCodeHelp');
        var submit = document.getElementById('accountSubmit');
        var editing = null, timer = null, request = 0;

        var currentPrefix = function () {
            var option = type.options[type.selectedIndex];
            return (option && option.dataset.prefix) || '#';
        };
        var syncCode = function () {
            suffix.value = suffix.value.toUpperCase().replace(/\s+/g, '');
            prefix.textContent = currentPrefix();
            code.value = suffix.value ? prefix.textContent + suffix.value : '';
        };
        var showHelp = function (state) {
            suffix.classList.toggle('is-invalid', state === 'taken' || state === 'needed');
            suffix.classList.toggle('is-valid', state === 'free');
            help.textContent = text[state];
            help.className = 'form-text' + (state === 'taken' || state === 'needed' ? ' text-danger' : state === 'free' ? ' text-success' : '');
            submit.disabled = state === 'taken';
        };
        var checkCode = function () {
            clearTimeout(timer);
            syncCode();
            if (!code.value) { showHelp(editing ? 'needed' : 'empty'); return; }
            showHelp('checking');
            var mine = ++request;
            timer = setTimeout(function () {
                var url = form.dataset.check + '?code=' + encodeURIComponent(code.value) + (editing ? '&ignore_id=' + editing : '');
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { if (mine === request) showHelp(d.available ? 'free' : 'taken'); })
                    .catch(function () { if (mine === request) showHelp('empty'); });
            }, 300);
        };
        type.addEventListener('change', checkCode);
        suffix.addEventListener('input', checkCode);

        var open = function (data) {
            editing = data ? data.id : null;
            form.reset();
            form.action = data ? form.dataset.update + data.id : form.dataset.store;
            document.getElementById('accountMethod').value = data ? 'PUT' : 'POST';
            document.getElementById('accountModalKind').value = data ? 'edit' : 'create';
            document.getElementById('accountId').value = data ? data.id : '';
            title.textContent = data ? title.dataset.edit : title.dataset.new;
            form.querySelectorAll('.is-invalid,.is-valid').forEach(function (el) { el.classList.remove('is-invalid', 'is-valid'); });
            var alert = form.querySelector('.alert-danger'); if (alert) alert.remove();
            if (data) {
                document.getElementById('accountName').value = data.name || '';
                type.value = data.type || '';
                document.getElementById('accountContact').value = data.contact || '';
                document.getElementById('accountAddress').value = data.address || '';
                document.getElementById('accountNote').value = data.note || '';
                document.getElementById('accountReports').checked = !!data.reports;
                var p = currentPrefix(), c = data.code || '';
                suffix.value = c.indexOf(p) === 0 ? c.slice(p.length) : c;
            } else {
                document.getElementById('accountContact').value = '+93';
                document.getElementById('accountReports').checked = true;
                suffix.value = '';
            }
            syncCode();
            showHelp(data ? 'free' : 'empty');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        };
        modalEl.addEventListener('shown.bs.modal', function () { document.getElementById('accountName').focus(); });
        document.addEventListener('click', function (e) {
            var b = e.target.closest && e.target.closest('[data-account-new], [data-account-edit]');
            if (!b) return;
            open(b.hasAttribute('data-account-edit') ? JSON.parse(b.dataset.accountEdit) : null);
        });
        form.addEventListener('submit', function (e) {
            syncCode();
            if (editing && !code.value) { e.preventDefault(); showHelp('needed'); suffix.focus(); }
        });

        // the form came back with an error: open it again as it was
        @if ($modalOld)
            editing = @json(old('_id') ?: null);
            form.action = editing ? form.dataset.update + editing : form.dataset.store;
            document.getElementById('accountMethod').value = editing ? 'PUT' : 'POST';
            document.getElementById('accountModalKind').value = editing ? 'edit' : 'create';
            document.getElementById('accountId').value = editing || '';
            title.textContent = editing ? title.dataset.edit : title.dataset.new;
            (function () { var p = currentPrefix(), c = @json(old('code', '')); suffix.value = c.indexOf(p) === 0 ? c.slice(p.length) : c; syncCode(); })();
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        @endif
    }

    // WhatsApp: the window is opened at once (so it is not blocked) and sent to the message when it is ready
    document.addEventListener('click', function (e) {
        var button = e.target.closest && e.target.closest('[data-whatsapp]');
        if (!button) return;
        (function () {
            var win = window.open('about:blank', '_blank');
            button.disabled = true;
            fetch(button.dataset.whatsapp, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    if (!res.ok || !res.d.url) throw new Error(res.d.error || text.failed);
                    if (win) win.location = res.d.url; else window.location = res.d.url;
                    if (window.ERP && ERP.toast) ERP.toast(text.sent, 'success');
                })
                .catch(function (e) {
                    if (win) win.close();
                    if (window.ERP && ERP.toast) ERP.toast(e.message || text.failed, 'danger');
                })
                .finally(function () { button.disabled = false; });
        })();
    });
});
</script>
@endpush
