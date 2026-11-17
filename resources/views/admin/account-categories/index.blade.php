@extends('layouts.admin.base')
@section('title', __('ui.account_categories'))
@section('modern', '1')

{{-- Account categories (Customers, Sarafs, Cash …): the code prefix starts every account code of the category. --}}
@php
    $canCreate = auth()->user()->can('create account categories');
    $canUpdate = auth()->user()->can('update account categories');
    $canDelete = auth()->user()->can('delete account categories');
@endphp

@section('content')
<x-page-header :title="__('ui.account_categories')" icon="folder-tree" :subtitle="__('The groups accounts belong to, and the prefix of their codes.')">
    <a class="btn btn-outline-soft" href="{{ route('admin.account-sub-categories.index') }}"><x-icon name="tags" class="me-1" /> {{ __('Account types') }}</a>
    @if ($canCreate)
        <button class="btn btn-primary" type="button" data-modal-form="#categoryForm"><x-icon name="plus" class="me-1" /> {{ __('Add category') }}</button>
    @endif
</x-page-header>

<div class="card">
    <div class="card-body">
        @if ($categories->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="folder-tree" /></div>
                <h2 class="h3">{{ __('No category yet') }}</h2>
            </div>
        @else
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ui.name') }}</th>
                            <th scope="col">{{ __('ui.code_prefix') }}</th>
                            <th scope="col" class="is-num">{{ __('Account types') }}</th>
                            <th scope="col" class="is-num">{{ __('ui.accounts') }}</th>
                            <th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            @php $count = (int) ($accounts[$category->id] ?? 0); @endphp
                            <tr>
                                <td>
                                    <div class="row-who">
                                        <span class="coin" style="{{ $category->bi_icon_color ? 'color:'.e($category->bi_icon_color).';background:color-mix(in srgb,'.e($category->bi_icon_color).' 14%,transparent)' : '' }}" aria-hidden="true">{{ initial($category->name) }}</span>
                                        <span class="row-name">{{ $category->name }}</span>
                                    </div>
                                </td>
                                <td><span class="chip-code" dir="ltr">{{ $category->code_prefix }}</span></td>
                                <td class="is-num">
                                    <a href="{{ route('admin.account-sub-categories.index', ['category_id' => $category->id]) }}">{{ number_format($category->sub_categories_count) }}</a>
                                </td>
                                <td class="is-num">{{ number_format($count) }}</td>
                                <td class="text-end text-nowrap">
                                    <div class="act-group">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-act is-amber" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }} {{ $category->name }}" data-modal-form="#categoryForm"
                                                    data-fill="{{ json_encode(['name' => $category->name, 'code_prefix' => $category->code_prefix, 'bi_icon' => $category->bi_icon, 'bi_icon_color' => $category->bi_icon_color ?: '#2f6fed'], JSON_UNESCAPED_UNICODE) }}"
                                                    data-action="{{ route('admin.account-categories.update', $category->id) }}" data-method="PUT">{!! ico('pen') !!}</button>
                                        @endif
                                        @if ($canDelete && $category->sub_categories_count === 0)
                                            <form method="POST" action="{{ route('admin.account-categories.destroy', $category->id) }}" class="d-inline" data-confirm="{{ __('Delete :name?', ['name' => $category->name]) }}" data-confirm-ok="{{ __('Delete') }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-act is-red" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }} {{ $category->name }}">{!! ico('trash') !!}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-3 mb-0">{{ __('A category with account types under it cannot be deleted.') }}</p>
        @endif
    </div>
</div>

@if ($canCreate || $canUpdate)
<x-form-modal id="categoryForm" :title="__('Add category')" :title-edit="__('Edit category')" :action="route('admin.account-categories.store')">
    <x-field modal="categoryForm" name="name" :label="__('ui.name')" required maxlength="255" />
    <div class="row g-3">
        <x-field col="col-6" modal="categoryForm" name="code_prefix" :label="__('ui.code_prefix')" required maxlength="10" dir="ltr" :hint="__('Starts every account code, like CUS.')" style="text-transform:uppercase" />
        <x-field col="col-6" modal="categoryForm" name="bi_icon_color" type="color" :label="__('Colour')" value="#2f6fed" class="form-control-color w-100" />
    </div>
    <input type="hidden" name="bi_icon" value="{{ old('_modal') === 'categoryForm' ? old('bi_icon') : '' }}">
</x-form-modal>
@endif
@endsection
