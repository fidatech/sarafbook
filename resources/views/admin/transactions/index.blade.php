@extends('layouts.admin.base')
@section('title', __('transactions.title'))
@section('modern', '1')

{{--
    Transactions in the fida-sarafi style: the form for a new entry (or changing a manual one)
    and every visible transaction below it, filtered on the server, with the totals of what is
    shown. Entries made by an exchange, a remittance or an expense are changed there, not here.
    Modes: treasure (the account and the safe), track (the account and a second account),
    no_track (the account alone).
--}}
@php
    $me = auth()->user();
    $canCreate = $me->can('create transactions');
    $canUpdate = $me->can('update transactions');
    $canDelete = $me->can('delete transactions');
    $isEdit = (bool) $editing;
    $again = old('_form') === 'tx';
    $val = fn ($key, $default = null) => $again ? old($key, $default) : ($editing[$key] ?? $default);
    $entries = $again ? array_values((array) old('entries', [])) : ($editing ? [[
        'type' => $editing['transaction_type'], 'currency_id' => $editing['currency_id'],
        'amount' => rtrim(rtrim(number_format((float) $editing['amount'], 2, '.', ''), '0'), '.'), 'note' => $editing['note'],
    ]] : []);
    if (! $entries) {
        $entries = [['type' => 'credit', 'currency_id' => $currencies->firstWhere('is_default', true)?->id ?? $currencies->first()?->id, 'amount' => '', 'note' => '']];
    }
    $mode = $val('transaction_mode', 'treasure');
    $accountOptions = $accounts->map(fn ($a) => [$a->id, $a->code.' · '.$a->name]);
    $sourceOf = fn ($row) => match (strtolower((string) $row->table_name)) {
        'exchanges' => ['exchanges', __('Exchange'), 'info'],
        'remittances' => ['remittances', __('Remittance'), 'warning'],
        'expenses' => ['expenses', __('Expense'), 'danger'],
        'accounts_opening_import' => ['opening', __('Opening balance'), 'secondary'],
        default => ['journal', __('Entry'), 'primary'],
    };
    $amountText = fn ($v) => format_amount((float) $v, 2);
@endphp

@section('content')
<x-page-header :title="__('transactions.title')" icon="right-left" :subtitle="__('Money in and out of the accounts.')">
    @can('view journal')<a class="btn btn-outline-soft" href="{{ route('admin.journal.index') }}"><x-icon name="book" class="me-1" /> {{ __('ui.journal') }}</a>@endcan
</x-page-header>

