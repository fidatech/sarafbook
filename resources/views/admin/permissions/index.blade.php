@extends('layouts.admin.base')

@section('title', __('access.permissions_title'))

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
@endsection

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('ui.permissions') }}</h5>
        <button class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasAddPermission">
            <i class="bi bi-plus-circle"></i>
            <span>{{ __('ui.add_permission') }}</span>
        </button>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('admin.permissions.assign') }}">
            @csrf

            <div class="mb-4">
                <label for="role_id" class="form-label">{{ __('ui.select_role') }}</label>
                <select name="role_id" id="role_id" class="form-select" required>
                    <option value="">{{ __('ui.choose_role') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}">{{ \App\Support\AccessLabels::role($role->name) }}</option>
                    @endforeach
                </select>
            </div>

            @php
                $groupedPermissions = $permissions->groupBy(fn($perm) => explode(' ', $perm->name)[1] ?? 'general');
                $icons = [
                    'view'   => 'eye',
                    'create' => 'plus-circle',
                    'update' => 'pencil-square',
                    'delete' => 'trash'
                ];
            @endphp

            @foreach ($groupedPermissions as $group => $groupPermissions)
                <div class="border-bottom mb-3 pb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="text-muted fw-semibold text-capitalize mb-2">{{ \App\Support\AccessLabels::group($group) }}</h6>

                        <div>
                            <a href="javascript:;" class="text-primary small"
                               onclick="toggleGroup('{{ Str::slug($group) }}', true)">
                                {{ __('ui.select_all') }}
                            </a>
                            /
                            <a href="javascript:;" class="text-danger small"
                               onclick="toggleGroup('{{ Str::slug($group) }}', false)">
                                {{ __('ui.unselect_all') }}
                            </a>
                        </div>
                    </div>

                    <div class="row" id="group-{{ Str::slug($group) }}">
                        @foreach ($groupPermissions as $permission)
                            @php $action = explode(' ', $permission->name)[0]; @endphp
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox"
                                           type="checkbox"
                                           name="permissions[]"
                                           value="{{ $permission->name }}"
                                           id="perm-{{ $permission->id }}">

                                    <label class="form-check-label text-capitalize"
                                           for="perm-{{ $permission->id }}">
                                        <i class="bi bi-{{ $icons[$action] ?? 'lock' }} me-1"></i>
                                        {{ \App\Support\AccessLabels::permission($permission->name) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary w-100">
                {{ __('ui.assign_selected_permissions') }}
            </button>
        </form>
    </div>
</div>

<!-- Offcanvas Create Form -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddPermission">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title">{{ __('ui.add_new_permission') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <form method="POST" action="{{ route('admin.permissions.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">{{ __('ui.permission_name') }}</label>
                <input type="text" name="name"
                       class="form-control"
                       placeholder="{{ __('ui.permission_placeholder') }}"
                       required>
            </div>

            <button class="btn btn-primary w-100">
                {{ __('ui.create_permission') }}
            </button>

        </form>
    </div>
</div>
@endsection


@section('js')
    <script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>

    <script>
        document.getElementById('role_id')?.addEventListener('change', function() {
            const roleId = this.value;
            if (!roleId) return;

            fetch(`/admin/roles/${roleId}/permissions-json`)
                .then(res => res.json())
                .then(data => {
                    document.querySelectorAll('.permission-checkbox').forEach(cb => {
                        cb.checked = data.permissions.includes(cb.value);
                    });
                });
        });
    </script>

    <script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>
    <script>
        $(function() {
            $('#permissions-table').DataTable({
                language: @include('admin.users.partials.table-language'),
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.permissions.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'group',
                        name: 'group',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ]
            });
        });

        function toggleGroup(groupSlug, checked) {
            const checkboxes = document.querySelectorAll(`#group-${groupSlug} input[type='checkbox']`);
            checkboxes.forEach(cb => cb.checked = checked);
        }
    </script>
@endsection
