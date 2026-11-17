@extends('layouts.admin.base')
@section('title', $pageTitle)
@section('modern', '1')

{{--
    The remittance lists (outgoing, incoming paid, incoming waiting to be paid) in the fida-sarafi
    style: one page, filtered on the server, with the totals by currency of what is shown. A
    waiting remittance is paid from here (the payment desk), rejected, or deleted.
--}}
@php
    $me = auth()->user();
    $canUpdate = $me->can('update remittances');
    $canDelete = $me->can('delete remittances');
    $canCreate = $me->can('create remittances');
    $amountText = fn ($v) => format_amount((float) $v, 2);
    $codes = $currencies->pluck('code', 'id');
    $tabs = [
        'outgoing' => [__('ui.outgoing_remittances'), route('admin.remittances.outgoing'), 'arrow-up-right-from-square'],
        'pendingIncoming' => [__('ui.pending_incoming_remittances'), route('admin.remittances.pendingIncoming'), 'hourglass-half'],
        'incomingPaid' => [__('ui.incoming_paid_remittances'), route('admin.remittances.incomingPaid'), 'circle-check'],
    ];
    $statusTone = ['paid' => 'success', 'pending' => 'warning', 'rejected' => 'danger'];
    $statusText = ['paid' => __('Paid'), 'pending' => __('Waiting'), 'rejected' => __('Rejected')];
    $commissionText = function ($r) {
        $cc = $r->commissionCurrency->code ?? '';
        return match ($r->commission_mode) {
            'company' => __('Ours').' '.format_amount((float) $r->commission_kabul_amount, 2).' '.$cc,
            'pay' => __('Theirs').' '.format_amount((float) $r->commission_partner_amount, 2).' '.$cc,
            'split' => __('Ours').' '.format_amount((float) $r->commission_kabul_amount, 2).' · '.__('Theirs').' '.format_amount((float) $r->commission_partner_amount, 2).' '.$cc,
            default => null,
        };
    };
@endphp

@section('content')
<x-page-header :title="$pageTitle" icon="paper-plane" :subtitle="__('Remittances sent and received through other Sarafs.')">
    @if ($canCreate)<a class="btn btn-primary" href="{{ route('admin.remittances.createNew', $presetType === 'incoming' ? ['type' => 'incoming'] : []) }}"><x-icon name="plus" class="me-1" /> {{ __('New remittance') }}</a>@endif
</x-page-header>

<nav class="rm-tabs mb-3" aria-label="{{ __('Remittances') }}">
    @foreach ($tabs as $key => [$label, $url, $icon])
        <a href="{{ $url }}" @class(['rm-tab', 'is-active' => $key === $kind]) @if ($key === $kind) aria-current="page" @endif>{!! ico($icon, 'me-1') !!} {{ $label }}</a>
    @endforeach
</nav>

<div class="card">
    <div class="card-body">
        <form method="GET" class="list-tools" role="search" data-live="list,totals">
            <input type="search" class="form-control grow" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Number, sender, receiver or Saraf…') }}" aria-label="{{ __('ui.search') }}">
            <select class="form-select" name="account_id" aria-label="{{ __('Saraf') }}" data-search>
                <option value="">{{ __('Every Saraf') }}</option>
                @foreach ($sarafs as $s)<option value="{{ $s->id }}" @selected($filters['account_id'] === $s->id)>{{ $s->code }} · {{ $s->name }}</option>@endforeach
            </select>
            <select class="form-select" name="currency_id" aria-label="{{ __('ui.currency') }}" data-plain>
                <option value="">{{ __('All currencies') }}</option>
                @foreach ($currencies as $c)<option value="{{ $c->id }}" @selected($filters['currency_id'] === $c->id)>{{ $c->code }}</option>@endforeach
            </select>
            @if ($presetType === 'outgoing')
                <select class="form-select" name="status" aria-label="{{ __('ui.status') }}" data-plain>
                    <option value="">{{ __('Any status') }}</option>
                    @foreach ($statusText as $k => $t)<option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $t }}</option>@endforeach
                </select>
            @endif
            <input type="date" class="form-control" name="from" value="{{ $filters['from'] }}" aria-label="{{ __('From date') }}" title="{{ __('From date') }}">
            <input type="date" class="form-control" name="to" value="{{ $filters['to'] }}" aria-label="{{ __('To date') }}" title="{{ __('To date') }}">
            <a class="btn btn-light-secondary" href="{{ url()->current() }}" data-live-clear title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
        </form>

@fragment('totals')
<div data-live-part="totals">
        @if ($totals->isNotEmpty())
            <div class="tx-totals mb-3">
                <span class="tx-totals-label">{{ $partnerSide ? __('To pay') : __('Total') }}</span>
                @foreach ($totals as $t)
                    <div class="tx-total"><span class="chip-code">{{ $codes[$t->cid] ?? '' }}</span><strong dir="ltr">{{ $amountText($t->total) }}</strong><span class="text-muted small">{{ __(':count remittances', ['count' => $t->n]) }}</span></div>
                @endforeach
            </div>
        @endif
</div>
@endfragment

