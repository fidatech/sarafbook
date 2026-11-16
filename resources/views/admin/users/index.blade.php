@extends('layouts.admin.base')
@section('title', __('access.users_title'))
@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
@endsection
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('ui.user_accounts') }}</h5>
            <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddUser">
                <i class="bi bi-plus-circle"></i>
                <span>{{ __('ui.create_account') }}</span>
            </button>
        </div>

        <div class="card-body">

            <div class="row row-cols-1 row-cols-md-3 g-4 mb-4 mt-2">
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                        <select class="form-select" id="account_type">
                            <option value="">{{ __('ui.user_type') }}</option>
                            <option value="admin">{{ __('ui.admin') }}</option>
                            <option value="client">{{ __('ui.client') }}</option>
                        </select>
                    </div>
                </div>

                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="search" placeholder="{{ __('ui.search') }}...">
                        <button class="btn btn-outline-secondary" type="button" id="search-btn">
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

            </div>

            <div class="table-responsive">
                <table class="table-bordered table-hover table text-center align-middle" id="yajra-datatable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('ui.account') }}</th>
                            <th>{{ __('ui.username') }}</th>
                            <th>{{ __('ui.type') }}</th>
                            <th>{{ __('ui.permissions') }}</th>
                            <th>{{ __('ui.status') }}</th>
                            <th>{{ __('ui.created') }}</th>
                            <th style="min-width: 120px;">{{ __('ui.manage') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>

    @include('admin.users.partials.create')
    @include('admin.users.partials.edit')

@endsection

@section('js')
    <script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>
    <script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>

    <script>
        const usersSwal = Swal.mixin({confirmButtonText: @json(__('ui.blade_ok')), cancelButtonText: @json(__('ui.cancel'))});
        $(function() {
            const table = $('#yajra-datatable').DataTable({
                language: @include('admin.users.partials.table-language'),
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('admin.users.fetch-users') }}",
                    data: function(d) {
                        d.account_type = $('#account_type').val();
                        d.search_term = $('#search').val();
                    }
                },
                columns: [{
                        data: null,
                        name: 'row_index',
                        searchable: false,
                        orderable: false,
                        render: (data, type, row, meta) => meta.row + 1
                    },
                    {
                        data: 'name_with_profile',
                        name: 'users.name'
                    },
                    {
                        data: 'username',
                        name: 'users.username',
                        render: data => `<span class="badge bg-label-primary">${data}</span>`
                    },
                    {
                        data: 'account_type',
                        name: 'users.account_type'
                    },
                    {
                        data: 'total_permissions',
                        name: 'users.total_permissions'
                    },
                    {
                        data: 'is_active',
                        name: 'users.is_active'
                    },
                    {
                        data: 'created_at',
                        name: 'users.created_at'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [2, 'asc']
                ],
                pageLength: 10,
                searching: false,
                lengthChange: false,
                info: false
            });

            // Filters
            $('#account_type, #search-btn').on('change click', () => table.draw());
            $('#search').on('keyup', e => e.key === 'Enter' && table.draw());

            // Prevent spaces in username
            $('#account-username').on('input', function() {
                this.value = this.value.replace(/\s/g, '');
            });

            // Password match validation
            $('#account-password, #account-repeat-password').on('input', function() {
                const pass = $('#account-password').val();
                const repeat = $('#account-repeat-password').val();
                const feedback = $('#password-feedback');
                const submit = $('#submit-button');

                if (!repeat) {
                    feedback.html('');
                    submit.prop('disabled', true);
                    return;
                }

                if (pass === repeat) {
                    feedback.html('<span class="text-success">{{ __('access.passwords_match') }}</span>');
                    submit.prop('disabled', false);
                } else {
                    feedback.html('<span class="text-danger">{{ __('access.passwords_mismatch') }}</span>');
                    submit.prop('disabled', true);
                }
            });
        });
    </script>
    <script>
        // Submit Edit Form
        $(document).on('click', '.edit-user-btn', function() {
            let userId = $(this).data('id');

            $.get('/admin/users/' + userId + '/edit', function(res) {

                $('#edit-user-id').val(res.user.id);
                $('#edit-name').val(res.user.name);
                $('#edit-username').val(res.user.username);
                $('#edit-is-active').prop('checked', res.user.is_active);

                if (res.role) {
                    $('#edit-role').val(res.role);
                }

                let actionUrl = '/admin/users/' + userId;
                $('#editUserForm').attr('action', actionUrl);

                $('#offcanvasEditUser').offcanvas('show');
            });
        });

        // ============================
        // AJAX UPDATE — Edit User
        // ============================
        $('#editUserForm').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let userId = $('#edit-user-id').val();
            let actionUrl = '/admin/users/' + userId;

            $.ajax({
                url: actionUrl,
                type: 'POST',
                data: form.serialize(),
                success: function(res) {

                    // Close offcanvas
                    let offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasEditUser'));
                    offcanvas.hide();

                    // Reload datatable
                    $('#yajra-datatable').DataTable().ajax.reload(null, false);

                    usersSwal.fire({
                        icon: 'success',
                        title: '{{ __('ui.blade_updated_2') }}',
                        text: '{{ __('ui.blade_user_updated_successfully') }}',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    let message = @json(__('access.error_message'));

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    usersSwal.fire({
                        icon: 'error',
                        title: '{{ __('ui.blade_error_2') }}',
                        text: message
                    });
                }
            });
        });

        // Delete User
        $(document).on('click', '.delete-user-btn', function() {
            const id = $(this).data('id');
            usersSwal.fire({
                title: '{{ __('ui.are_you_sure') }}',
                text: '{{ __('ui.blade_this_will_delete_the_user_permanently') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('ui.blade_yes_delete_it_2') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/users/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            $('#yajra-datatable').DataTable().ajax.reload();
                            usersSwal.fire(@json(__('access.deleted_title')), response.message, 'success');
                        },
                        error: function(xhr) {
                            let errorMessage = @json(__('access.error_message'));

                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }

                            usersSwal.fire(@json(__('access.error_title')), errorMessage, 'error');
                        }
                    });
                }
            });
        });
    </script>
@endsection
