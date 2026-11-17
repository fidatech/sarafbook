@extends('layouts.admin.base')
@section('title', $account->name)
@section('modern', '1')

{{--
    The account statement in the fida-sarafi style: the account and its balance in each currency,
    quick links to record money for it (the full forms, with the account filled in, coming back
    here after saving), and its lines newest first with the balance after each line. Colour marks
    and the OK tick for checking, one line or many, as in the journal.
--}}
@php
    $me = auth()->user();
    $here = request()->getRequestUri();
    $back = route('admin.accounts.show', $account->id);
    $canMark = $me->can('update transactions');
    $canDelete = $me->can('delete transactions');
    $amountText = fn ($v) => format_amount((float) $v, 2);
    $type = __('accounts.types')[$account->subCategory->name ?? ''] ?? ($account->subCategory->name ?? '—');
    $isCash = (int) $account->subCategory?->account_category_id === (int) \App\Models\AccountCategory::where('name', 'Cash')->value('id');
    $hasPhone = strlen(preg_replace('/\D/', '', (string) $account->contact)) >= 6;
    $sourceOf = fn ($row) => match (strtolower((string) $row->table_name)) {
        'exchanges' => ['exchanges', __('Exchange'), 'info'],
        'remittances' => ['remittances', __('Remittance'), 'warning'],
        'expenses' => ['expenses', __('Expense'), 'danger'],
        'accounts_opening_import' => ['opening', __('Opening balance'), 'secondary'],
        default => ['journal', __('Entry'), 'primary'],
    };
@endphp

@section('content')
<x-page-header :title="$account->name" icon="address-book" :crumbs="[[__('ui.accounts'), route('admin.accounts.index')], [$account->code]]">
    <a class="btn btn-outline-soft" href="{{ route('admin.accounts.print', $account->id) }}" target="_blank" rel="noopener" data-no-prefetch><x-icon name="print" class="me-1" /> {{ __('Print') }}</a>
    @if ($hasPhone && $me->can('send-whatsapp customers'))
        <button type="button" class="btn btn-outline-soft" data-whatsapp="{{ route('admin.accounts.send-whatsapp', $account->id) }}"><x-icon name="comment-dots" class="me-1" /> {{ __('Send the balance') }}</button>
    @endif
</x-page-header>

