@extends('layouts.admin.base')
@section('title', __('ui.manage') . ' ' . $user->name)
@section('css')
    <style>
        .fade-in-card {
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .group-label {
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 0.25rem;
            text-transform: capitalize;
        }

        .select-all-link {
            font-size: 0.85rem;
            cursor: pointer;
        }
    </style>
@endsection
@section('content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card fade-in-card border shadow-sm">
                <div class="card-body p-4 text-center">

                    <div class="position-relative d-inline-block mb-3">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto text-white"
                             style="width: 100px; height: 100px; font-size: 36px;">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    </div>

                    <h4 class="fw-semibold mb-1">{{ $user->name }}</h4>

                    <span class="badge bg-label-{{ $user->account_type === 'client' ? 'primary' : 'info' }} mb-3">
                        <i class="bi {{ $user->account_type === 'client' ? 'bi-person' : 'bi-person-workspace' }} me-1"></i>
                        {{ \App\Support\AccessLabels::role($user->account_type) }}
                    </span>

                    <ul class="list-unstyled small text-start">
                        <li class="mb-1">
                            <strong>👤 {{ __('ui.username') }}:</strong>
                            <span class="text-dark">{{ $user->username }}</span>
                        </li>

                        <li class="mb-1">
                            <strong>📌 {{ __('ui.status') }}:</strong>
                            <span class="badge bg-label-{{ $user->is_active ? 'success' : 'danger' }}">
                                {{ $user->is_active ? __('ui.active') : __('ui.deactivated') }}
                            </span>
                        </li>

                        <li class="mb-1">
                            <strong>🔒 {{ __('ui.role') }}:</strong>
                            {{ $user->account_type === 'client' ? __('ui.customer') : __('ui.admin') }}
                        </li>

                        <li class="mb-1">
                            <strong>📅 {{ __('ui.created_at') }}:</strong>
                            {{ $user->created_at->format('d-m-Y') }}
                        </li>

                        <li class="mb-1">
                            <strong>🧑‍💼 {{ __('ui.created_by') }}:</strong>
                            {{ $user->creator->name }}
                        </li>
                    </ul>

                    <a href="javascript:;" class="btn btn-{{ $user->is_active ? 'outline-danger' : 'outline-success' }} w-100 mt-3"
                       onclick="confirmStatusChange('{{ $user->id }}', '{{ $user->name }}', {{ $user->is_active ? 'false' : 'true' }})">

                        <i class="bi bi-{{ $user->is_active ? 'x-circle' : 'check-circle' }} me-1"></i>

                        {{ $user->is_active ? __('ui.deactivate_account') : __('ui.activate_account') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card fade-in-card">
                <div class="card-body">

                    @if ($user->account_type === 'admin')
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="toggleAllGroups(true)">
                                {{ __('ui.select_all_permissions') }}
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleAllGroups(false)">
                                {{ __('ui.unselect_all_permissions') }}
                            </button>
                        </div>
                    @endif

                    <ul class="nav nav-tabs mb-3" role="tablist">

                        @if ($user->account_type === 'admin')
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#permissionsTab" role="tab">
                                    {{ __('ui.permissions') }}
                                </a>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a class="nav-link {{ $user->account_type !== 'admin' ? 'active' : '' }}"
                               data-bs-toggle="tab" href="#passwordTab" role="tab">
                                {{ __('ui.change_password') }}
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">

                        @if ($user->account_type === 'admin')
                            <div class="tab-pane fade show active" id="permissionsTab" role="tabpanel">

                                <form method="POST" action="{{ route('admin.users.update-permissions', $user->id) }}">
                                    @csrf
                                    <div class="row">

                                        @php
                                            $icons = [
                                                'view' => 'eye',
                                                'create' => 'plus-circle',
                                                'update' => 'pencil-square',
                                                'delete' => 'trash',
                                                'export' => 'file-earmark-arrow-down',
                                                'assign' => 'person-check',
                                                'approve' => 'check-circle',
                                            ];

                                            $permissionsByModule = collect($allPermissions)
                                                ->map(function ($perm) {
                                                    $parts = explode(' ', $perm->name, 2);
                                                    if (count($parts) === 1) {
                                                        $perm->action = $parts[0];
                                                        $perm->module = 'misc';
                                                    } else {
                                                        $perm->action = $parts[0];
                                                        $perm->module = $parts[1];
                                                        if ($perm->module === 'profit') $perm->module = 'reports';
                                                        if ($perm->module === 'sale rate') $perm->module = 'reports';
                                                    }
                                                    return $perm;
                                                })
                                                ->groupBy('module');
                                        @endphp

                                        @foreach ($permissionsByModule as $module => $permissions)
                                            <div class="col-md-12">

                                                <div class="d-flex justify-content-between align-items-center group-label mb-2 mt-3">
                                                    <span>
                                                        <i class="bi bi-shield-lock me-1"></i>
                                                        {{ \App\Support\AccessLabels::group($module) }}
                                                    </span>

                                                    <span class="text-primary select-all-link"
                                                          onclick="toggleGroup('{{ Str::slug($module) }}', true)">
                                                        {{ __('ui.select_all_permissions') }}
                                                    </span>

                                                    <span class="text-danger select-all-link ms-2"
                                                          onclick="toggleGroup('{{ Str::slug($module) }}', false)">
                                                        {{ __('ui.unselect_all_permissions') }}
                                                    </span>
                                                </div>

                                                <div class="row mb-3" id="group-{{ Str::slug($module) }}">
                                                    @foreach ($permissions as $perm)
                                                        <div class="col-md-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       name="permissions[]"
                                                                       value="{{ $perm->name }}"
                                                                       id="perm-{{ Str::slug($perm->name) }}"
                                                                       {{ $user->hasPermissionTo($perm->name) ? 'checked' : '' }}>

                                                                <label class="form-check-label text-capitalize"
                                                                       for="perm-{{ Str::slug($perm->name) }}">
                                                                    <i class="bi bi-{{ $icons[$perm->action] ?? 'gear' }} me-1"></i>

                                                                    {{ \App\Support\AccessLabels::permission($perm->name) }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                            </div>
                                        @endforeach

                                    </div>

                                    <div class="mt-4">
                                        <button class="btn btn-primary">{{ __('ui.save_permissions') }}</button>
                                    </div>

                                </form>

                            </div>
                        @endif

                        <div class="tab-pane fade {{ $user->account_type !== 'admin' ? 'show active' : '' }}"
                             id="passwordTab"
                             role="tabpanel">

                            <form method="post" action="{{ route('admin.users.update-password', $user->id) }}" class="mt-4">
                                @csrf @method('put')

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label for="newPassword" class="form-label">{{ __('ui.new_password') }}</label>
                                        <input type="password" id="newPassword" name="newPassword" class="form-control" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="confirmPassword" class="form-label">{{ __('ui.confirm_password') }}</label>
                                        <input type="password" id="confirmPassword" name="new_password" class="form-control" required>
                                    </div>
                                </div>

                                <ul class="mb-0 mt-3">
                                    <li>{{ __('ui.min_8_characters') }}</li>
                                    <li>{{ __('ui.uppercase_lowercase_required') }}</li>
                                </ul>

                                <div class="mt-4">
                                    <button class="btn btn-primary me-2">{{ __('ui.save_changes') }}</button>
                                    <button type="reset" class="btn btn-outline-secondary">{{ __('ui.reset') }}</button>
                                </div>

                            </form>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        const usersSwal = Swal.mixin({confirmButtonText: @json(__('ui.blade_ok')), cancelButtonText: @json(__('ui.cancel'))});
        function confirmStatusChange(id, name, activate) {
            const message = activate ? @json(__('access.activate_message')) : @json(__('access.deactivate_message'));
            const confirmText = activate ? @json(__('access.yes_activate')) : @json(__('access.yes_deactivate'));
            usersSwal.fire({
                title: '{{ __('ui.are_you_sure') }}',
                text: message.replace(':name', name),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: confirmText
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = `/admin/users/${id}/update-status`;
                }
            });
        }

        function toggleGroup(groupSlug, checked) {
            const checkboxes = document.querySelectorAll(`#group-${groupSlug} input[type='checkbox']`);
            checkboxes.forEach(cb => cb.checked = checked);
        }

        function toggleAllGroups(checked) {
            const allGroups = document.querySelectorAll('[id^="group-"] input[type="checkbox"]');
            allGroups.forEach(cb => cb.checked = checked);
        }
    </script>
@endsection
