@extends('layouts.admin.base')
@section('title', __('Roles'))
@section('modern', '1')

{{-- Roles: a name and what its users may do. The administrator role keeps its name and cannot be deleted. --}}
@php
    $canCreate = auth()->user()->can('create roles');
    $canUpdate = auth()->user()->can('update roles');
    $canDelete = auth()->user()->can('delete roles');
@endphp

@section('content')
<x-page-header :title="__('Roles')" icon="user-shield" :subtitle="__('What each kind of user may do.')">
    @can('view users')<a class="btn btn-outline-soft" href="{{ route('admin.users.index') }}"><x-icon name="users-gear" class="me-1" /> {{ __('ui.users') }}</a>@endcan
    @if ($canCreate)
        <button class="btn btn-primary" type="button" data-modal-form="#roleForm"><x-icon name="plus" class="me-1" /> {{ __('Add role') }}</button>
    @endif
</x-page-header>

<div class="row g-3">
    @foreach ($roles as $role)
        @php $isAdmin = strtolower($role->name) === 'admin'; @endphp
        <div class="col-12 col-md-6 col-xl-4">
            <section class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="coin" aria-hidden="true">{!! ico($isAdmin ? 'crown' : 'user-shield') !!}</span>
                        <div class="min-w-0 me-auto">
                            <h2 class="h5 mb-0">{{ \App\Support\AccessLabels::role($role->name) }}</h2>
                            <span class="text-muted small">{{ __(':count users', ['count' => $role->users_count]) }} · {{ __(':count permissions', ['count' => $role->permissions->count()]) }}</span>
                        </div>
                    </div>
                    <div class="progress mb-3" style="height:6px" role="img" aria-label="{{ __(':count of :max permissions', ['count' => $role->permissions->count(), 'max' => $permissions->count()]) }}">
                        <div class="progress-bar" style="width: {{ $permissions->count() ? round($role->permissions->count() / $permissions->count() * 100) : 0 }}%"></div>
                    </div>
                    <div class="mt-auto d-flex gap-2">
                        @if ($canUpdate)
                            <button class="btn btn-sm btn-outline-soft" type="button" data-modal-form="#roleForm"
                                    data-fill="{{ json_encode(['name' => $role->name, 'permissions' => $role->permissions->pluck('name')], JSON_UNESCAPED_UNICODE) }}"
                                    data-action="{{ route('admin.roles.update', $role->id) }}" data-method="PUT">{!! ico('pen', 'me-1') !!} {{ __('Change') }}</button>
                        @endif
                        @if ($canDelete && ! $isAdmin && $role->users_count === 0)
                            <form method="POST" action="{{ route('admin.roles.destroy', $role->id) }}" data-confirm="{{ __('Delete :name?', ['name' => \App\Support\AccessLabels::role($role->name)]) }}" data-confirm-ok="{{ __('Delete') }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light-secondary text-danger" type="submit">{!! ico('trash', 'me-1') !!} {{ __('Delete') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    @endforeach
</div>

@if ($canCreate || $canUpdate)
<x-form-modal id="roleForm" :title="__('Add role')" :title-edit="__('Change role')" :action="route('admin.roles.store')" size="xl">
    <x-field modal="roleForm" name="name" :label="__('ui.name')" required maxlength="100" :hint="__('The administrator role keeps its name.')" />
    <p class="form-label">{{ __('What users of this role may do') }}</p>
    <x-permission-grid :permissions="$permissions" :selected="old('_modal') === 'roleForm' ? (array) old('permissions', []) : []" id="rolePerm" />
</x-form-modal>
@endif
@endsection
