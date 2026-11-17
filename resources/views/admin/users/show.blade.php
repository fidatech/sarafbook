@extends('layouts.admin.base')
@section('title', $user->name)
@section('modern', '1')

{{-- One user: who they are, their role, and what they may do, one permission at a time. --}}
@php
    $canUpdate = auth()->user()->can('update users');
    $roleName = $user->roles->first()?->name;
    $mine = $user->permissions->pluck('name')->all();
    $fromRole = $user->roles->flatMap->permissions->pluck('name')->unique()->all();
    $extra = array_values(array_diff($mine, $fromRole));
    $missing = array_values(array_diff($fromRole, $mine));
@endphp

@section('content')
<x-page-header :title="$user->name" icon="user" :crumbs="[[__('ui.users'), route('admin.users.index')], [$user->name]]">
    <a class="btn btn-outline-soft" href="{{ route('admin.users.index') }}"><x-icon name="arrow-left" class="me-1" /> {{ __('Back') }}</a>
</x-page-header>

<div class="row g-3">
    <div class="col-12 col-xl-3">
        <section class="card">
            <div class="card-body text-center">
                <span @class(['avatar avatar-xl mx-auto mb-2', 'avatar-soft-primary' => $user->is_active, 'avatar-soft-secondary' => ! $user->is_active]) aria-hidden="true">{{ initial($user->name) }}</span>
                <h2 class="h5 mb-0">{{ $user->name }}</h2>
                <p class="text-muted mb-2" dir="ltr">{{ '@'.$user->username }}</p>
                @if ($roleName)<span class="badge-soft primary">{{ \App\Support\AccessLabels::role($roleName) }}</span>@endif
                @if ($user->is_active)
                    <span class="badge-soft success"><span class="dot"></span>{{ __('ui.active') }}</span>
                @else
                    <span class="badge-soft secondary"><span class="dot"></span>{{ __('ui.deactivated') }}</span>
                @endif
            </div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('Permissions') }}</span><strong>{{ count($mine) }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('Since') }}</span><span>{{ optional($user->created_at)->format('Y-m-d') }}</span></li>
                @if ($extra)<li class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('More than the role') }}</span><strong>{{ count($extra) }}</strong></li>@endif
                @if ($missing)<li class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('Less than the role') }}</span><strong>{{ count($missing) }}</strong></li>@endif
            </ul>
        </section>
    </div>
    <div class="col-12 col-xl-9">
        <section class="card" aria-labelledby="permTitle">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <div class="me-auto">
                    <h2 class="h5 card-title mb-0" id="permTitle">{{ __('What :name may do', ['name' => $user->name]) }}</h2>
                    <span class="text-muted small">{{ __('Starts from the role; change any box for this user alone.') }}</span>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.users.update-permissions', $user->id) }}">
                @csrf
                <div class="card-body">
                    <x-permission-grid :permissions="$allPermissions" :selected="$mine" id="userPerm" :disabled="! $canUpdate" />
                </div>
                @if ($canUpdate)
                    <div class="card-footer d-flex flex-wrap gap-2 justify-content-end">
                        @if ($roleName)
                            <button type="button" class="btn btn-light-secondary" data-role-perms="{{ json_encode($fromRole) }}">{{ __('Same as the role') }}</button>
                        @endif
                        <button type="submit" class="btn btn-primary"><x-icon name="check" class="me-1" /> {{ __('Save permissions') }}</button>
                    </div>
                @endif
            </form>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-role-perms]');
    if (!b) return;
    var want = JSON.parse(b.getAttribute('data-role-perms'));
    var form = b.closest('form');
    form.querySelectorAll('input[name="permissions[]"]').forEach(function (c) { c.checked = want.indexOf(c.value) !== -1; });
    form.querySelectorAll('[data-perm-row]').forEach(function (r) { r.dispatchEvent(new Event('change', { bubbles: true })); });
    form.querySelectorAll('[data-perm-grid] tbody tr').forEach(function (tr) {
        var boxes = tr.querySelectorAll('input[name="permissions[]"]'), all = tr.querySelector('[data-perm-row]');
        var on = Array.prototype.filter.call(boxes, function (x) { return x.checked; }).length;
        if (all) { all.checked = on === boxes.length && on > 0; all.indeterminate = on > 0 && on < boxes.length; }
    });
});
</script>
@endpush