<div class="row g-3 mb-3">
    <div class="col-12 col-xl-4">
        <section class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="avatar avatar-lg avatar-soft-primary" aria-hidden="true">{{ initial($account->name) }}</span>
                    <div class="min-w-0">
                        <h2 class="h5 mb-0 text-truncate">{{ $account->name }}</h2>
                        <div class="row-meta"><span dir="ltr">{{ $account->code }}</span><span class="badge-soft primary">{{ $type }}</span>@unless ($account->is_active)<span class="badge-soft secondary">{{ __('ui.deactivated') }}</span>@endunless</div>
                    </div>
                </div>
                <dl class="ex-facts">
                    <div><dt>{{ __('ui.contact') }}</dt><dd>@if ($hasPhone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $account->contact) }}" dir="ltr">{{ $account->contact }}</a>@else — @endif</dd></div>
                    @if ($account->address)<div><dt>{{ __('ui.address') }}</dt><dd>{{ $account->address }}</dd></div>@endif
                    @if ($account->company)<div><dt>{{ __('ui.note') }}</dt><dd>{{ $account->company }}</dd></div>@endif
                    @if ($account->user)<div><dt>{{ __('Customer login') }}</dt><dd dir="ltr">{{ '@'.$account->user->username }}</dd></div>@endif
                </dl>
            </div>
            @unless ($isCash)
            <div class="card-footer st-actions">
                @can('create transactions')<a class="btn btn-sm btn-primary" href="{{ route('admin.transactions.index', ['account_id' => $account->id, 'back' => $back]) }}#txForm">{!! ico('right-left', 'me-1') !!} {{ __('Transaction') }}</a>@endcan
                @can('create exchanges')<a class="btn btn-sm btn-outline-soft" href="{{ route('admin.exchanges.create', ['account_id' => $account->id, 'back' => $back]) }}">{!! ico('coins', 'me-1') !!} {{ __('Exchange') }}</a>@endcan
                @can('create remittances')
                    <a class="btn btn-sm btn-outline-soft" href="{{ route('admin.remittances.createNew', ['account_id' => $account->id, 'type' => 'outgoing', 'back' => $back]) }}">{!! ico('arrow-up-right-from-square', 'me-1') !!} {{ __('ui.outgoing') }}</a>
                    <a class="btn btn-sm btn-outline-soft" href="{{ route('admin.remittances.createNew', ['account_id' => $account->id, 'type' => 'incoming', 'back' => $back]) }}">{!! ico('download', 'me-1') !!} {{ __('ui.incoming') }}</a>
                @endcan
            </div>
            @endunless
        </section>
    </div>
    <div class="col-12 col-xl-8">
        <section class="card h-100" aria-labelledby="stBal">
            <div class="card-header"><h2 class="h5 card-title mb-0" id="stBal">{{ __('Balance now') }}</h2><span class="text-muted small">{{ __('−: they owe us · +: we owe them') }}</span></div>
            <div class="card-body">
                @if ($balances->isEmpty())
                    <p class="text-muted mb-0">{{ __('No balance yet.') }}</p>
                @else
                    <div class="st-balances">
                        @foreach ($balances as $b)
                            @php $v = (float) $b->balance; @endphp
                            <div class="st-balance">
                                <span class="chip-code">{{ $b->currency->code ?? '' }}</span>
                                <strong dir="ltr" class="{{ $v < 0 ? 'is-owed' : ($v > 0 ? 'is-owing' : 'text-muted') }}">{{ $v < 0 ? '−' : '' }}{{ $amountText(abs($v)) }}</strong>
                                <small class="text-muted">{{ $v < 0 ? __('They owe us') : ($v > 0 ? __('We owe them') : __('Settled')) }}</small>
                                <small class="text-muted" dir="ltr">+{{ $amountText($b->credit) }} / −{{ $amountText($b->debit) }}</small>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>

<section class="card" aria-labelledby="stLines">
    <div class="card-header"><h2 class="h5 card-title mb-0" id="stLines">{{ __('ui.account_statement') }}</h2></div>
    <div class="card-body">
        <form method="GET" class="list-tools" role="search" data-live="list">
            <input type="search" class="form-control grow" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Note or reference…') }}" aria-label="{{ __('ui.search') }}">
            <select class="form-select" name="currency_id" aria-label="{{ __('ui.currency') }}" data-plain>
                <option value="">{{ __('All currencies') }}</option>
                @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected($filters['currency_id'] === $c->id)>{{ $c->code }}</option>@endforeach
            </select>
            <select class="form-select" name="type" aria-label="{{ __('ui.type') }}" data-plain>
                <option value="">{{ __('In and out') }}</option>
                <option value="credit" @selected($filters['type'] === 'credit')>{{ __('ui.credit') }} (+)</option>
                <option value="debit" @selected($filters['type'] === 'debit')>{{ __('ui.debit') }} (−)</option>
            </select>
            <select class="form-select" name="color" aria-label="{{ __('Colour') }}" data-plain>
                <option value="">{{ __('Any colour') }}</option>
                <option value="none" @selected($filters['color'] === 'none')>{{ __('No colour') }}</option>
                @foreach ($colors as $i => $color)<option value="{{ $color }}" @selected($filters['color'] === $color)>{{ __('Colour :n', ['n' => $i + 1]) }}</option>@endforeach
            </select>
            <select class="form-select" name="is_ok" aria-label="{{ __('Checked') }}" data-plain>
                <option value="">{{ __('Checked or not') }}</option>
                <option value="1" @selected($filters['is_ok'] === '1')>{{ __('Checked') }}</option>
                <option value="0" @selected($filters['is_ok'] === '0')>{{ __('Not checked') }}</option>
            </select>
            <input type="date" class="form-control" name="from" value="{{ $filters['from'] }}" aria-label="{{ __('From date') }}" title="{{ __('From date') }}">
            <input type="date" class="form-control" name="to" value="{{ $filters['to'] }}" aria-label="{{ __('To date') }}" title="{{ __('To date') }}">
            <a class="btn btn-light-secondary" href="{{ $back }}" data-live-clear title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
        </form>

        @if ($canMark)
        <div class="jr-bulk" id="jrBulk" hidden role="region" aria-label="{{ __('Mark the chosen lines') }}">
            <strong id="jrCount"></strong>
            <span class="text-muted small">{{ __('Mark them:') }}</span>
            @foreach ($colors as $i => $color)<button type="button" class="jr-swatch" style="--sw: {{ $color }}" data-mark-color="{{ $color }}" title="{{ __('Colour :n', ['n' => $i + 1]) }}" aria-label="{{ __('Colour :n', ['n' => $i + 1]) }}"></button>@endforeach
            <button type="button" class="btn btn-sm btn-light-secondary" data-mark-color="">{{ __('No colour') }}</button>
            <button type="button" class="btn btn-sm btn-outline-soft" data-mark-ok="1">{!! ico('check', 'me-1') !!} {{ __('Checked') }}</button>
            <button type="button" class="btn btn-sm btn-light-secondary" data-mark-ok="0">{{ __('Not checked') }}</button>
        </div>
        @endif