@if ($canCreate || ($isEdit && $canUpdate))
<form method="POST" action="{{ $isEdit ? route('admin.transactions.update', $editing['linked_id']) : route('admin.transactions.store') }}" enctype="multipart/form-data" class="card mb-3 tx-card" id="txForm" novalidate>
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <input type="hidden" name="_form" value="tx">
    @if (request('back'))<input type="hidden" name="back" value="{{ request('back') }}">@endif
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <div class="me-auto">
            <h2 class="h5 card-title mb-0">{{ $isEdit ? __('Change the entry') : __('New transaction') }}</h2>
            @if ($isEdit)<span class="text-muted small">{{ __('Saved :date', ['date' => $editing['created_at']]) }}</span>@endif
        </div>
        @if ($isEdit || request('back'))
            <a class="btn btn-sm btn-light-secondary" href="{{ request('back') ? back_to(route('admin.transactions.index')) : route('admin.transactions.index') }}">{{ __('Cancel') }}</a>
        @endif
    </div>
    <div class="card-body">
        @if ($again && $errors->any())
            <div class="alert alert-danger py-2" role="alert"><ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="row g-3">
            <div class="col-12 col-md-3">
                <label class="form-label" for="txDate">{{ __('ui.date') }} <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="txDate" name="date" value="{{ $val('date', now()->toDateString()) }}" required max="{{ now()->addDay()->toDateString() }}">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="txAccount">{{ __('ui.account') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="txAccount" name="account_id" required data-search data-balances="{{ route('admin.transactions.account-balances') }}">
                    <option value="">{{ __('Type a name or code…') }}</option>
                    @foreach ($accountOptions as [$id, $text])<option value="{{ $id }}" @selected((string) $val('account_id', request('account_id')) === (string) $id)>{{ $text }}</option>@endforeach
                </select>
                <div class="tx-balances" id="txBalances" aria-live="polite"></div>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="txRef">{{ __('ui.blade_reference') }}</label>
                <input type="text" class="form-control" id="txRef" name="reference" value="{{ $val('reference') }}" maxlength="255" placeholder="{{ __('Voucher number') }}">
            </div>

            <div class="col-12">
                <fieldset>
                    <legend class="form-label">{{ __('How the money moves') }}</legend>
                    <div class="tx-modes">
                        @foreach ([
                            'treasure' => ['vault', __('With the safe'), __('Cash comes into or goes out of the safe.')],
                            'track' => ['arrow-right-arrow-left', __('Between two accounts'), __('One account gets what the other gives.')],
                            'no_track' => ['user', __('On this account only'), __('Only this account changes.')],
                        ] as $value => [$icon, $label, $hint])
                            <label class="tx-mode">
                                <input type="radio" name="transaction_mode" value="{{ $value }}" @checked($mode === $value)>
                                <span class="tx-mode-body"><span class="coin">{!! ico($icon) !!}</span><span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>
            <div class="col-12 col-md-6" id="txTrackBox" @if ($mode !== 'track') hidden @endif>
                <label class="form-label" for="txTrack">{{ __('The other account') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="txTrack" name="track_account_id" data-search>
                    <option value="">{{ __('Type a name or code…') }}</option>
                    @foreach ($accountOptions as [$id, $text])<option value="{{ $id }}" @selected((string) $val('track_account_id') === (string) $id)>{{ $text }}</option>@endforeach
                </select>
                <div class="form-text">{{ __('When this account gets money (credit), the other gives it (debit), and the other way round.') }}</div>
            </div>

            <div class="col-12">
                <div class="tx-section">{{ __('Amounts') }}</div>
                <div id="txEntries" class="tx-entries">
                    @foreach ($entries as $i => $entry)
                        <div class="tx-entry" data-entry>
                            <div class="tx-entry-type">
                                <label class="visually-hidden" for="e{{ $i }}t">{{ __('ui.type') }}</label>
                                <select class="form-select" id="e{{ $i }}t" name="entries[{{ $i }}][type]" required data-plain>
                                    <option value="credit" @selected(($entry['type'] ?? 'credit') === 'credit')>{{ __('ui.credit') }} (+)</option>
                                    <option value="debit" @selected(($entry['type'] ?? '') === 'debit')>{{ __('ui.debit') }} (−)</option>
                                </select>
                            </div>
                            <div class="tx-entry-cur">
                                <label class="visually-hidden" for="e{{ $i }}c">{{ __('ui.currency') }}</label>
                                <select class="form-select" id="e{{ $i }}c" name="entries[{{ $i }}][currency_id]" required data-plain>
                                    @foreach ($currencies->where('is_active', true) as $c)<option value="{{ $c->id }}" @selected((string) ($entry['currency_id'] ?? '') === (string) $c->id)>{{ $c->code }}</option>@endforeach
                                </select>
                            </div>
                            <div class="tx-entry-amount">
                                <label class="visually-hidden" for="e{{ $i }}a">{{ __('ui.amount') }}</label>
                                <input type="text" class="form-control" id="e{{ $i }}a" name="entries[{{ $i }}][amount]" value="{{ $entry['amount'] ?? '' }}" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="0" required>
                            </div>
                            <div class="tx-entry-note">
                                <label class="visually-hidden" for="e{{ $i }}n">{{ __('ui.note') }}</label>
                                <input type="text" class="form-control" id="e{{ $i }}n" name="entries[{{ $i }}][note]" value="{{ $entry['note'] ?? '' }}" maxlength="500" placeholder="{{ __('Note (written for you when empty)') }}">
                            </div>
                            <div class="tx-entry-file">
                                <label class="btn btn-light-secondary w-100 mb-0" title="{{ __('Attach a picture or PDF') }}">
                                    {!! ico('paperclip') !!}<span class="visually-hidden">{{ __('Attach a picture or PDF') }}</span>
                                    <input type="file" name="entries[{{ $i }}][attachment]" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" hidden>
                                </label>
                            </div>
                            <div class="tx-entry-del">
                                <button type="button" class="btn btn-light-secondary w-100" data-entry-remove title="{{ __('Remove this line') }}" aria-label="{{ __('Remove this line') }}" @if (count($entries) === 1) hidden @endif>{!! ico('xmark') !!}</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                @unless ($isEdit)
                    <button type="button" class="btn btn-sm btn-light-secondary mt-2" id="txAddEntry">{!! ico('plus', 'me-1') !!} {{ __('Another currency') }}</button>
                @endunless
            </div>
        </div>
    </div>
    <div class="card-footer d-flex flex-wrap align-items-center gap-2">
        <span class="text-muted small me-auto" id="txSay" aria-live="polite"></span>
        <button type="submit" class="btn btn-primary px-4"><x-icon name="check" class="me-1" /> {{ $isEdit ? __('Save the change') : __('Save') }}</button>
    </div>
</form>
@endif

<section class="card" aria-labelledby="txListTitle">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <h2 class="h5 card-title mb-0 me-auto" id="txListTitle">{{ __('All transactions') }}</h2>
    </div>
    <div class="card-body">
        <form method="GET" class="list-tools" role="search" data-live="list,totals">
            <input type="search" class="form-control grow" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Account, note or reference…') }}" aria-label="{{ __('ui.search') }}">
            <select class="form-select" name="currency_id" aria-label="{{ __('ui.currency') }}" data-plain>
                <option value="">{{ __('All currencies') }}</option>
                @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected($filters['currency_id'] === $c->id)>{{ $c->code }}</option>@endforeach
            </select>
            <select class="form-select" name="type" aria-label="{{ __('ui.type') }}" data-plain>
                <option value="">{{ __('In and out') }}</option>
                <option value="credit" @selected($filters['type'] === 'credit')>{{ __('ui.credit') }} (+)</option>
                <option value="debit" @selected($filters['type'] === 'debit')>{{ __('ui.debit') }} (−)</option>
            </select>
            <select class="form-select" name="source" aria-label="{{ __('From') }}" data-plain>
                <option value="">{{ __('Every kind') }}</option>
                <option value="journal" @selected($filters['source'] === 'journal')>{{ __('Entries') }}</option>
                <option value="exchanges" @selected($filters['source'] === 'exchanges')>{{ __('Exchanges') }}</option>
                <option value="remittances" @selected($filters['source'] === 'remittances')>{{ __('Remittances') }}</option>
                <option value="expenses" @selected($filters['source'] === 'expenses')>{{ __('Expenses') }}</option>
            </select>
            <input type="date" class="form-control" name="from" value="{{ $filters['from'] }}" aria-label="{{ __('From date') }}" title="{{ __('From date') }}">
            <input type="date" class="form-control" name="to" value="{{ $filters['to'] }}" aria-label="{{ __('To date') }}" title="{{ __('To date') }}">
            @if ($filters['account_id'])<input type="hidden" name="account_id" value="{{ $filters['account_id'] }}">@endif
            <a class="btn btn-light-secondary" href="{{ route('admin.transactions.index') }}" data-live-clear title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
        </form>

@fragment('totals')
<div data-live-part="totals">
        @if ($totals->isNotEmpty())
            <div class="tx-totals mb-3">
                <span class="tx-totals-label">{{ $filtered ? __('What is shown') : __('Today') }}</span>
                @foreach ($totals as $t)
                    @php $net = (float) $t->credit - (float) $t->debit; @endphp
                    <div class="tx-total">
                        <span class="chip-code">{{ $t->code }}</span>
                        <span class="amount is-credit" dir="ltr" title="{{ __('ui.credit') }}">+{{ $amountText($t->credit) }}</span>
                        <span class="amount is-debit" dir="ltr" title="{{ __('ui.debit') }}">−{{ $amountText($t->debit) }}</span>
                        <strong dir="ltr" class="{{ $net < 0 ? 'is-owed' : 'is-owing' }}" title="{{ __('Net') }}">= {{ $net < 0 ? '−' : '' }}{{ $amountText(abs($net)) }}</strong>
                    </div>
                @endforeach
            </div>
        @endif
</div>
@endfragment

@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}">
        @if ($rows->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="right-left" /></div>
                <h2 class="h3">{{ $filtered ? __('Nothing found') : __('No transaction yet') }}</h2>
            </div>
        @else
            <p class="list-legend small text-muted mb-2"><span class="ms-auto">{{ __(':from–:to of :total', ['from' => number_format($rows->firstItem()), 'to' => number_format($rows->lastItem()), 'total' => number_format($rows->total())]) }}</span></p>
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ui.date') }}</th>
                            <th scope="col">{{ __('ui.account') }}</th>
                            <th scope="col">{{ __('ui.note') }}</th>
                            <th scope="col" class="is-num">{{ __('ui.amount') }}</th>
                            <th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php [$tStatement, $tEdit, $tDelete, $tPrint, $tOpen, $tFile] = [__('Account statement'), __('Edit'), __('Delete'), __('Print'), __('Open the record'), __('Attachment')]; @endphp
                        @foreach ($rows as $row)
                            @php
                                [$src, $srcLabel, $srcTone] = $sourceOf($row);
                                $in = $row->transaction_type === 'credit';
                                $manual = $src === 'journal';
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ optional($row->created_at)->format('Y-m-d') }}<div class="row-meta">{{ optional($row->created_at)->format('H:i') }}</div></td>
                                <td>
                                    @if ($row->account)
                                        <a class="row-name" href="{{ route('admin.accounts.show', $row->account_id) }}">{{ $row->account->name }}</a>
                                        <div class="row-meta"><span dir="ltr">{{ $row->account->code }}</span> <span class="badge-soft {{ $srcTone }}">{{ $srcLabel }}</span></div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="tx-note">
                                    <span class="d-block text-break">{{ \Illuminate\Support\Str::limit((string) $row->note, 140) ?: '—' }}</span>
                                    @if ($row->reference)<span class="chip-code" dir="ltr">{{ $row->reference }}</span>@endif
                                </td>
                                <td class="is-num"><span class="amount {{ $in ? 'is-credit' : 'is-debit' }}" dir="ltr">{{ $in ? '+' : '−' }}{{ $amountText($row->amount) }} <small>{{ $row->currency->code ?? '' }}</small></span></td>
                                <td class="text-end text-nowrap">
                                    <div class="act-group">
                                        @if ($row->attachment)
                                            <a class="btn-act is-teal" href="{{ asset($row->attachment) }}" target="_blank" rel="noopener" title="{{ $tFile }}" aria-label="{{ $tFile }}">{!! ico('paperclip') !!}</a>
                                        @endif
                                        @if ($manual && strcasecmp((string) $row->table_name, 'Journal') === 0)
                                            <a class="btn-act is-blue" href="{{ route('admin.transactions.print', $row) }}" target="_blank" rel="noopener" title="{{ $tPrint }}" aria-label="{{ $tPrint }}" data-no-prefetch>{!! ico('print') !!}</a>
                                        @endif
                                        @if ($manual && $canUpdate)
                                            <a class="btn-act is-amber" href="{{ route('admin.transactions.index', ['edit' => $row->id]) }}#txForm" title="{{ $tEdit }}" aria-label="{{ $tEdit }}" data-no-prefetch>{!! ico('pen') !!}</a>
                                        @elseif ($src === 'exchanges' && $row->table_row_id)
                                            <a class="btn-act is-indigo" href="{{ route('admin.exchanges.show', $row->table_row_id) }}" title="{{ $tOpen }}" aria-label="{{ $tOpen }}">{!! ico('arrow-up-right-from-square') !!}</a>
                                        @endif
                                        @if ($canDelete)
                                            <form method="POST" action="{{ route('admin.transactions.destroy', $row->id) }}" class="d-inline"
                                                  data-confirm="{{ __('Delete this transaction?') }}"
                                                  data-confirm-text="{{ $manual ? __('Both sides of the entry are deleted and the balances change.') : __('The :what it belongs to is deleted with it, and the balances change.', ['what' => mb_strtolower($srcLabel)]) }}"
                                                  data-confirm-ok="{{ $tDelete }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-act is-red" title="{{ $tDelete }}" aria-label="{{ $tDelete }}">{!! ico('trash') !!}</button>
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
</section>

<template id="txEntryTemplate">
    <div class="tx-entry" data-entry>
        <div class="tx-entry-type"><select class="form-select" name="entries[__][type]" required data-plain aria-label="{{ __('ui.type') }}"><option value="credit">{{ __('ui.credit') }} (+)</option><option value="debit">{{ __('ui.debit') }} (−)</option></select></div>
        <div class="tx-entry-cur"><select class="form-select" name="entries[__][currency_id]" required data-plain aria-label="{{ __('ui.currency') }}">@foreach ($currencies->where('is_active', true) as $c)<option value="{{ $c->id }}">{{ $c->code }}</option>@endforeach</select></div>
        <div class="tx-entry-amount"><input type="text" class="form-control" name="entries[__][amount]" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="0" required aria-label="{{ __('ui.amount') }}"></div>
        <div class="tx-entry-note"><input type="text" class="form-control" name="entries[__][note]" maxlength="500" placeholder="{{ __('Note (written for you when empty)') }}" aria-label="{{ __('ui.note') }}"></div>
        <div class="tx-entry-file"><label class="btn btn-light-secondary w-100 mb-0" title="{{ __('Attach a picture or PDF') }}">{!! ico('paperclip') !!}<span class="visually-hidden">{{ __('Attach a picture or PDF') }}</span><input type="file" name="entries[__][attachment]" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" hidden></label></div>
        <div class="tx-entry-del"><button type="button" class="btn btn-light-secondary w-100" data-entry-remove title="{{ __('Remove this line') }}" aria-label="{{ __('Remove this line') }}">{!! ico('xmark') !!}</button></div>
    </div>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('txForm');
    if (!form) return;
    var text = {!! json_encode(['balance' => __('Balance'), 'none' => __('No balance yet'), 'lines' => __(':count lines will be saved')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var box = document.getElementById('txEntries'), tpl = document.getElementById('txEntryTemplate');
    var account = form.querySelector('select[name=account_id]'), balances = document.getElementById('txBalances');
    var trackBox = document.getElementById('txTrackBox'), track = form.querySelector('select[name=track_account_id]'), say = document.getElementById('txSay');

    // the balance of the chosen account, in every currency
    var showBalances = function () {
        balances.innerHTML = '';
        if (!account.value) return;
        fetch(account.dataset.balances + '?account_id=' + encodeURIComponent(account.value), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var html = '';
                Object.keys(d.balances || {}).forEach(function (code) {
                    var b = parseFloat(d.balances[code].balance) || 0;
                    if (Math.abs(b) < 0.005) return;
                    html += '<span class="tx-balance ' + (b < 0 ? 'is-owed' : 'is-owing') + '" dir="ltr"><b>' + ERP.escape(code) + '</b> ' + (b < 0 ? '−' : '') + Math.abs(b).toLocaleString('en-US', { maximumFractionDigits: 2 }) + '</span>';
                });
                balances.innerHTML = html ? '<span class="text-muted small">' + ERP.escape(text.balance) + ':</span> ' + html : '<span class="text-muted small">' + ERP.escape(text.none) + '</span>';
            }).catch(function () {});
    };
    account.addEventListener('change', showBalances);
    if (account.value) showBalances();

    // the second account only for "between two accounts"
    var syncMode = function () {
        var m = (form.querySelector('input[name=transaction_mode]:checked') || {}).value;
        trackBox.hidden = m !== 'track';
    };
    form.addEventListener('change', function (e) { if (e.target.name === 'transaction_mode') syncMode(); });
    syncMode();

    // lines: one per currency
    var count = function () {
        var rows = box.querySelectorAll('[data-entry]');
        rows.forEach(function (r) { var b = r.querySelector('[data-entry-remove]'); if (b) b.hidden = rows.length === 1; });
        say.textContent = rows.length > 1 ? text.lines.replace(':count', rows.length) : '';
    };
    var add = document.getElementById('txAddEntry');
    if (add) add.addEventListener('click', function () {
        var n = Date.now() % 100000;
        var html = tpl.innerHTML.replace(/entries\[__\]/g, 'entries[' + n + ']');
        box.insertAdjacentHTML('beforeend', html);
        var last = box.lastElementChild;
        var prev = last.previousElementSibling;
        if (prev) last.querySelector('select[name$="[type]"]').value = prev.querySelector('select[name$="[type]"]').value;
        last.querySelector('input[name$="[amount]"]').focus();
        count();
    });
    box.addEventListener('click', function (e) {
        var b = e.target.closest('[data-entry-remove]');
        if (!b) return;
        b.closest('[data-entry]').remove();
        count();
    });
    // a chosen file shows on its button
    box.addEventListener('change', function (e) {
        if (e.target.type !== 'file') return;
        var label = e.target.closest('label');
        label.classList.toggle('btn-primary', !!e.target.files.length);
        label.classList.toggle('btn-light-secondary', !e.target.files.length);
        label.title = e.target.files.length ? e.target.files[0].name : label.getAttribute('title');
    });
    count();
    if (location.hash === '#txForm') setTimeout(function () { var f = document.getElementById('txAccount'); if (f) f.focus(); }, 50);
});
</script>
@endpush
