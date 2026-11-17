@extends('layouts.admin.base')
@section('title', __('ui.journal'))
@section('modern', '1')

{{--
    The journal in the fida-sarafi style: every ledger line, newest first, with a colour mark and
    an OK tick for checking the books (one line or many at once). The position at the top comes
    from the stored balances.
--}}
@php
    $me = auth()->user();
    $canDelete = $me->can('delete journal');
    $canMark = $me->can('update transactions');
    $canPrint = $me->can('print journal');
    $canMessage = $me->can('send-whatsapp journal');
    $amountText = fn ($v) => format_amount((float) $v, 2);
    $printUrl = route('admin.journal.print', array_filter(['account_id' => $filters['account_id'], 'currency_id' => $filters['currency_id'], 'start_date' => $filters['from'] ?: null, 'end_date' => $filters['to'] ?: ($filters['from'] ? now()->toDateString() : null)]));
@endphp

@section('content')
<x-page-header :title="__('ui.journal')" icon="book" :subtitle="__('Every line of the books, newest first.')">
    @if ($canPrint)<a class="btn btn-outline-soft" href="{{ $printUrl }}" target="_blank" rel="noopener" data-no-prefetch id="journalPrint"><x-icon name="print" class="me-1" /> {{ __('Print') }}</a>@endif
    @can('view transactions')<a class="btn btn-primary" href="{{ route('admin.transactions.index') }}#txForm"><x-icon name="plus" class="me-1" /> {{ __('New transaction') }}</a>@endcan
</x-page-header>

@if ($position->isNotEmpty())
<section class="card mb-3" aria-labelledby="posTitle">
    <div class="card-header"><h2 class="h5 card-title mb-0" id="posTitle">{{ __('Where the money is') }}</h2><span class="text-muted small">{{ __('Balances now, by currency') }}</span></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table w-100 mb-0 keep-table acc-totals">
                <thead><tr><th scope="col">{{ __('ui.currency') }}</th><th scope="col" class="text-end">{{ __('Customers') }}</th><th scope="col" class="text-end">{{ __('Sarafs') }}</th><th scope="col" class="text-end">{{ __('In the safe') }}</th></tr></thead>
                <tbody>
                    @foreach ($position as $p)
                        <tr>
                            <th scope="row"><span class="chip-code">{{ $p->code }}</span></th>
                            @foreach (['customers', 'saraf', 'cash'] as $k)
                                @php $v = (float) $p->{$k}; @endphp
                                <td class="text-end text-nowrap fw-semibold" dir="ltr"><span class="{{ $v < 0 ? 'is-owed' : 'is-owing' }}">{{ $v < 0 ? '−' : '' }}{{ $amountText(abs($v)) }}</span></td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endif

<div class="card">
    <div class="card-body">
        <form method="GET" class="list-tools" role="search" data-live="list">
            <input type="search" class="form-control grow" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Account, note or reference…') }}" aria-label="{{ __('ui.search') }}">
            <select class="form-select" name="account_id" aria-label="{{ __('ui.account') }}" data-search>
                <option value="">{{ __('Every account') }}</option>
                @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected($filters['account_id'] === $a->id)>{{ $a->code }} · {{ $a->name }}</option>@endforeach
            </select>
            <select class="form-select" name="currency_id" aria-label="{{ __('ui.currency') }}" data-plain>
                <option value="">{{ __('All currencies') }}</option>
                @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected($filters['currency_id'] === $c->id)>{{ $c->code }}</option>@endforeach
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
            <a class="btn btn-light-secondary" href="{{ route('admin.journal.index') }}" data-live-clear title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
        </form>

        @if ($canMark)
        <div class="jr-bulk" id="jrBulk" hidden role="region" aria-label="{{ __('Mark the chosen lines') }}">
            <strong id="jrCount"></strong>
            <span class="text-muted small">{{ __('Mark them:') }}</span>
            @foreach ($colors as $i => $color)
                <button type="button" class="jr-swatch" style="--sw: {{ $color }}" data-mark-color="{{ $color }}" title="{{ __('Colour :n', ['n' => $i + 1]) }}" aria-label="{{ __('Colour :n', ['n' => $i + 1]) }}"></button>
            @endforeach
            <button type="button" class="btn btn-sm btn-light-secondary" data-mark-color="">{{ __('No colour') }}</button>
            <button type="button" class="btn btn-sm btn-outline-soft" data-mark-ok="1">{!! ico('check', 'me-1') !!} {{ __('Checked') }}</button>
            <button type="button" class="btn btn-sm btn-light-secondary" data-mark-ok="0">{{ __('Not checked') }}</button>
        </div>
        @endif

