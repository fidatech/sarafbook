@extends('layouts.admin.base')
@section('title', __('ui.users'))
@section('modern', '1')

{{-- The people who sign in to this Sarafi: role, status, and a pop-up to add or change them. --}}
@php
    $me = auth()->user();
    $canCreate = $me->can('create users');
    $canUpdate = $me->can('update users');
    $canDelete = $me->can('delete users');
    $filtered = $filters['q'] !== '' || $filters['role'] !== '' || $filters['status'] !== '';
    $roleOptions = $roles->mapWithKeys(fn ($r) => [$r->name => \App\Support\AccessLabels::role($r->name)])->all();
    $full = $maxUsers && $userCount >= $maxUsers;
@endphp

@section('content')
<x-page-header :title="__('ui.users')" icon="users-gear" :subtitle="$maxUsers ? __(':count of :max users of your plan', ['count' => $userCount, 'max' => $maxUsers]) : __('The people who sign in to this Sarafi.')">
    @can('view roles')
        <a class="btn btn-outline-soft" href="{{ route('admin.roles.index') }}"><x-icon name="user-shield" class="me-1" /> {{ __('Roles') }}</a>
    @endcan
    @if ($canCreate)
        <button class="btn btn-primary" type="button" data-modal-form="#userForm" @disabled($full) @if ($full) title="{{ __('Your plan allows :count users. Ask for a larger plan to add more.', ['count' => $maxUsers]) }}" @endif><x-icon name="user-plus" class="me-1" /> {{ __('Add user') }}</button>
    @endif
</x-page-header>

<div class="card">
    <div class="card-body">
        <form method="GET" class="list-tools" role="search" data-live="list">
            <input type="search" class="form-control grow" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Name or username…') }}" aria-label="{{ __('ui.search') }}">
            <select class="form-select" name="role" aria-label="{{ __('Role') }}">
                <option value="">{{ __('All roles') }}</option>
                @foreach ($roleOptions as $value => $text)<option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $text }}</option>@endforeach
            </select>
            <select class="form-select" name="status" aria-label="{{ __('ui.status') }}">
                <option value="">{{ __('Any status') }}</option>
                <option value="active" @selected($filters['status'] === 'active')>{{ __('ui.active') }}</option>
                <option value="off" @selected($filters['status'] === 'off')>{{ __('ui.deactivated') }}</option>
            </select>
            <a class="btn btn-light-secondary" href="{{ route('admin.users.index') }}" data-live-clear title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
        </form>