@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}">
        @if ($rows->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="paper-plane" /></div>
                <h2 class="h3">{{ $filtered ? __('Nothing found') : ($kind === 'pendingIncoming' ? __('Nothing is waiting to be paid') : __('No remittance yet')) }}</h2>
            </div>
        @else
            <p class="list-legend small text-muted mb-2"><span class="ms-auto">{{ __(':from–:to of :total', ['from' => number_format($rows->firstItem()), 'to' => number_format($rows->lastItem()), 'total' => number_format($rows->total())]) }}</span></p>
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ui.date') }}</th>
                            <th scope="col">{{ __('Saraf') }}</th>
                            <th scope="col">{{ __('ui.sender') }} → {{ __('ui.receiver') }}</th>
                            <th scope="col" class="is-num">{{ __('ui.amount') }}</th>
                            <th scope="col">{{ __('ui.status') }}</th>
                            <th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            @php
                                $saraf = $r->type === 'outgoing' ? $r->toAccount : $r->fromAccount;
                                $show = $partnerSide ? [$r->partner_amount, $r->partnerCurrency->code ?? ''] : [$r->amount, $r->currency->code ?? ''];
                                $other = ! $partnerSide && $r->partner_amount && ((int) $r->partner_currency_id !== (int) $r->currency_id || (float) $r->partner_amount != (float) $r->amount);
                                $canPay = $r->status === 'pending' && $r->type === 'incoming' && $canUpdate;
                                $com = $commissionText($r);
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ optional($r->date)->format('Y-m-d') }}<div class="row-meta">{{ optional($r->created_at)->format('H:i') }}@if ($r->hawala_number) · <span dir="ltr">#{{ $r->hawala_number }}</span>@endif</div></td>
                                <td>
                                    @if ($saraf)<a class="row-name" href="{{ route('admin.accounts.show', $saraf->id) }}">{{ $saraf->name }}</a><div class="row-meta" dir="ltr">{{ $saraf->code }}</div>@else — @endif
                                    @if ($r->trackAccount)<div class="row-meta">↔ {{ $r->trackAccount->name }}@if ($r->track_hawala_number) <span dir="ltr">#{{ $r->track_hawala_number }}</span>@endif</div>@endif
                                </td>
                                <td><span>{{ $r->sender ?: '—' }}</span> <span class="text-muted">→</span> <span>{{ $r->receiver ?: '—' }}</span>@if ($r->description)<div class="row-meta">{{ \Illuminate\Support\Str::limit($r->description, 80) }}</div>@endif</td>
                                <td class="is-num">
                                    <span class="fw-bold" dir="ltr">{{ $amountText($show[0]) }} <small class="text-muted">{{ $show[1] }}</small></span>
                                    @if ($other)<div class="row-meta justify-content-end" dir="ltr">{{ __('partner') }} {{ $amountText($r->partner_amount) }} {{ $r->partnerCurrency->code ?? '' }}</div>@endif
                                    @if ($com)<div class="row-meta justify-content-end">{{ $com }}</div>@endif
                                </td>
                                <td><span class="badge-soft {{ $statusTone[$r->status] ?? 'secondary' }}"><span class="dot"></span>{{ $statusText[$r->status] ?? $r->status }}</span></td>
                                <td class="text-end text-nowrap">
                                    <div class="act-group">
                                        @if ($canPay)
                                            <a class="btn btn-sm btn-primary" href="{{ route('admin.remittances.payment', $r) }}">{!! ico('hand-holding-dollar', 'me-1') !!} {{ __('Pay') }}</a>
                                        @endif
                                        <a class="btn-act is-blue" href="{{ route($r->paid_at && $canUpdate ? 'admin.remittances.payment-receipt' : 'admin.remittances.print', $r->id) }}" target="_blank" rel="noopener" title="{{ __('Print') }}" aria-label="{{ __('Print') }}" data-no-prefetch>{!! ico('print') !!}</a>
                                        <div class="dropdown d-inline-block">
                                            <button class="btn-act is-gray" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('More') }}" aria-label="{{ __('More') }}">{!! ico('ellipsis-vertical') !!}</button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><button class="dropdown-item" type="button" data-whatsapp="{{ route('admin.remittances.send-whatsapp', $r->id) }}">{!! ico('comment-dots', 'me-2 text-success') !!} {{ __('Send on WhatsApp') }}</button></li>
                                                @if ($r->paid_at && $canUpdate)
                                                    <li><a class="dropdown-item" href="{{ route('admin.remittances.payment', $r) }}">{!! ico('user-check', 'me-2') !!} {{ __('remittance_payment.record') }}</a></li>
                                                @endif
                                                @if ($r->status !== 'rejected' && ! $r->paid_at && $canUpdate)
                                                    <li>
                                                        <form method="POST" action="{{ route('admin.remittances.reject', $r->id) }}" data-confirm="{{ __('Reject this remittance?') }}" data-confirm-text="{{ __('Its lines are reversed and it stays in the list as rejected.') }}" data-confirm-ok="{{ __('Reject') }}">@csrf
                                                            <button class="dropdown-item text-warning" type="submit">{!! ico('ban', 'me-2') !!} {{ __('Reject') }}</button>
                                                        </form>
                                                    </li>
                                                @endif
                                                @if (! $r->paid_at && $canDelete)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('admin.remittances.destroy', $r->id) }}" data-confirm="{{ __('Delete this remittance?') }}" data-confirm-text="{{ __('Its lines in the accounts and the safe are deleted too, and the balances change.') }}" data-confirm-ok="{{ __('Delete') }}">
                                                            @csrf @method('DELETE')
                                                            <button class="dropdown-item text-danger" type="submit">{!! ico('trash', 'me-2') !!} {{ __('Delete') }}</button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
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
// WhatsApp: the window opens at once (so it is not blocked) and goes to the message when it is ready
document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-whatsapp]');
    if (!b) return;
    var win = window.open('about:blank', '_blank');
    fetch(b.getAttribute('data-whatsapp'), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
            var url = res.d.url || res.d.whatsapp_url;
            if (!res.ok || !url) throw new Error(res.d.error || res.d.message || '');
            if (win) win.location = url; else location = url;
        })
        .catch(function (err) { if (win) win.close(); if (ERP.toast) ERP.toast(err.message || @json(__('It could not be sent.')), 'danger'); });
});
</script>
@endpush
