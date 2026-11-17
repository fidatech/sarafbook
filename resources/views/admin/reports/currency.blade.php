@extends('layouts.admin.base')
@section('title', __('Currency report'))
@section('modern', '1')

{{-- One currency: which accounts brought it in and which took it out, from the stored balances. --}}
@php
    $amountText = fn ($v) => format_amount((float) $v, 2);
    $code = optional($currencies->firstWhere('id', $currencyId))->code;
@endphp

@section('content')
<x-page-header :title="__('Currency report')" icon="coins" :subtitle="__('Who brought each currency in, and who took it out.')">
    <a class="btn btn-outline-soft" href="{{ route('admin.reports.currency.export.pdf', ['currency_id' => $currencyId]) }}" target="_blank" rel="noopener" data-no-prefetch><x-icon name="print" class="me-1" /> {{ __('Print') }}</a>
</x-page-header>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" data-live="list" class="safe-ranges" role="radiogroup" aria-label="{{ __('ui.currency') }}">
            @foreach ($currencies as $c)
                <label class="safe-range"><input type="radio" name="currency_id" value="{{ $c->id }}" @checked($c->id === $currencyId)><span>{{ $c->code }}</span></label>
            @endforeach
        </form>
    </div>
</div>

@fragment('list')
<div data-live-part="list" data-filtered="0">
    <div class="row g-3">
        @foreach ([['credits', __('Brought in (credit)'), 'credit', 'nc', 'is-owing'], ['debits', __('Took out (debit)'), 'debit', 'nd', 'is-owed']] as [$var, $title, $col, $count, $tone])
            <div class="col-12 col-xl-6">
                <section class="card h-100">
                    <div class="card-header d-flex align-items-center">
                        <h2 class="h5 card-title mb-0 me-auto">{{ $title }}</h2>
                        <strong dir="ltr" class="{{ $tone }}">{{ $amountText($sums->{$col} ?? 0) }} {{ $code }}</strong>
                    </div>
                    <div class="card-body">
                        @if (${$var}->isEmpty())
                            <p class="text-muted mb-0">{{ __('Nothing here') }}</p>
                        @else
                            <p class="small text-muted mb-2">{{ __(':count accounts', ['count' => number_format((int) ($sums->{$count} ?? 0))]) }}@if ((int) ($sums->{$count} ?? 0) > ${$var}->count()) · {{ __('the biggest :n shown', ['n' => ${$var}->count()]) }}@endif</p>
                            <div class="table-responsive data-wrap">
                                <table class="table w-100 mb-0 data-table keep-table">
                                    <tbody>
                                        @foreach (${$var} as $row)
                                            <tr>
                                                <td><a class="row-name" href="{{ route('admin.accounts.show', $row->id) }}">{{ $row->name }}</a><div class="row-meta" dir="ltr">{{ $row->code }}</div></td>
                                                <td class="is-num fw-semibold {{ $tone }}" dir="ltr">{{ $amountText($row->amount) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        @endforeach
    </div>
</div>
@endfragment
@endsection
