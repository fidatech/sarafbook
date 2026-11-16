@extends('layouts.admin.base')

@section('title', __('expenses.title'))

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}">
    <style>
        .card-soft {
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        }

        .filter-label {
            font-size: .8rem;
            font-weight: 600;
            color: #555;
        }

        /* =======================
                                  ODOO STYLE SUMMARY CARDS
                                  ======================= */
        .summary-card {
            border-radius: 14px;
            padding: 18px 20px;
            background: #fafafa;
            border: 1px solid #e5e7eb;
            transition: 0.25s ease-in-out;
            height: 100%;
        }

        .summary-card:hover {
            background: #ffffff;
            border-color: #d1d5db;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
        }

        .summary-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 6px;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-bottom: 1px dotted #e5e7eb;
        }

        .summary-card .label {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .summary-card .value {
            font-size: 0.9rem;
            font-weight: 700;
            color: #374151;
        }

        /* TOTAL CARD */
        .total-card {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
        }

        .total-card .summary-title {
            font-size: 1rem;
            font-weight: 800;
            color: #1e3a8a;
        }

        .total-card .value {
            font-size: 1.3rem;
            font-weight: 900;
            color: #1e40af;
        }

        /* =======================
                                  MOBILE COMPACT IMPROVEMENTS
                                  ======================= */
        @media (max-width: 576px) {
            .card-soft {
                border-radius: 14px;
            }

            .page-title {
                font-size: 1.05rem;
            }

            .btn-mobile {
                padding: .5rem .7rem;
                font-size: .9rem;
            }

            .filter-label {
                font-size: .72rem;
            }

            .compact-gap {
                gap: .6rem !important;
            }

            .compact-mb {
                margin-bottom: .75rem !important;
            }

            .summary-card {
                padding: 12px 12px;
                border-radius: 12px;
            }

            .summary-title {
                font-size: .9rem;
            }

            .summary-card .value {
                font-size: .9rem;
            }

            .total-card .value {
                font-size: 1.1rem;
            }

            .table thead {
                display: none;
            }

            #expenses-table tbody tr {
                display: block;
                border: 1px solid rgba(0, 0, 0, .06);
                border-radius: 14px;
                margin-bottom: .75rem;
                overflow: hidden;
            }

            #expenses-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: .55rem .75rem;
                border-top: 1px dashed rgba(0, 0, 0, .06);
            }

            #expenses-table tbody td:first-child {
                border-top: 0;
            }

            #expenses-table tbody td::before {
                content: attr(data-label);
                font-weight: 700;
                color: #6b7280;
                font-size: .78rem;
                padding-right: .75rem;
            }

            #expenses-table tbody td.text-end {
                justify-content: space-between;
            }

            .dt-buttons {
                width: 100%;
            }

            .dt-buttons .btn {
                width: 100%;
                margin-bottom: .5rem;
            }
        }

        /* Make Select2 match Bootstrap height */
        .select2-container .select2-selection--single {
            height: calc(2.375rem + 2px);
            border: 1px solid #ced4da;
            border-radius: .375rem;
            display: flex;
            align-items: center;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.2;
            padding-left: .75rem;
            padding-right: .75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(2.375rem + 2px);
            right: .5rem;
        }

        /* Toggle card header */
        .toggle-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
        }

        .toggle-title {
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            font-size: 0.95rem;
        }

        .toggle-subtitle {
            font-size: .8rem;
            color: #64748b;
            margin: 0;
        }

        /* Expenses: compact, consistent banking UI */
        .expenses-page{--expense-ink:#1c304f;--expense-muted:#64758e;--expense-line:#e5ebf3;max-width:1830px;margin-inline:auto;padding-bottom:24px;color:var(--expense-ink)}
        .expenses-hero{display:flex;justify-content:space-between;align-items:center;gap:18px;background:linear-gradient(113deg,#172946,#2a426f 62%,#3659a3);color:#fff;border-radius:17px;padding:19px 23px;margin-bottom:15px;position:relative;overflow:hidden;box-shadow:0 12px 28px rgba(29,48,88,.13)}
        .expenses-hero:after{content:"";position:absolute;inset-inline-end:-72px;top:-115px;width:195px;height:195px;border-radius:50%;border:27px solid rgba(255,255,255,.045);pointer-events:none}
        .expenses-hero>div{position:relative;z-index:1}
        .expenses-eyebrow{display:inline-flex;align-items:center;gap:7px;font-size:.69rem;font-weight:800;letter-spacing:.04em;color:#c3d2f7}
        .expenses-hero h2{font-size:1.43rem;font-weight:800;color:#fff;letter-spacing:-.025em;margin:4px 0}
        .expenses-hero p{font-size:.76rem;color:#d0ddf4;margin:0}
        .expenses-hero-actions{display:flex;align-items:center;gap:9px;flex-wrap:wrap}
        .expenses-hero-actions .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;border-radius:10px;padding:9px 14px;font-size:.77rem;font-weight:800;white-space:nowrap}
        .expenses-export-btn{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.28);color:#fff}
        .expenses-export-btn:hover{background:rgba(255,255,255,.2);color:#fff}
        .expenses-add-btn{background:#fff;color:#274c8e;border:1px solid #fff;box-shadow:0 4px 12px rgba(15,27,58,.1)}
        .expenses-add-btn:hover{background:#eaf1ff;color:#1d407b}
        .expenses-page .card-soft{border:1px solid var(--expense-line);border-radius:15px;box-shadow:0 4px 17px rgba(29,48,82,.04);background:#fff}
        .expenses-page .expenses-filter-card>.card-body{padding:15px 18px}
        .expenses-page .toggle-title{font-size:.83rem;font-weight:800;color:#2a4365}
        .expenses-page .toggle-title i{color:#496eaf}
        .expenses-page #toggle-filters-summary{border-radius:9px;min-width:36px;min-height:34px;border-color:#dae3ef;color:#45638d;background:#f6f9fd}
        .expenses-page .filter-label{display:block;font-size:.7rem;font-weight:750;color:#60728c;margin-bottom:6px}
        .expenses-page .filter-section .form-control,.expenses-page #filters-summary-body .form-control,.expenses-page #filters-summary-body .form-select{height:41px;min-height:41px;background:#fff!important;border:1px solid #dce5ef!important;border-radius:9px!important;box-shadow:none!important;font-size:.79rem;color:#2d415e}
        .expenses-page #filters-summary-body .select2-container{max-width:100%}
        .expenses-page #filters-summary-body .select2-container--default .select2-selection--single{min-height:41px;height:41px;border:1px solid #dce5ef!important;border-radius:9px!important;display:flex;align-items:center;padding:4px 9px}
        .expenses-page #filters-summary-body .select2-selection__rendered{font-size:.78rem!important;color:#2d415e!important;line-height:28px!important}
        .expenses-page #filters-summary-body .select2-selection__arrow{top:7px!important}
        .expenses-page #filters-summary-body :is(.form-control,.form-select):focus{border-color:#8ba6dd!important;box-shadow:0 0 0 3px rgba(73,111,184,.11)!important}
        .expenses-page #summary-wrapper{padding-top:12px;border-top:1px solid #ebf0f5;margin-top:15px}
        .expenses-page #summary-wrapper .summary-card{padding:15px;border:1px solid #e4ebf3;background:linear-gradient(150deg,#fff,#fbfcff);border-radius:12px;box-shadow:0 3px 12px rgba(31,51,82,.03);position:relative;overflow:hidden}
        .expenses-page #summary-wrapper .summary-card:before{content:"";position:absolute;inset-inline-start:0;top:0;bottom:0;width:3px;background:#4c70b9}
        .expenses-page #summary-wrapper .summary-title{font-size:.78rem;font-weight:800;color:#304a73;margin-bottom:10px}
        .expenses-page #summary-wrapper .summary-line{border-color:#e9eef6;padding:7px 0;gap:10px}
        .expenses-page #summary-wrapper .summary-line:last-child{border-bottom:0}
        .expenses-page #summary-wrapper .label{font-size:.72rem;color:#63778e;font-weight:750}
        .expenses-page #summary-wrapper .value{font-size:.81rem;font-weight:850;color:#213d61;font-variant-numeric:tabular-nums}
        .expenses-page #summary-wrapper .total-card{background:#eff5ff;border-color:#dce7fb}
        .expenses-page #summary-wrapper .total-card:before{background:#19806a}
        .expenses-page #summary-wrapper .total-card .value{font-size:1.15rem;font-weight:900;color:#1a5484;overflow-wrap:anywhere}
        .expenses-table-title{display:flex;align-items:center;gap:11px;padding:15px 18px;border-bottom:1px solid #e9eef5}
        .expenses-table-title .expenses-table-icon{display:grid;place-items:center;width:39px;height:39px;flex:0 0 39px;border-radius:10px;background:#eaf0ff;color:#4268b0;font-size:1.05rem}
        .expenses-table-title strong{font-size:.92rem;font-weight:800;color:#263e60;display:block}
        .expenses-table-title small{font-size:.7rem;color:#7a8ba0;display:block;margin-top:2px}
        .expenses-page .expenses-table-card>.card-body{padding:14px 16px}
        .expenses-page .expenses-table-scroll{border:1px solid #e7edf5;border-radius:10px;overflow-x:auto}
        .expenses-page #expenses-table{margin-bottom:0!important;min-width:990px;border-collapse:separate;border-spacing:0}
        .expenses-page #expenses-table thead th{background:#f3f6fb;color:#61718a;font-size:.69rem;letter-spacing:.02em;font-weight:800;padding:12px 11px;white-space:nowrap;border-bottom:1px solid #e8edf5}
        .expenses-page #expenses-table tbody td{font-size:.76rem;color:#40536f;padding:12px 11px;vertical-align:middle;border-bottom:1px solid #edf1f6}
        .expenses-page #expenses-table tbody tr:hover td{background:#fafcff}
        .expenses-page #expenses-table td.text-end{font-weight:800;font-variant-numeric:tabular-nums}
        .expenses-page .dataTables_wrapper .dataTables_filter input,.expenses-page .dataTables_wrapper .dataTables_length select{border:1px solid #dbe4f0;border-radius:8px;padding:6px 9px;min-height:35px;font-size:.76rem;background:#fff}
        .expenses-page .dataTables_wrapper .dataTables_filter,.expenses-page .dataTables_wrapper .dataTables_length,.expenses-page .dataTables_wrapper .dataTables_info,.expenses-page .dataTables_wrapper .dataTables_paginate{font-size:.74rem;color:#677993}
        .expenses-page .dataTables_wrapper>.row{row-gap:10px}
        .expenses-modal-content{border:1px solid #e4eaf4;border-radius:15px;overflow:hidden;box-shadow:0 20px 52px rgba(23,43,79,.16)}
        .expenses-modal-content .modal-header{padding:16px 19px;background:#f9fbff;border-color:#e8eef5}
        .expenses-modal-content .modal-title{font-size:.99rem;font-weight:800;color:#263d5f}
        .expenses-modal-content .modal-title i{color:#496cb0}
        .expenses-modal-content .modal-body{padding:18px 20px;max-height:min(70vh,720px);overflow-y:auto}
        .expenses-modal-content .form-label{font-size:.72rem;font-weight:750;color:#5a6d87;margin-bottom:6px}
        .expenses-modal-content .form-control,.expenses-modal-content .form-select{min-height:41px;border:1px solid #dce4ef;border-radius:9px;background:#fff;font-size:.79rem;color:#2a405d;box-shadow:none}
        .expenses-modal-content textarea.form-control{min-height:80px;height:auto}
        .expenses-modal-content input[inputmode="decimal"]{font-variant-numeric:tabular-nums;font-weight:800}
        .expenses-modal-content .select2-container--default .select2-selection--single{min-height:41px;height:41px;display:flex;align-items:center;padding:5px 9px;border:1px solid #dce4ef!important;border-radius:9px!important}
        .expenses-modal-content .select2-selection__rendered{font-size:.79rem!important;color:#2a405d!important}
        .expenses-modal-content .modal-footer{padding:14px 20px;border-color:#e9eef5}
        .expenses-modal-content .modal-footer .btn{min-height:39px;border-radius:9px;font-weight:800;font-size:.78rem;padding:8px 18px}
        .expenses-modal-content .modal-footer .btn-primary{background:#3158a7;border-color:#3158a7}
        .expenses-page :is(button,a,input,select):focus-visible{outline:3px solid #96b0e5;outline-offset:2px}
        @media(max-width:767px){.expenses-hero{padding:16px;align-items:flex-start}.expenses-hero h2{font-size:1.2rem}.expenses-hero p{font-size:.7rem}.expenses-hero-actions{justify-content:flex-end}.expenses-hero-actions .btn{padding:9px}.expenses-hero-actions .btn span{display:none}.expenses-page .expenses-filter-card>.card-body{padding:13px}.expenses-page .expenses-table-card>.card-body{padding:10px}.expenses-modal-content .modal-body{padding:14px}}
        @media(max-width:576px){.expenses-page #expenses-table{min-width:0}.expenses-page #expenses-table thead{display:none}.expenses-page #expenses-table tbody tr{display:block;border:1px solid #e5ecf5;border-radius:11px;margin-bottom:10px;overflow:hidden}.expenses-page #expenses-table tbody td{display:flex;justify-content:space-between;gap:12px;white-space:normal;overflow-wrap:anywhere;padding:10px 12px;text-align:end}.expenses-page #expenses-table tbody td:before{color:#697d97;font-weight:750;font-size:.71rem;text-align:start;flex:0 0 37%}.expenses-page .expenses-table-scroll{border:none}}
    </style>
@endsection

@section('content')
    <div class="expenses-page">
        <header class="expenses-hero">
            <div>
                <span class="expenses-eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i>{{ __('expenses.title') }}</span>
                <h2>{{ __('ui.expenses_title') }}</h2>
                <p>{{ __('expenses.filters_summary') }}</p>
            </div>
            <div class="expenses-hero-actions">
                <button type="button" id="btn-export-expenses" class="btn expenses-export-btn">
                    <i class="bi bi-filetype-pdf" aria-hidden="true"></i><span>{{ __('ui.export_pdf') }}</span>
                </button>
                @can('create expenses')
                    <button type="button" class="btn expenses-add-btn" data-bs-toggle="modal" data-bs-target="#expenseModal">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i><span>{{ __('ui.add_expense_btn') }}</span>
                    </button>
                @endcan
            </div>
        </header>
    <div class="row g-3">

         {{-- FILTERS + SUMMARY (ONE TOGGLE CARD) --}}
        <div class="col-12">
            <div class="card card-soft expenses-filter-card mb-2">
                <div class="card-body">
                    <div class="toggle-card-header">
                        <div>
                            <p class="toggle-title"><i class="bi bi-funnel me-2" aria-hidden="true"></i>{{ __('expenses.filters_summary') }}</p>
                        </div>

                        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" id="toggle-filters-summary" type="button">
                            <i class="bi bi-chevron-up" id="filters-summary-icon"></i>
                            <span class="d-none d-sm-inline"></span>
                            <span class="d-inline d-sm-none"></span>
                        </button>
                    </div>

                    <div id="filters-summary-body" class="pt-3">

                        {{-- FILTERS --}}
                        <div class="row g-2 align-items-end compact-gap">
                            <div class="col-6 col-md-4 col-xl">
                                <label for="filter-date-from" class="filter-label">{{ __('ui.date_from') }}</label>
                                <input type="date" id="filter-date-from" class="form-control">
                            </div>

                            <div class="col-6 col-md-4 col-xl">
                                <label for="filter-date-to" class="filter-label">{{ __('ui.date_to') }}</label>
                                <input type="date" id="filter-date-to" class="form-control">
                            </div>

                            <div class="col-6 col-md-4 col-xl">
                                <label for="filter-currency" class="filter-label">{{ __('ui.currency') }}</label>
                                <select id="filter-currency" class="select2 form-select">
                                    <option value="">{{ __('ui.all') }}</option>
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6 col-md-6 col-xl">
                                <label for="filter-status" class="filter-label">{{ __('ui.status') }}</label>
                                <select id="filter-status" class="select2 form-select">
                                    <option value="">{{ __('ui.all') }}</option>
                                    <option value="approved">{{ __('ui.approved') }}</option>
                                    <option value="pending">{{ __('ui.pending') }}</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 col-xl">
                                <label for="filter-expense-category" class="filter-label">{{ __('ui.expense_category') }}</label>
                                <select id="filter-expense-category" class="select2 form-select">
                                    <option value="">{{ __('ui.all') }}</option>
                                    @foreach ($expenseCategories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- SUMMARY --}}
                        <div class="mt-3">
                            <div id="summary-wrapper">
                                <div class="text-muted py-3 text-center">{{ __('ui.loading_summary') }}</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="col-12">
            <div class="card card-soft expenses-table-card">
                <div class="expenses-table-title">
                    <span class="expenses-table-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                    <div><strong>{{ __('ui.expenses_title') }}</strong><small>{{ __('expenses.filters_summary') }}</small></div>
                </div>
                <div class="card-body">
                    <div class="table-responsive expenses-table-scroll">
                        <table id="expenses-table" class="table-striped table-hover w-100 table align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('ui.date') }}</th>
                                    <th>{{ __('ui.category') }}</th>
                                    <th>{{ __('ui.description') }}</th>
                                    <th>{{ __('expenses.payment_amount') }}</th>
                                    <th class="text-end">{{ __('ui.amount') }}</th>
                                    <th>{{ __('ui.status') }}</th>
                                    <th>{{ __('ui.attachment') }}</th>
                                    <th>{{ __('ui.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
    </div>

    {{-- MODAL --}}
    <div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form id="expense-form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="expense-id" name="id">
                <input type="hidden" name="expense_type" id="expense_type" value="company">
                <div class="modal-content expenses-modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-receipt-cutoff me-2" aria-hidden="true"></i>{{ __('ui.add_expense_btn') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">{{ __('ui.expense_date') }}</label>
                                <input type="date" id="expense_date" name="expense_date" class="form-control" value="{{ now()->toDateString() }}" required>
                            </div>

                            <div class="col-6">
                                <label class="form-label">{{ __('ui.expense_category') }}</label>
                                <select name="expense_category_id" id="expense_category_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_category') }}</option>
                                    @foreach ($expenseCategories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('ui.account') }}</label>
                                <select name="account_id" id="account_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_account') }}</option>
                                    @foreach ($cashAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label">{{ __('expenses.payment_currency') }}</label>
                                <select name="payment_currency_id" id="payment_currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label">{{ __('expenses.payment_amount') }}</label>
                                <input type="text" step="any" min="0.01" id="payment_amount" name="payment_amount" class="form-control numeric-input"
                                    inputmode="decimal">
                            </div>

                            <div class="col-6">
                                <label class="form-label">{{ __('ui.rate') }}</label>
                                <input type="text" step="0.0001" id="rate" name="rate" class="form-control" value="1" inputmode="decimal">
                            </div>

                            <div class="col-6">
                                <label class="form-label">{{ __('ui.operation') }}</label>
                                <select name="operation_type" id="operation_type" class="form-select">
                                    <option value="multiply">{{ __('expenses.multiply') }}</option>
                                    <option value="divide">{{ __('expenses.divide') }}</option>
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label">{{ __('ui.currency') }}</label>
                                <select name="currency_id" id="currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label">{{ __('ui.amount') }}</label>
                                <input type="text" step="any" min="0.01" id="amount" name="amount" class="form-control numeric-input" inputmode="decimal"
                                    required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('ui.description') }}</label>
                                <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('ui.attachment_optional') }}</label>
                                <input type="file" id="attachment" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            </div>

                            <div class="col-12" id="existing-attachment" style="display:none;">
                                <label class="form-label">{{ __('ui.current_attachment') }}</label><br>
                                <a href="#" id="existing-attachment-link" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-paperclip"></i> {{ __('ui.view_attachment') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer pt-3">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('ui.close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-text">{{ __('ui.save') }}</span>
                            <span class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js')
    {{-- DataTables & Select2 (NOT in base) --}}
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('dist/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js') }}"></script>

    <script>
        const expensesSwal = Swal.mixin({confirmButtonText: @json(__('ui.blade_ok')), cancelButtonText: @json(__('ui.cancel'))});
        $(document).ready(function() {

            // normal select2 (outside modal)
            $('.select2').not('#expenseModal .select2').select2({
                language: @include('admin.expenses.partials.select-language'),
                width: '100%'
            });

            // select2 inside modal
            $('#expenseModal').on('shown.bs.modal', function() {
                $(this).find('.select2').select2({
                    language: @include('admin.expenses.partials.select-language'),
                width: '100%',
                    dropdownParent: $('#expenseModal')
                });
            });
            $('#payment_currency_id').on('change', function() {
                let selected = $(this).val();

                if (!$('#currency_id').val()) {
                    $('#currency_id').val(selected).trigger('change');
                }
            });

            let isUpdating = false;

function calculateAmountFromPayment() {
    if (isUpdating) return;
    isUpdating = true;

    let payment = parseFormattedNumber($('#payment_amount').val()) || 0;
    let rate = parseFormattedNumber($('#rate').val()) || 1;
    let op = $('#operation_type').val();

    let result = (op === 'multiply') ? payment * rate : payment / rate;

    $('#amount').val(result.toFixed(2));

    isUpdating = false;
}

function calculatePaymentFromAmount() {
    if (isUpdating) return;
    isUpdating = true;

    let amount = parseFormattedNumber($('#amount').val()) || 0;
    let rate = parseFormattedNumber($('#rate').val()) || 1;
    let op = $('#operation_type').val();

    let result = (op === 'multiply') ? amount / rate : amount * rate;

    $('#payment_amount').val(result.toFixed(2));

    isUpdating = false;
}

            // Main behavior you asked for:
            // payment_amount or rate or operation_type changes -> amount changes
            $('#payment_amount, #rate, #operation_type').on('keyup change', function() {
                calculateAmountFromPayment();
            });

            // Optional: if amount changes manually, update payment_amount back
            $('#amount').on('keyup change', function() {
                calculatePaymentFromAmount();
            });
        });


        $(function() {
            // ----------------------------
            // Helpers
            // ----------------------------
            function showSwal(icon, title, html) {
                expensesSwal.fire({
                    icon: icon,
                    title: title,
                    text: html,
                    confirmButtonText: '{{ __('ui.blade_ok') }}'
                });
            }

            const todayDefault = "{{ now()->toDateString() }}";

            // ----------------------------
            // Toggle Filters + Summary (ONE CARD)
            // ----------------------------
            $('#toggle-filters-summary').on('click', function() {
                const body = $('#filters-summary-body');
                const icon = $('#filters-summary-icon');

                body.slideToggle(200);

                if (icon.hasClass('bi-chevron-down')) {
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
                } else {
                    icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
                }
            });

            // ----------------------------
            // DataTable
            // ----------------------------
            const table = $('#expenses-table').DataTable({
                language: @include('admin.expenses.partials.table-language'),
                processing: true,
                serverSide: true,
                responsive: false,
                ajax: {
                    url: "{{ route('admin.expenses.data') }}",
                    data: function(d) {
                        d.date_from = $('#filter-date-from').val();
                        d.date_to = $('#filter-date-to').val();
                        d.currency_id = $('#filter-currency').val();
                        d.status = $('#filter-status').val();
                        d.expense_category_id = $('#filter-expense-category').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'expense_date',
                        name: 'expense_date'
                    },
                    {
                        data: 'expense_category_name',
                        name: 'expenseCategory.name'
                    },
                    {
                        data: 'description',
                        render: function(d) {
                            if (!d) return '—';
                            const text = String(d);
                            return escapeExpenseText(text.length > 40 ? text.substring(0, 40) + '…' : text);
                        }
                    },
                    {
                        data: 'payment_amount',
                        name: 'payment_amount',
                        className: 'text-end'
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        className: 'text-end'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'attachment',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [
                    [1, 'desc']
                ],
                drawCallback: function() {
                    // Mobile “card rows”: set data-label for each td based on header titles
                    if (window.matchMedia && window.matchMedia('(max-width: 576px)').matches) {
                        const labels = [
                            '#',
                            @json(__('ui.date')),
                            @json(__('ui.category')),
                            @json(__('ui.description')),
                            @json(__('expenses.payment_amount')),
                            @json(__('ui.amount')),
                            @json(__('ui.status')),
                            @json(__('ui.attachment')),
                            @json(__('ui.actions'))
                        ];

                        $('#expenses-table tbody tr').each(function() {
                            $(this).find('td').each(function(idx) {
                                $(this).attr('data-label', labels[idx] ?? '');
                            });
                        });
                    }
                }
            });

            const escapeExpenseText = value => String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            })[char]);

            // ----------------------------
            // Summary Loader
            // ----------------------------
            function loadSummary() {
                $("#summary-wrapper").html(`<div class="text-muted py-3 text-center">{{ __('ui.loading_summary') }}</div>`);

                $.ajax({
                    url: "{{ route('admin.expenses.summary') }}",
                    method: "GET",
                    data: {
                        date_from: $('#filter-date-from').val(),
                        date_to: $('#filter-date-to').val(),
                        currency_id: $('#filter-currency').val(),
                        status: $('#filter-status').val(),
                        expense_category_id: $('#filter-expense-category').val(),
                    },
                    success: function(res) {
                        let html = `<div class="row g-3 mb-2">`;

                        (res.perCategory || []).forEach(cat => {
                            html += `
                <div class="col-12 col-sm-6 col-lg-3">
                  <div class="summary-card">
                    <div class="summary-title">${escapeExpenseText(cat.category)}</div>
              `;

                            const totals = cat.totals || {};
                            Object.entries(totals).forEach(([currency, amount]) => {
                                const num = parseFormattedNumber(amount || 0);
                                html += `
                  <div class="summary-line">
                    <span class="label">${escapeExpenseText(currency)}</span>
                    <span class="value">${num.toLocaleString()}</span>
                  </div>
                `;
                            });

                            html += `</div></div>`;
                        });

                        html += `</div>`;

                        html += `<div class="row g-3 mt-2">`;
                        const totalsRow = res.totals || {};
                        Object.entries(totalsRow).forEach(([currency, amount]) => {
                            const num = parseFormattedNumber(amount || 0);
                            html += `
                <div class="col-12 col-sm-6 col-lg-3">
                  <div class="summary-card total-card">
                    <div class="summary-title">{{ __('expenses.total') }} ${escapeExpenseText(currency)}</div>
                    <div class="value">${num.toLocaleString()}</div>
                  </div>
                </div>
              `;
                        });
                        html += `</div>`;

                        $("#summary-wrapper").html(html);
                    },
                    error: function() {
                        $("#summary-wrapper").html(`<div class="text-danger py-3 text-center">{{ __('ui.blade_failed_to_load_summary') }}</div>`);
                    }
                });
            }

            // initial load
            loadSummary();

            // Filters change => table + summary
            $('#filter-date-from, #filter-date-to, #filter-currency, #filter-status, #filter-expense-type, #filter-expense-category')
                .on('change', function() {
                    table.ajax.reload();
                    loadSummary();
                });

            // ----------------------------
            // Modal + Form
            // ----------------------------
            const modalEl = document.getElementById('expenseModal');
            const modal = new bootstrap.Modal(modalEl);

            function resetForm() {
                $('#expense-id').val('');
                $('#expense-form')[0].reset();
                $('#existing-attachment').hide();

                $('#expense_date').val(todayDefault);
                $('#expense_category_id').val('').trigger('change');
            }

            modalEl.addEventListener('hidden.bs.modal', resetForm);

            // Create / Update
            $('#expense-form').on('submit', function(e) {
                e.preventDefault();

                const id = $('#expense-id').val();
                const url = id ? `/admin/expenses/${id}` : `{{ route('admin.expenses.store') }}`;

                // 🔥 Normalize all numeric inputs FIRST
                $('#expense-form .numeric-input').each(function() {
                    this.value = convertToEnglishNumbers(this.value);
                });

                // 🔥 Force correct names (protect from mutation)
                $('#amount').attr('name', 'amount');
                $('#payment_amount').attr('name', 'payment_amount');

                // 🔥 Validate BEFORE FormData
                let rawAmount = $('#amount').val();
                let amount = parseFormattedNumber(rawAmount);

                if (!rawAmount || isNaN(amount) || amount <= 0) {
                    expensesSwal.fire({
                        icon: 'warning',
                        title: '{{ __('ui.blade_amount_required') }}',
                        text: '{{ __('ui.blade_please_enter_a_valid_amount') }}'
                    });
                    $('#amount').focus();
                    return;
                }

                // optional: validate payment amount too
                let rawPayment = $('#payment_amount').val();
                if (rawPayment) {
                    let payment = parseFormattedNumber(rawPayment);
                    if (isNaN(payment)) {
                        expensesSwal.fire({
                            icon: 'warning',
                            title: '{{ __('ui.blade_invalid_payment_amount') }}'
                        });
                        return;
                    }
                }

                const fd = new FormData(this);
                if (id) fd.append('_method', 'PUT');

                const btn = $('#expense-form button[type="submit"]');
                btn.prop('disabled', true);
                btn.find('.btn-text').addClass('d-none');
                btn.find('.spinner-border').removeClass('d-none');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        modal.hide();

                        expensesSwal.fire({
                            icon: 'success',
                            title: (res && res.message) ? res.message : @json(__('expenses.saved')),
                            timer: 1000,
                            showConfirmButton: false
                        });

                        table.ajax.reload(null, false);
                        loadSummary();
                    },
                    error: function(xhr) {
                        let msg = @json(__('expenses.error_message'));
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            msg = Object.values(errors).flat().join('<br>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }

                        showSwal('error', @json(__('expenses.validation_error')), msg);
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                        btn.find('.btn-text').removeClass('d-none');
                        btn.find('.spinner-border').addClass('d-none');
                    }
                });
            });

            // Edit
            $('#expenses-table').on('click', '.btn-edit', function() {
                const row = table.row($(this).closest('tr')).data();
                if (!row) return;

                $('#expense-id').val(row.id);
                $('#amount').val(row.amount_raw ?? row.amount);
                $('#description').val(row.description || '');
                $('#expense_date').val(row.expense_date || todayDefault);

                $('#expense_category_id').val(row.expense_category_id || '').trigger('change');

                if (row.attachment_url) {
                    $('#existing-attachment-link').attr('href', row.attachment_url);
                    $('#existing-attachment').show();
                } else {
                    $('#existing-attachment').hide();
                }

                modal.show();
            });

            // Delete
            $('#expenses-table').on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                if (!id) return;

                expensesSwal.fire({
                    title: '{{ __('ui.blade_delete_this_expense') }}',
                    text: '{{ __('ui.action_cannot_be_undone') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '{{ __('ui.blade_yes_delete') }}',
                    cancelButtonText: '{{ __('ui.cancel') }}',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: `/admin/expenses/${id}`,
                        method: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {
                            expensesSwal.fire({
                                icon: 'success',
                                title: (res && res.message) ? res.message : @json(__('expenses.deleted')),
                                timer: 1000,
                                showConfirmButton: false
                            });
                            table.ajax.reload(null, false);
                            loadSummary();
                        },
                        error: function() {
                            showSwal('error', @json(__('expenses.error')), @json(__('expenses.delete_failed')));
                        }
                    });
                });
            });

            // -----------------------
            // EXPORT EXPENSES AS PDF
            // -----------------------
            $('#btn-export-expenses').on('click', function() {

                let params = new URLSearchParams({
                    date_from: $('#filter-date-from').val(),
                    date_to: $('#filter-date-to').val(),
                    currency_id: $('#filter-currency').val(),
                    status: $('#filter-status').val(),
                    expense_category_id: $('#filter-expense-category').val(),
                });

                window.open("{{ route('admin.expenses.export') }}?" + params.toString(), "_blank");
            });

        });
    </script>
@endsection
