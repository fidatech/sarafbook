@extends('layouts.admin.base')
@section('title', __('ui.expense_categories'))
@section('modern', '1')

{{-- Expense categories: rent, salaries, … A category with expenses cannot be deleted. --}}
@php
    $canCreate = auth()->user()->can('create expense categories');
    $canUpdate = auth()->user()->can('update expense categories');
    $canDelete = auth()->user()->can('delete expense categories');
@endphp

@section('content')
<x-page-header :title="__('ui.expense_categories')" icon="receipt" :subtitle="__('Groups for your expenses, so the reports can add them up.')">
    @if ($canCreate)
        <button class="btn btn-primary" type="button" data-modal-form="#expenseCategoryForm"><x-icon name="plus" class="me-1" /> {{ __('Add category') }}</button>
    @endif
</x-page-header>

<div class="card">
    <div class="card-body">
        @if ($categories->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="receipt" /></div>
                <h2 class="h3">{{ __('No category yet') }}</h2>
                @if ($canCreate)
                    <p>{{ __('Add categories like rent or salaries.') }}</p>
                    <button class="btn btn-primary" type="button" data-modal-form="#expenseCategoryForm"><x-icon name="plus" class="me-1" /> {{ __('Add category') }}</button>
                @endif
            </div>
        @else
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ui.name') }}</th>
                            <th scope="col">{{ __('ui.description') }}</th>
                            <th scope="col" class="is-num">{{ __('Expenses') }}</th>
                            <th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td><span class="row-name">{{ $category->name }}</span></td>
                                <td class="text-muted">{{ $category->description ?: '—' }}</td>
                                <td class="is-num">{{ number_format($category->expenses_count) }}</td>
                                <td class="text-end text-nowrap">
                                    <div class="act-group">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-act is-amber" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }} {{ $category->name }}" data-modal-form="#expenseCategoryForm"
                                                    data-fill="{{ json_encode(['name' => $category->name, 'description' => $category->description], JSON_UNESCAPED_UNICODE) }}"
                                                    data-action="{{ route('admin.expense-categories.update', $category->id) }}" data-method="PUT">{!! ico('pen') !!}</button>
                                        @endif
                                        @if ($canDelete && $category->expenses_count === 0)
                                            <form method="POST" action="{{ route('admin.expense-categories.destroy', $category->id) }}" class="d-inline" data-confirm="{{ __('Delete :name?', ['name' => $category->name]) }}" data-confirm-ok="{{ __('Delete') }}">
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
        @endif
    </div>
</div>

@if ($canCreate || $canUpdate)
<x-form-modal id="expenseCategoryForm" :title="__('Add category')" :title-edit="__('Edit category')" :action="route('admin.expense-categories.store')">
    <x-field modal="expenseCategoryForm" name="name" :label="__('ui.name')" required maxlength="255" />
    <x-field modal="expenseCategoryForm" name="description" type="textarea" :label="__('ui.description')" />
</x-form-modal>
@endif
@endsection
