@extends('layouts.admin.base')

@section('title', __('ui.accounts'))

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
    <link href="{{ asset('assets/offline/9d2842cf83f43d76.css') }}" rel="stylesheet" />
    <style>
        .avatar-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
            text-align: center;
            font-size: 0.75rem;
        }

        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1055;
        }

        .avatar.avatar-initial {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 44px;
            height: 44px;
            font-size: 20px;
        }
    </style>
@endsection

@section('content')
    <div class="toast-container">
        @if (session('success'))
            <div class="toast align-items-center text-bg-success show border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">{{ session('success') }}</div>
                    <button type="button" class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="toast align-items-center text-bg-danger show border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">{{ session('error') }}</div>
                    <button type="button" class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
    </div>

    <div class="row g-4 mb-4">
        @php
            $stats = [
                ['label' => __('ui.total_accounts'), 'id' => 'total_accounts', 'icon' => 'bi-people-fill', 'bg' => 'primary', 'border' => 'success'],
                ['label' => __('ui.total_credit'), 'id' => 'total_credit', 'icon' => 'bi-cash-coin', 'bg' => 'success', 'border' => 'primary'],
                ['label' => __('ui.total_debit'), 'id' => 'total_debit', 'icon' => 'bi-cash-stack', 'bg' => 'danger', 'border' => 'danger'],
                ['label' => __('ui.balance'), 'id' => 'balance', 'icon' => 'bi-wallet', 'bg' => 'warning', 'border' => 'dark'],
            ];
        @endphp

        @foreach ($stats as $stat)
            <div class="col-md-6 col-xl-3">
                <div class="card card-border-shadow-{{ $stat['border'] }} shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">{{ $stat['label'] }}</small>
                            <h5 class="fw-semibold mb-0" id="{{ $stat['id'] }}">0</h5>
                        </div>
                        <span class="avatar avatar-initial bg-label-{{ $stat['bg'] }} rounded-circle">
                            <i class="bi {{ $stat['icon'] }}"></i>
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex w-100 flex-wrap gap-2">

                {{-- Search --}}
                <div class="flex-fill" style="min-width: 250px;">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control rounded-start-0" id="search" placeholder="{{ __('ui.search') }}...">
                        <button class="btn btn-outline-secondary" id="search-btn">
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- Account Type --}}
                <div class="flex-fill" style="min-width: 200px;">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-diagram-3"></i></span>
                        <select id="account_type" class="rounded-start-0 form-select">
                            <option value="">{{ __('ui.type') }}</option>
                            @foreach ($accountSubCategories as $item)
                                <option value="{{ $item->id }}">{{ __('accounts.types_plural')[$item->name] ?? Str::plural($item->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Currency --}}
                <div class="flex-fill" style="min-width: 160px;">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-currency-dollar"></i></span>
                        <select id="currency_id" class="rounded-start-0 form-select">
                            <option value="">{{ __('ui.all') }}</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}">{{ $currency->code }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Balance Filter --}}
                <div class="flex-fill" style="min-width: 220px;">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-filter-circle"></i></span>
                        <select id="balance_filter" class="rounded-start-0 form-select">
                            <option value="">{{ __('ui.all_no_balance_order') }}</option>
                            <option value="creditors">{{ __('ui.creditors_largest_first') }}</option>
                            <option value="debtors">{{ __('ui.debtors_most_negative') }}</option>
                            <option value="mixed">{{ __('ui.mixed_balances') }}</option>
                        </select>
                    </div>
                </div>

                {{-- Per Page --}}
                <div style="width: 120px;">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-list-nested"></i></span>
                        <select id="per-page" class="rounded-start-0 form-select">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                {{-- Create Button --}}
                <div class="d-flex gap-2">
                    @can('create customers')
                        <button class="btn btn-outline-primary d-flex align-items-center gap-2" type="button"
                                data-bs-toggle="modal" data-bs-target="#bulkAccountImportModal">
                            <i class="bi bi-file-earmark-spreadsheet"></i>
                            <span>{{ __('ui.bulk_import') }}</span>
                        </button>
                    @endcan
                </div>
                <div style="width: 180px;" class="mt-md-0 float-end mt-5">
                    <div class="input-group">
                        @can('create customers')
                        <button class="btn btn-primary d-flex align-items-center btn-block" style="height: 48px;"
                                data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddUser">
                            <i class="bi bi-plus-circle"></i>
                            <span>{{ __('ui.create_account') }}</span>
                        </button>
                        @endcan
                    </div>
                </div>

            </div>
        </div>

        <div class="table-responsive p-3">
            <table class="table table-bordered table-hover text-nowrap table-responsive" id="yajra-datatable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('ui.account_name') }}</th>
                        <th>{{ __('ui.type') }}</th>
                        <th>{{ __('ui.details') }}</th>
                        <th>{{ __('ui.balance') }}</th>
                        <th style="width: 200px;">{{ __('ui.actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @include('admin.accounts.partials.create-form')
    @include('admin.accounts.partials.create-user-modal')

    <div class="modal fade" id="bulkAccountImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.accounts.bulk-import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('ui.bulk_account_import') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <div class="fw-semibold mb-1">{{ __('ui.import_instructions') }}</div>
                            <div>{{ __('ui.import_optional_fields_help') }}</div>
                            <div>{{ __('ui.import_balance_sign_help') }}</div>
                            <div>{{ __('ui.import_atomic_help') }}</div>
                        </div>
                        <a class="btn btn-outline-secondary mb-4" href="{{ route('admin.accounts.bulk-import.template') }}">
                            <i class="bi bi-download me-1"></i>{{ __('ui.download_excel_template') }}
                        </a>
                        <label class="form-label" for="accounts-import-file">{{ __('ui.csv_or_excel_file') }}</label>
                        <input class="form-control" id="accounts-import-file" name="file" type="file" accept=".csv,.xlsx,.xls" required>
                        <div class="form-text">{{ __('ui.import_file_help') }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>{{ __('ui.import_accounts') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Fullscreen Modal -->
    <div class="modal fade" id="accountPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-white">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('ui.account_summary') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" id="accountPreviewContent" style="background: #f4f4f4;"></div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @include('admin.accounts.partials.scripts')
    <script>
        document.querySelectorAll('.toast').forEach(toast => new bootstrap.Toast(toast).show());
    </script>
@endsection