@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}">
        @if ($period && $period->isNotEmpty())
            <div class="tx-totals mb-3">
                <span class="tx-totals-label">{{ __('What is shown') }}</span>
                @foreach ($period as $t)
                    @php $net = (float) $t->credit - (float) $t->debit; @endphp
                    <div class="tx-total"><span class="chip-code">{{ $t->code }}</span><span class="amount is-credit" dir="ltr">+{{ $amountText($t->credit) }}</span><span class="amount is-debit" dir="ltr">−{{ $amountText($t->debit) }}</span><strong dir="ltr" class="{{ $net < 0 ? 'is-owed' : 'is-owing' }}">= {{ $net < 0 ? '−' : '' }}{{ $amountText(abs($net)) }}</strong></div>
                @endforeach
            </div>
        @endif
        @if ($rows->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="book-open" /></div>
                <h2 class="h3">{{ $filtered ? __('Nothing found') : __('No line yet') }}</h2>
            </div>
        @else
            <p class="list-legend small text-muted mb-2"><span class="ms-auto">{{ __(':from–:to of :total', ['from' => number_format($rows->firstItem()), 'to' => number_format($rows->lastItem()), 'total' => number_format($rows->total())]) }}</span></p>
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table jr-table">
                    <thead>
                        <tr>
                            @if ($canMark)<th scope="col" class="jr-pick"><input class="form-check-input" type="checkbox" data-pick-all aria-label="{{ __('Choose every line on this page') }}"></th>@endif
                            <th scope="col">{{ __('ui.date') }}</th>
                            <th scope="col">{{ __('ui.note') }}</th>
                            <th scope="col" class="is-num">{{ __('ui.credit') }} (+)</th>
                            <th scope="col" class="is-num">{{ __('ui.debit') }} (−)</th>
                            <th scope="col" class="is-num">{{ __('Balance after') }}</th>
                            <th scope="col" class="text-center">{{ __('Checked') }}</th>
                            <th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php [$tEdit, $tDelete, $tWa, $tPrint, $tFile] = [__('Edit'), __('Delete'), __('Send on WhatsApp'), __('Print'), __('Attachment')]; @endphp
                        @foreach ($rows as $row)
                            @php
                                [$src, $srcLabel, $srcTone] = $sourceOf($row);
                                $in = $row->transaction_type === 'credit';
                                $amount = $row->payment_amount ?? $row->amount;
                                $code = $row->paymentCurrency->code ?? ($row->currency->code ?? '');
                                $after = (float) $row->after_balance;
                                $actual = $row->actual_amount !== null && ((int) $row->actual_currency_id !== (int) ($row->payment_currency_id ?? $row->currency_id) || (float) $row->actual_amount != (float) $amount);
                            @endphp
                            <tr @if ($row->color) style="--mark: {{ $row->color }}" class="jr-marked" @endif>
                                @if ($canMark)<td class="jr-pick"><input class="form-check-input" type="checkbox" value="{{ $row->id }}" data-pick aria-label="{{ __('Choose this line') }}"></td>@endif
                                <td class="text-nowrap">{{ optional($row->created_at)->format('Y-m-d') }}<div class="row-meta">{{ optional($row->created_at)->format('H:i') }}</div></td>
                                <td class="tx-note">
                                    <span class="d-block text-break">{{ \Illuminate\Support\Str::limit((string) $row->note, 160) ?: '—' }}</span>
                                    <span class="badge-soft {{ $srcTone }}">{{ $srcLabel }}</span>@if ($row->reference) <span class="chip-code" dir="ltr">{{ $row->reference }}</span>@endif
                                    @if ($actual)<div class="row-meta">{{ __('Actual') }} <span dir="ltr">{{ $amountText($row->actual_amount) }} {{ $row->actualCurrency->code ?? '' }}</span></div>@endif
                                </td>
                                <td class="is-num amount is-credit" dir="ltr">@if ($in){{ $amountText($amount) }} <small>{{ $code }}</small>@endif</td>
                                <td class="is-num amount is-debit" dir="ltr">@unless ($in){{ $amountText($amount) }} <small>{{ $code }}</small>@endunless</td>
                                <td class="is-num fw-semibold {{ $after < 0 ? 'is-owed' : '' }}" dir="ltr">{{ $after < 0 ? '−' : '' }}{{ $amountText(abs($after)) }} <small class="text-muted">{{ $code }}</small></td>
                                <td class="text-center">@if ($canMark)<input class="form-check-input" type="checkbox" data-ok="{{ $row->id }}" @checked($row->is_ok) aria-label="{{ __('Checked') }}">@elseif ($row->is_ok){!! ico('check', 'text-success') !!}@endif</td>
                                <td class="text-end text-nowrap">
                                    <div class="act-group">
                                        @if ($row->attachment)<a class="btn-act is-teal" href="{{ asset($row->attachment) }}" target="_blank" rel="noopener" title="{{ $tFile }}" aria-label="{{ $tFile }}">{!! ico('paperclip') !!}</a>@endif
                                        @if ($src === 'remittances' && $row->table_row_id)
                                            <a class="btn-act is-blue" href="{{ route($row->remittance_paid_at && $me->can('update remittances') ? 'admin.remittances.payment-receipt' : 'admin.remittances.print', $row->table_row_id) }}" target="_blank" rel="noopener" title="{{ $tPrint }}" aria-label="{{ $tPrint }}" data-no-prefetch>{!! ico('print') !!}</a>
                                        @elseif ($src === 'journal' && strcasecmp((string) $row->table_name, 'Journal') === 0)
                                            <a class="btn-act is-blue" href="{{ route('admin.transactions.print', $row->id) }}" target="_blank" rel="noopener" title="{{ $tPrint }}" aria-label="{{ $tPrint }}" data-no-prefetch>{!! ico('print') !!}</a>
                                        @endif
                                        @if (! empty($row->wa_url))<a class="btn-act is-teal" href="{{ $row->wa_url }}" target="_blank" rel="noopener" title="{{ $tWa }}" aria-label="{{ $tWa }}" data-no-prefetch>{!! ico('comment-dots') !!}</a>@endif
                                        @if ($canMark && $src === 'journal')
                                            <a class="btn-act is-amber" href="{{ route('admin.transactions.index', ['edit' => $row->id, 'back' => $here]) }}#txForm" title="{{ $tEdit }}" aria-label="{{ $tEdit }}" data-no-prefetch>{!! ico('pen') !!}</a>
                                        @elseif ($src === 'exchanges' && $row->table_row_id && $me->can('update exchanges'))
                                            <a class="btn-act is-amber" href="{{ route('admin.exchanges.edit', ['exchange' => $row->table_row_id, 'back' => $here]) }}" title="{{ $tEdit }}" aria-label="{{ $tEdit }}" data-no-prefetch>{!! ico('pen') !!}</a>
                                        @endif
                                        @if ($canDelete)
                                            <form method="POST" action="{{ route('admin.transactions.destroy', $row->id) }}" class="d-inline" data-confirm="{{ __('Delete this transaction?') }}"
                                                  data-confirm-text="{{ $src === 'journal' ? __('Both sides of the entry are deleted and the balances change.') : __('The :what it belongs to is deleted with it, and the balances change.', ['what' => mb_strtolower($srcLabel)]) }}" data-confirm-ok="{{ $tDelete }}">
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // marks and checking, as in the journal
    var bulk = document.getElementById('jrBulk');
    if (bulk) {
        var url = @json(route('admin.transactions.bulk-meta'));
        var token = (document.querySelector('meta[name=csrf-token]') || {}).content;
        var text = {!! json_encode(['chosen' => __(':count chosen'), 'failed' => __('It could not be saved. Try again.')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
        var picked = function () { return Array.prototype.map.call(document.querySelectorAll('[data-pick]:checked'), function (b) { return parseInt(b.value, 10); }); };
        var sync = function () { var ids = picked(); bulk.hidden = ids.length === 0; document.getElementById('jrCount').textContent = text.chosen.replace(':count', ids.length); };
        var send = function (ids, data) {
            data.ids = ids;
            return fetch(url, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify(data) })
                .then(function (r) { return r.json().then(function (d) { if (!r.ok) throw new Error(d.message || text.failed); return d; }); })
                .then(function (d) { if (ERP.toast) ERP.toast(d.message, 'success'); return d; })
                .catch(function (e) { if (ERP.toast) ERP.toast(e.message || text.failed, 'danger'); throw e; });
        };
        document.addEventListener('change', function (e) {
            if (e.target.matches('[data-pick-all]')) document.querySelectorAll('[data-pick]').forEach(function (b) { b.checked = e.target.checked; });
            if (e.target.matches('[data-pick], [data-pick-all]')) sync();
            if (e.target.matches('[data-ok]')) { var box = e.target; send([parseInt(box.getAttribute('data-ok'), 10)], { is_ok: box.checked }).catch(function () { box.checked = !box.checked; }); }
        });
        bulk.addEventListener('click', function (e) {
            var b = e.target.closest('[data-mark-color], [data-mark-ok]'); if (!b) return;
            var data = b.hasAttribute('data-mark-color') ? { color: b.getAttribute('data-mark-color') || null } : { is_ok: b.getAttribute('data-mark-ok') === '1' };
            send(picked(), data).then(function () { bulk.hidden = true; var f = document.querySelector('form[data-live]'); if (f) f.requestSubmit(); }).catch(function () {});
        });
        document.addEventListener('erp:live', sync);
    }
});
// the balance on WhatsApp: the window opens at once and goes to the message when it is ready
document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-whatsapp]'); if (!b) return;
    var win = window.open('about:blank', '_blank');
    fetch(b.getAttribute('data-whatsapp'), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) { var u = res.d.url; if (!res.ok || !u) throw new Error(res.d.error || res.d.message || ''); if (win) win.location = u; else location = u; })
        .catch(function (err) { if (win) win.close(); if (ERP.toast) ERP.toast(err.message || @json(__('It could not be sent.')), 'danger'); });
});
</script>
@endpush
