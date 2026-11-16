@extends('layouts.platform')
@section('title', __('Plans'))

@section('content')
<x-page-header :title="__('Plans')" icon="layer-group" :crumbs="[[__('Platform'), route('platform.dashboard')], [__('Plans')]]" :subtitle="__('What each plan costs for a year and what it includes.')">
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#plan-new"><x-icon name="plus" /> {{ __('New plan') }}</button>
</x-page-header>

<div class="row g-3">
    @forelse ($plans as $plan)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2"><h2 class="h5 mb-1">{{ $plan->name }}</h2>@unless ($plan->is_active)<span class="badge-soft secondary">{{ __('Hidden') }}</span>@endunless</div>
                    <p class="h4 mb-0" dir="ltr">{{ number_format((float) $plan->price_yearly) }} <small class="text-muted fs-6">{{ $plan->currency }} / {{ __('year') }}</small></p>
                    <p class="text-muted small">{{ __('Setup') }}: <span dir="ltr">{{ number_format((float) $plan->setup_fee) }} {{ $plan->currency }}</span> · {{ __('Users') }}: {{ $plan->max_users ?? __('unlimited') }} · {{ __('Sarafis') }}: {{ $plan->tenants_count }}</p>
                    <ul class="list-unstyled small mb-0">
                        @foreach ($features as $key => $label)
                            <li class="{{ ! empty($plan->features[$key]) ? '' : 'text-muted' }}"><x-icon :name="! empty($plan->features[$key]) ? 'circle-check' : 'circle-xmark'" class="{{ ! empty($plan->features[$key]) ? 'text-success' : '' }}" /> {{ __($label) }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-footer text-end"><button class="btn btn-sm btn-soft-primary" type="button" data-bs-toggle="modal" data-bs-target="#plan-{{ $plan->id }}"><x-icon name="pen" /> {{ __('Edit') }}</button></div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">{{ __('No plan yet. Add the plans you sell, with your own prices.') }}</div></div></div>
    @endforelse
</div>

@foreach ($plans->concat([null]) as $plan)
    @php $id = $plan?->id ?? 'new'; @endphp
    <div class="modal fade" id="plan-{{ $id }}" tabindex="-1" aria-labelledby="plan-{{ $id }}-title" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="{{ $plan ? route('platform.plans.update', $plan) : route('platform.plans.store') }}">
                @csrf @if ($plan) @method('put') @endif
                <div class="modal-header"><h2 class="modal-title h5" id="plan-{{ $id }}-title">{{ $plan ? $plan->name : __('New plan') }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-7"><label class="form-label" for="p-name-{{ $id }}">{{ __('Name') }}</label><input class="form-control" id="p-name-{{ $id }}" name="name" value="{{ $plan?->name }}" maxlength="80" required></div>
                        <div class="col-5"><label class="form-label" for="p-slug-{{ $id }}">{{ __('Short name') }}</label><input class="form-control" id="p-slug-{{ $id }}" name="slug" value="{{ $plan?->slug }}" dir="ltr" pattern="[a-z0-9-]+" maxlength="40" required></div>
                        <div class="col-5"><label class="form-label" for="p-price-{{ $id }}">{{ __('Price for a year') }}</label><input class="form-control" id="p-price-{{ $id }}" name="price_yearly" value="{{ $plan?->price_yearly }}" inputmode="decimal" dir="ltr" required></div>
                        <div class="col-4"><label class="form-label" for="p-setup-{{ $id }}">{{ __('Setup fee') }}</label><input class="form-control" id="p-setup-{{ $id }}" name="setup_fee" value="{{ $plan?->setup_fee }}" inputmode="decimal" dir="ltr"></div>
                        <div class="col-3"><label class="form-label" for="p-cur-{{ $id }}">{{ __('Currency') }}</label><select class="form-select" id="p-cur-{{ $id }}" name="currency">@foreach (['AFN', 'USD'] as $c)<option @selected(($plan?->currency ?? 'AFN') === $c)>{{ $c }}</option>@endforeach</select></div>
                        <div class="col-6"><label class="form-label" for="p-users-{{ $id }}">{{ __('Users (empty: unlimited)') }}</label><input class="form-control" id="p-users-{{ $id }}" name="max_users" value="{{ $plan?->max_users }}" inputmode="numeric" dir="ltr"></div>
                        <div class="col-6"><label class="form-label" for="p-sort-{{ $id }}">{{ __('Order in lists') }}</label><input class="form-control" id="p-sort-{{ $id }}" name="sort" value="{{ $plan?->sort ?? 0 }}" inputmode="numeric" dir="ltr"></div>
                        <div class="col-12">
                            <fieldset><legend class="form-label">{{ __('Included') }}</legend>
                                @foreach ($features as $key => $label)
                                    <div class="form-check"><input class="form-check-input" type="checkbox" id="p-{{ $id }}-{{ $key }}" name="features[{{ $key }}]" value="1" @checked($plan ? ! empty($plan->features[$key]) : true)><label class="form-check-label" for="p-{{ $id }}-{{ $key }}">{{ __($label) }}</label></div>
                                @endforeach
                            </fieldset>
                        </div>
                        <div class="col-12"><input type="hidden" name="is_active" value="0"><div class="form-check"><input class="form-check-input" type="checkbox" id="p-active-{{ $id }}" name="is_active" value="1" @checked($plan?->is_active ?? true)><label class="form-check-label" for="p-active-{{ $id }}">{{ __('Offered to new Sarafis') }}</label></div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('Save') }}</button></div>
            </form>
        </div>
    </div>
@endforeach
@endsection