@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}">
        @if ($rows->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="book" /></div>
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
                            <th scope="col">{{ __('ui.account') }}</th>
                            <th scope="col">{{ __('ui.note') }}</th>
                            <th scope="col" class="is-num">{{ __('ui.amount') }}</th>
                            <th scope="col" class="text-center">{{ __('Checked') }}</th>
                            <th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php [$tDelete, $tFile, $tWa, $tStatement] = [__('Delete'), __('Attachment'), __('Send on WhatsApp'), __('Account statement')]; @endphp
                        @foreach ($rows as $row)
                            @php $in = $row->transaction_type === 'credit'; @endphp
                            <tr @if ($row->color) style="--mark: {{ $row->color }}" class="jr-marked" @endif>
                                @if ($canMark)<td class="jr-pick"><input class="form-check-input" type="checkbox" value="{{ $row->id }}" data-pick aria-label="{{ __('Choose this line') }}"></td>@endif
                                <td class="text-nowrap">{{ optional($row->created_at)->format('Y-m-d') }}<div class="row-meta">{{ optional($row->created_at)->format('H:i') }}</div></td>
                                <td>
                                    @if ($row->account)
                                        <a class="row-name" href="{{ route('admin.accounts.show', $row->account_id) }}" title="{{ $tStatement }}">{{ $row->account->name }}</a>
                                        <div class="row-meta"><span dir="ltr">{{ $row->account->code }}</span>@unless ($row->is_visible)<span class="badge-soft secondary">{{ __('Safe side') }}</span>@endunless</div>
                                    @else — @endif
                                </td>
                                <td class="tx-note"><span class="d-block text-break">{{ \Illuminate\Support\Str::limit((string) $row->note, 140) ?: '—' }}</span>@if ($row->reference)<span class="chip-code" dir="ltr">{{ $row->reference }}</span>@endif</td>
                                <td class="is-num"><span class="amount {{ $in ? 'is-credit' : 'is-debit' }}" dir="ltr">{{ $in ? '+' : '−' }}{{ $amountText($row->amount) }} <small>{{ $row->currency->code ?? '' }}</small></span></td>
                                <td class="text-center">
                                    @if ($canMark)
                                        <input class="form-check-input" type="checkbox" data-ok="{{ $row->id }}" @checked($row->is_ok) aria-label="{{ __('Checked') }}">
                                    @elseif ($row->is_ok){!! ico('check', 'text-success') !!}@endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="act-group">
                                        @if ($row->attachment)<a class="btn-act is-teal" href="{{ asset($row->attachment) }}" target="_blank" rel="noopener" title="{{ $tFile }}" aria-label="{{ $tFile }}">{!! ico('paperclip') !!}</a>@endif
                                        @if ($canMessage && $row->account && (int) $row->account->account_sub_category_id !== 3 && strlen(preg_replace('/\D/', '', (string) $row->account->contact)) >= 6)
                                            <a class="btn-act is-teal" href="{{ route('admin.journal.send-whatsapp', $row->id) }}" target="_blank" rel="noopener" title="{{ $tWa }}" aria-label="{{ $tWa }}" data-no-prefetch>{!! ico('comment-dots') !!}</a>
                                        @endif
                                        @if ($canDelete)
                                            <form method="POST" action="{{ route('admin.journal.destroy', $row->id) }}" class="d-inline" data-confirm="{{ __('Delete this transaction?') }}" data-confirm-text="{{ __('The lines that belong together are deleted, and the balances change.') }}" data-confirm-ok="{{ $tDelete }}">
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
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var bulk = document.getElementById('jrBulk');
    if (!bulk) return;
    var url = @json(route('admin.transactions.bulk-meta'));
    var token = (document.querySelector('meta[name=csrf-token]') || {}).content;
    var text = {!! json_encode(['chosen' => __(':count chosen'), 'failed' => __('It could not be saved. Try again.')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var picked = function () { return Array.prototype.map.call(document.querySelectorAll('[data-pick]:checked'), function (b) { return parseInt(b.value, 10); }); };
    var sync = function () {
        var ids = picked();
        bulk.hidden = ids.length === 0;
        document.getElementById('jrCount').textContent = text.chosen.replace(':count', ids.length);
    };
    var send = function (ids, data) {
        data.ids = ids;
        return fetch(url, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify(data) })
            .then(function (r) { return r.json().then(function (d) { if (!r.ok) throw new Error(d.message || text.failed); return d; }); })
            .then(function (d) { if (ERP.toast) ERP.toast(d.message, 'success'); return d; })
            .catch(function (e) { if (ERP.toast) ERP.toast(e.message || text.failed, 'danger'); throw e; });
    };
    // the list is drawn again after a mark, through the filter form
    var redraw = function () { var f = document.querySelector('form[data-live]'); if (f) f.requestSubmit(); };
    document.addEventListener('change', function (e) {
        if (e.target.matches('[data-pick-all]')) document.querySelectorAll('[data-pick]').forEach(function (b) { b.checked = e.target.checked; });
        if (e.target.matches('[data-pick], [data-pick-all]')) sync();
        if (e.target.matches('[data-ok]')) {
            var box = e.target;
            send([parseInt(box.getAttribute('data-ok'), 10)], { is_ok: box.checked }).catch(function () { box.checked = !box.checked; });
        }
    });
    bulk.addEventListener('click', function (e) {
        var b = e.target.closest('[data-mark-color], [data-mark-ok]');
        if (!b) return;
        var data = b.hasAttribute('data-mark-color') ? { color: b.getAttribute('data-mark-color') || null } : { is_ok: b.getAttribute('data-mark-ok') === '1' };
        send(picked(), data).then(function () { bulk.hidden = true; redraw(); }).catch(function () {});
    });
    document.addEventListener('erp:live', sync);
});
// the print button follows the filters on the screen
document.addEventListener('erp:live', function () {
    var a = document.getElementById('journalPrint'); if (!a) return;
    var now = new URLSearchParams(location.search), out = new URL(a.href);
    ['account_id', 'currency_id'].forEach(function (k) { if (now.get(k)) out.searchParams.set(k, now.get(k)); else out.searchParams.delete(k); });
    if (now.get('from')) { out.searchParams.set('start_date', now.get('from')); out.searchParams.set('end_date', now.get('to') || new Date().toISOString().slice(0, 10)); }
    else { out.searchParams.delete('start_date'); out.searchParams.delete('end_date'); }
    a.href = out.toString();
});
</script>
@endpush