@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}">
        @if ($users->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="users-gear" /></div>
                <h2 class="h3">{{ __('Nothing found') }}</h2>
            </div>
        @else
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('User') }}</th>
                            <th scope="col">{{ __('Role') }}</th>
                            <th scope="col">{{ __('ui.status') }}</th>
                            <th scope="col">{{ __('Since') }}</th>
                            <th scope="col" class="text-end"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            @php
                                $roleName = $user->roles->first()?->name;
                                $self = $user->id === $me->id;
                                $fill = json_encode(['name' => $user->name, 'username' => $user->username, 'role' => $roleName, 'is_active' => (bool) $user->is_active], JSON_UNESCAPED_UNICODE);
                            @endphp
                            <tr>
                                <td>
                                    <div class="row-who">
                                        <a href="{{ route('admin.users.show', $user->id) }}" @class(['avatar avatar-sm', 'avatar-soft-primary' => $user->is_active, 'avatar-soft-secondary' => ! $user->is_active]) aria-hidden="true" tabindex="-1">{{ initial($user->name) }}</a>
                                        <div class="min-w-0">
                                            <a class="row-name" href="{{ route('admin.users.show', $user->id) }}">{{ $user->name }}</a>
                                            <div class="row-meta"><span dir="ltr">{{ '@'.$user->username }}</span>@if ($self)<span class="badge-soft info">{{ __('You') }}</span>@endif</div>
                                        </div>
                                    </div>
                                </td>
                                <td>@if ($roleName)<span class="badge-soft primary">{{ \App\Support\AccessLabels::role($roleName) }}</span>@else<span class="text-muted">—</span>@endif
                                    <div class="row-meta">{{ __(':count permissions', ['count' => $user->permissions_count]) }}</div></td>
                                <td>
                                    @if ($user->is_active)
                                        <span class="badge-soft success"><span class="dot"></span>{{ __('ui.active') }}</span>
                                    @else
                                        <span class="badge-soft secondary"><span class="dot"></span>{{ __('ui.deactivated') }}</span>
                                    @endif
                                </td>
                                <td class="text-muted text-nowrap">{{ optional($user->created_at)->format('Y-m-d') }}</td>
                                <td class="text-end text-nowrap">
                                    <div class="act-group">
                                        <a class="btn-act is-blue" href="{{ route('admin.users.show', $user->id) }}" title="{{ __('Permissions') }}" aria-label="{{ __('Permissions') }} — {{ $user->name }}">{!! ico('key') !!}</a>
                                        @if ($canUpdate)
                                            <button type="button" class="btn-act is-amber" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }} {{ $user->name }}" data-modal-form="#userForm" data-fill="{{ $fill }}" data-action="{{ route('admin.users.update', $user->id) }}" data-method="PUT">{!! ico('pen') !!}</button>
                                        @endif
                                        @if ($canUpdate || $canDelete)
                                            <div class="dropdown d-inline-block">
                                                <button class="btn-act is-gray" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('More') }}" aria-label="{{ __('More') }}">{!! ico('ellipsis-vertical') !!}</button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if ($canUpdate)
                                                        <li><button class="dropdown-item" type="button" data-modal-form="#passwordForm" data-action="{{ route('admin.users.update-password', $user->id) }}" data-method="PUT" data-fill="{{ json_encode(['who' => $user->name]) }}">{!! ico('lock', 'me-2') !!} {{ __('Change password') }}</button></li>
                                                        @unless ($self)
                                                            <li>
                                                                <form method="POST" action="{{ route('admin.users.status', $user->id) }}">@csrf
                                                                    <button class="dropdown-item" type="submit">{!! ico($user->is_active ? 'user-slash' : 'user-check', 'me-2') !!} {{ $user->is_active ? __('Switch off') : __('Switch on') }}</button>
                                                                </form>
                                                            </li>
                                                        @endunless
                                                    @endif
                                                    @if ($canDelete && ! $self)
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" data-confirm="{{ __('Delete :name?', ['name' => $user->name]) }}" data-confirm-text="{{ __('They can no longer sign in. Their records stay.') }}" data-confirm-ok="{{ __('Delete') }}">
                                                                @csrf @method('DELETE')
                                                                <button class="dropdown-item text-danger" type="submit">{!! ico('trash', 'me-2') !!} {{ __('Delete') }}</button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $users->links('partials.pagination') }}
        @endif
</div>
@endfragment
    </div>
</div>

@if ($canCreate || $canUpdate)
<x-form-modal id="userForm" :title="__('Add user')" :title-edit="__('Edit user')" :action="route('admin.users.store')">
    <x-field modal="userForm" name="name" :label="__('Full name')" required maxlength="255" autocomplete="off" />
    <x-field modal="userForm" name="username" :label="__('Username')" required maxlength="255" dir="ltr" autocomplete="off" autocapitalize="none" />
    <div class="row g-3" data-only-new>
        <x-field col="col-6" modal="userForm" name="password" type="password" :label="__('Password')" required minlength="6" autocomplete="new-password" :hint="__('At least 6 characters.')" />
        <x-field col="col-6" modal="userForm" name="repeat_password" type="password" :label="__('Repeat the password')" required autocomplete="new-password" />
    </div>
    <x-field modal="userForm" name="role" type="select" :label="__('Role')" :options="$roleOptions" :empty="__('Choose…')" required :hint="__('The user gets the permissions of the role; you can change them one by one later.')" />
    <x-field modal="userForm" name="is_active" type="switch" :label="__('Can sign in')" :value="true" />
</x-form-modal>

<x-form-modal id="passwordForm" :title="__('Change password')" :action="route('admin.users.index')">
    <p class="text-muted"><span data-who></span></p>
    <input type="hidden" name="who" value="">
    <x-field modal="passwordForm" name="new_password" type="password" :label="__('New password')" required minlength="6" autocomplete="new-password" :hint="__('At least 6 characters.')" />
    <x-field modal="passwordForm" name="new_password_confirmation" type="password" :label="__('Repeat the password')" required autocomplete="new-password" />
</x-form-modal>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('erp:form-open', function (e) {
    var who = e.target.querySelector('[data-who]');
    if (who) who.textContent = (e.detail.data && e.detail.data.who) || '';
});
</script>
@endpush
