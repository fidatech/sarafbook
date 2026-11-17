@extends('layouts.admin.base')
@section('title', __('Exchange #:id', ['id' => $exchange->id]))
@section('modern', '1')

{{-- One exchange: what was given and taken, the rate, the profit, and the lines it made in the books. --}}
@php
    $x = $exchange;
    $me = auth()->user();
    $amountText = fn ($v) => format_amount((float) $v, 2);
    $rateText = fn ($v) => rtrim(rtrim(number_format((float) $v, 6, '.', ','), '0'), '.');
    $modes = ['cash' => __('Walk-in (cash)'), 'customer' => __('A customer'), 'saraf' => __('A Saraf'), 'no_track' => __('Inside the account (both currencies on it)'), 'treasure' => __('Through the safe (cash in and out)'), 'track' => __('To a second account')];
@endphp

@section('content')
<x-page-header :title="__('Exchange #:id', ['id' => $x->id])" icon="coins" :crumbs="[[__('ui.exchanges'), route('admin.exchanges.index')], ['#'.$x->id]]">
    @if ($me->can('update exchanges'))<a class="btn btn-outline-soft" href="{{ route('admin.exchanges.edit', $x->id) }}"><x-icon name="pen" class="me-1" /> {{ __('Change') }}</a>@endif
    @if ($me->can('delete exchanges'))
        <form method="POST" action="{{ route('admin.exchanges.destroy', $x->id) }}" data-confirm="{{ __('Delete this exchange?') }}" data-confirm-text="{{ __('Its lines in the accounts and the safe are deleted too, and the balances change.') }}" data-confirm-ok="{{ __('Delete') }}">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-light-secondary text-danger"><x-icon name="trash" class="me-1" /> {{ __('Delete') }}</button>
        </form>
    @endif
</x-page-header>

<div class="row g-3">
    <div class="col-12 col-xl-5">
        <section class="card h-100">
            <div class="card-body">
                <div class="ex-show">
                    <div><small>{{ __('They gave us') }}</small><strong class="is-owed" dir="ltr">{{ $amountText($x->from_amount) }} {{ $x->fromCurrency->code ?? '' }}</strong></div>
                    <div class="ex-show-rate" dir="ltr">{{ ($x->operation_type ?? 'multiply') === 'divide' ? '÷' : '×' }} {{ $rateText($x->rate) }}</div>
                    <div><small>{{ __('We gave them') }}</small><strong class="is-owing" dir="ltr">{{ $amountText($x->to_amount) }} {{ $x->toCurrency->code ?? '' }}</strong></div>
                </div>
                <dl class="ex-facts">
                    <div><dt>{{ __('ui.date') }}</dt><dd>{{ \Illuminate\Support\Carbon::parse($x->date)->format('Y-m-d') }}</dd></div>
                    <div><dt>{{ __('With') }}</dt><dd>@if ($x->mode === 'cash' || ! $x->account){{ __('Walk-in (cash)') }}@else<a href="{{ route('admin.accounts.show', $x->account_id) }}">{{ $x->account->name }}</a> <span class="chip-code" dir="ltr">{{ $x->account->code }}</span>@endif</dd></div>
                    <div><dt>{{ __('How') }}</dt><dd>{{ $modes[$x->mode] ?? $x->mode }}</dd></div>
                    <div><dt>{{ __('Rate of the day') }}</dt><dd dir="ltr">{{ $x->base_rate ? $rateText($x->base_rate) : '—' }}</dd></div>
                    <div><dt>{{ __('Profit') }}</dt><dd dir="ltr" class="{{ $x->profit < 0 ? 'is-owed' : 'is-owing' }} fw-semibold">{{ (float) $x->profit == 0 ? '—' : ($x->profit < 0 ? '−' : '+').$amountText(abs($x->profit)).' '.($x->profitCurrency->code ?? '') }}</dd></div>
                    <div><dt>{{ __('In US dollars') }}</dt><dd dir="ltr">{{ $amountText($x->usd_amount) }} USD</dd></div>
                    <div><dt>{{ __('By') }}</dt><dd>{{ $creator->name ?? '—' }} <small class="text-muted">{{ optional($x->created_at)->format('Y-m-d H:i') }}</small></dd></div>
                    @if ($x->note)<div><dt>{{ __('ui.note') }}</dt><dd>{{ $x->note }}</dd></div>@endif
                </dl>
            </div>
        </section>
    </div>
    <div class="col-12 col-xl-7">
        <section class="card h-100" aria-labelledby="exLines">
            <div class="card-header"><h2 class="h5 card-title mb-0" id="exLines">{{ __('Lines in the books') }}</h2></div>
            <div class="card-body">
                @if ($lines->isEmpty())
                    <p class="text-muted mb-0">{{ __('No line was found for this exchange.') }}</p>
                @else
                    <div class="table-responsive data-wrap">
                        <table class="table w-100 mb-0 data-table">
                            <thead><tr><th scope="col">{{ __('ui.account') }}</th><th scope="col" class="is-num">{{ __('ui.amount') }}</th></tr></thead>
                            <tbody>
                                @foreach ($lines as $line)
                                    @php $in = $line->transaction_type === 'credit'; @endphp
                                    <tr>
                                        <td>@if ($line->account)<a class="row-name" href="{{ route('admin.accounts.show', $line->account_id) }}">{{ $line->account->name }}</a><div class="row-meta"><span dir="ltr">{{ $line->account->code }}</span>@if ($line->category === 'exchange_profit')<span class="badge-soft success">{{ __('Profit') }}</span>@endif</div>@else — @endif</td>
                                        <td class="is-num"><span class="amount {{ $in ? 'is-credit' : 'is-debit' }}" dir="ltr">{{ $in ? '+' : '−' }}{{ $amountText($line->amount) }} <small>{{ $line->currency->code ?? '' }}</small></span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
