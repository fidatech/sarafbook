<style>
    .form-check-input:checked {
        background-color: #4361ee;
        border-color: #4361ee;
    }

    .form-check-label {
        font-size: 13px;
    }
</style>
<div class="offcanvas offcanvas-end w-50" tabindex="-1" id="offcanvasAddUser" aria-labelledby="offcanvasAddUserLabel">
    <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddUserLabel" class="offcanvas-title">{{ __('ui.add_user') }}</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('ui.close') }}"></button>
    </div>

    <div class="offcanvas-body">
        <form class="pt-0" id="addUserForm" method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">{{ __('ui.full_name') }}</label>
                    <input type="text" name="name" class="form-control" placeholder="{{ __('access.full_name_placeholder') }}" required />
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('ui.username') }}</label>
                    <input type="text" name="username" class="form-control" placeholder="{{ __('access.username_placeholder') }}" required />
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('ui.password') }}</label>
                    <input type="password" name="password" class="form-control" required />
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('ui.repeat_password') }}</label>
                    <input type="password" name="repeat_password" class="form-control" required />
                    <div id="password-feedback" class="form-text mt-1"></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('ui.role') }}</label>
                    <select name="role" class="form-select" required>
                        <option value="">{{ __('ui.select_role') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ \App\Support\AccessLabels::role($role->name) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('ui.status') }}</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">{{ __('ui.active') }}</label>
                    </div>
                </div>

            </div>

            <div class="d-grid mt-4 gap-2">
                <button type="submit" class="btn btn-primary" id="submit-button">
                    <i class="bi bi-check-circle me-1"></i> {{ __('ui.save') }}
                </button>

                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">
                    <i class="bi bi-x-circle me-1"></i> {{ __('ui.cancel') }}
                </button>
            </div>

        </form>
    </div>
</div>

