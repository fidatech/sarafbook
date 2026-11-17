@extends('layouts.admin.base')
@section('title', __('Permissions'))
@section('modern', '1')

{{--
    What each role may do, as a grid. Saving gives the role's users the same permissions. The
    permission names themselves belong to the system and are not edited here.
--}}
@php $canUpdate = auth()->user()->can('update permissions') || auth()->user()->can('update roles'); @endphp

@section('content')
<x-page-header :title="__('Permissions')" icon="key" :subtitle="__('Choose a role and tick what its users may do.')">
    <a class="btn btn-outline-soft" href="{{ route('admin.roles.index') }}"><x-icon name="user-shield" class="me-1" /> {{ __('Roles') }}</a>
</x-page-header>

@if ($role)
<div class="card">
    <div class="card-header">
        <nav class="d-flex flex-wrap gap-1" aria-label="{{ __('Roles') }}">
            @foreach ($roles as $r)
                <a href="{{ route('admin.permissions.index', ['role' => $r->id]) }}" @class(['btn btn-sm', 'btn-primary' => $r->id === $role->id, 'btn-light-secondary' => $r->id !== $role->id]) @if ($r->id === $role->id) aria-current="page" @endif>
                    {{ \App\Support\AccessLabels::role($r->name) }} <span class="opacity-75">· {{ $r->permissions->count() }}</span>
                </a>
            @endforeach
        </nav>
    </div>
    <form method="POST" action="{{ route('admin.permissions.assign') }}">
        @csrf
        <input type="hidden" name="role_id" value="{{ $role->id }}">
        <div class="card-body">
            <p class="text-muted small">{{ __(':count users have this role; saving gives them these permissions.', ['count' => $role->users_count]) }}</p>
            <x-permission-grid :permissions="$permissions" :selected="$role->permissions->pluck('name')" id="assignPerm" :disabled="! $canUpdate" />
        </div>
        @if ($canUpdate)
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary"><x-icon name="check" class="me-1" /> {{ __('Save for :role', ['role' => \App\Support\AccessLabels::role($role->name)]) }}</button>
            </div>
        @endif
    </form>
</div>
@else
<div class="card"><div class="card-body"><div class="empty-state"><div class="es-icon"><x-icon name="key" /></div><h2 class="h3">{{ __('No role yet') }}</h2></div></div></div>
@endif
@endsection
