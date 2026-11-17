@extends('layouts.admin.base')
@section('title', __('Account types'))
@section('modern', '1')

{{-- Account types (Customer, Saraf, Safe …) under the categories. A type with accounts cannot be deleted. --}}
@php
    $canCreate = auth()->user()->can('create account sub categories');
    $canUpdate = auth()->user()->can('update account sub categories');
    $canDelete = auth()->user()->can('delete account sub categories');
    $filtered = $filters['q'] !== '' || $filters['category_id'];
    $categoryOptions = $categories->pluck('name', 'id')->all();
@endphp

@section('content')
<x-page-header :title="__('Account types')" icon="tags" :subtitle="__('The kinds of account under each category.')">
    <a class="btn btn-outline-soft" href="{{ route('admin.account-categories.index') }}"><x-icon name="folder-tree" class="me-1" /> {{ __('ui.account_categories') }}</a>
    @if ($canCreate)
        <button class="btn btn-primary" type="button" data-modal-form="#typeForm"><x-icon name="plus" class="me-1" /> {{ __('Add account type') }}</button>
    @endif
</x-page-header>

<div class="card">
    <div class="card-body">
        <form method="GET" class="list-tools" role="search" data-live="list">
            <input type="search" class="form-control grow" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Search…') }}" aria-label="{{ __('ui.search') }}">
            <select class="form-select" name="category_id" aria-label="{{ __('ui.category') }}">
                <option value="">{{ __('All categories') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category_id'] === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <a class="btn btn-light-secondary" href="{{ route('admin.account-sub-categories.index') }}" data-live-clear title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
        </form>

@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}">
        @if ($subCategories->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="tags" /></div>
                <h2 class="h3">{{ $filtered ? __('Nothing found') : __('No account type yet') }}</h2>
            </div>
        @else
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ui.name') }}</th>
                            <th scope="col">{{ __('ui.category') }}</th>
                            <th scope="col" class="is-num">{{ __('ui.accounts') }}</th>
                            <th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subCategories as $type)
                            <tr>
                                <td><span class="row-name">{{ __('accounts.types')[$type->name] ?? $type->name }}</span>@if ((__('accounts.types')[$type->name] ?? $type->name) !== $type->name)<div class="row-meta" dir="ltr">{{ $type->name }}</div>@endif</td>
                                <td>{{ $type->accountCategory->name ?? '—' }} <span class="chip-code" dir="ltr">{{ $type->accountCategory->code_prefix ?? '' }}</span></td>
                                <td class="is-num"><a href="{{ route('admin.accounts.index', ['type' => $type->id]) }}">{{ number_format($type->accounts_count) }}</a></td>
                                <td class="text-end text-nowrap">
                                    <div class="act-group">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-act is-amber" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }} {{ $type->name }}" data-modal-form="#typeForm"
                                                    data-fill="{{ json_encode(['name' => $type->name, 'account_category_id' => $type->account_category_id], JSON_UNESCAPED_UNICODE) }}"
                                                    data-action="{{ route('admin.account-sub-categories.update', $type->id) }}" data-method="PUT">{!! ico('pen') !!}</button>
                                        @endif
                                        @if ($canDelete && $type->accounts_count === 0)
                                            <form method="POST" action="{{ route('admin.account-sub-categories.destroy', $type->id) }}" class="d-inline" data-confirm="{{ __('Delete :name?', ['name' => $type->name]) }}" data-confirm-ok="{{ __('Delete') }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-act is-red" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }} {{ $type->name }}">{!! ico('trash') !!}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-3 mb-0">{{ __('A type that has accounts cannot be deleted, so no account is lost by mistake.') }}</p>
        @endif
</div>
@endfragment
    </div>
</div>

@if ($canCreate || $canUpdate)
<x-form-modal id="typeForm" :title="__('Add account type')" :title-edit="__('Edit account type')" :action="route('admin.account-sub-categories.store')">
    <x-field modal="typeForm" name="name" :label="__('ui.name')" required maxlength="255" />
    <x-field modal="typeForm" name="account_category_id" type="select" :label="__('ui.category')" :options="$categoryOptions" :empty="__('Choose…')" required />
</x-form-modal>
@endif
@endsection
