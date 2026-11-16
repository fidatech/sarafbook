@extends('layouts.admin.base')

@section('title', __('ui.account_statement'))

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/ad2e98fea92964b2.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/febd1387d851569a.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/3218bf0b4c8ec67e.css') }}" />
    <style>
        .statement-table {
            width: 100%;
            min-width: 1240px;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
        }

        .statement-table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
            border-bottom: 1px solid #e2e8f0;
            padding: 9px 8px;
            white-space: nowrap;
        }

        .statement-table tbody td {
            padding: 8px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .statement-table tbody tr:hover {
            background: #f8fafc;
        }

        .statement-table .note-cell {
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: unset !important;
            line-height: 1.5;
            color: #334155;
        }

        .statement-table .amount-cell,
        .statement-table .balance-cell {
            font-weight: 800;
        }

        .statement-table .date-cell {
            font-size: 11px;
            color: #64748b;
        }

        .statement-table .badge {
            font-size: 10px;
            padding: 5px 7px;
            border-radius: 999px;
        }

        .table-responsive.statement-table-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow-x: auto;
            overflow-y: hidden;
            background: #fff;
            scrollbar-gutter: stable;
        }

        /* Account statement action buttons */
.statement-actions {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    white-space: nowrap;
    width: 100%;
    max-width: 140px;
}

.statement-action-btn {
    width: 30px;
    height: 30px;
    border-radius: 9px !important;
    border: 1px solid transparent;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 !important;
    font-size: 13px;
    line-height: 1;
    box-shadow: none !important;
    transition: all .15s ease-in-out;
}

.statement-action-btn:hover {
    transform: translateY(-1px);
}

.statement-action-btn.whatsapp {
    background: #ecfdf5;
    color: #16a34a;
    border-color: #bbf7d0;
}

.statement-action-btn.whatsapp:hover {
    background: #16a34a;
    color: #fff;
}

.statement-action-btn.print {
    background: #f3f4f6;
    color: #374151;
    border-color: #d1d5db;
}

.statement-action-btn.print:hover {
    background: #374151;
    color: #fff;
}

.statement-action-btn.edit {
    background: #eff6ff;
    color: #2563eb;
    border-color: #bfdbfe;
}

.statement-action-btn.edit:hover {
    background: #2563eb;
    color: #fff;
}

.statement-action-btn.delete {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}

.statement-action-btn.delete:hover {
    background: #dc2626;
    color: #fff;
}

.statement-table .action-cell {
    text-align: center;
    width: 160px !important;
    min-width: 160px;
    overflow: hidden !important;
    text-overflow: clip !important;
}

.statement-table thead th:last-child {
    width: 160px !important;
    min-width: 160px;
    text-align: center;
}

@media (max-width: 767.98px) {
    .table-responsive.statement-table-wrap {
        padding: 12px !important;
    }
}

.transaction-color-dot{display:inline-block;width:18px;height:18px;border-radius:6px;border:1px solid rgba(15,23,42,.12);vertical-align:middle}.transaction-color-dot.empty{background:#fff;border-style:dashed}.transaction-bulk-bar{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:10px 12px}.statement-table tbody tr.transaction-row-colored,.statement-table tbody tr.transaction-row-colored:hover{--bs-table-bg:var(--transaction-row-color)!important;--bs-table-accent-bg:var(--transaction-row-color)!important;--bs-table-hover-bg:var(--transaction-row-color)!important;background-color:var(--transaction-row-color)!important}.statement-table tbody tr.transaction-row-colored>td,.statement-table tbody tr.transaction-row-colored:hover>td{background-color:var(--transaction-row-color)!important;box-shadow:none!important}.transaction-ok-toggle,.transaction-select,#select-all-transactions{cursor:pointer}

        .summary-bar {
            background: linear-gradient(135deg, #f8f9ff, #eef1ff);
            border: 1px solid #e2e6ff;
            border-radius: 14px;
            padding: 14px 16px;
        }

        .summary-item {
            background: white;
            border-radius: 12px;
            padding: 12px 14px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .06);
            min-width: 180px;
        }

        .summary-item .label {
            font-size: .75rem;
            text-transform: uppercase;
            color: #6c757d;
            letter-spacing: .05em;
        }

        .summary-item .value {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .currency-badge {
            background: #fff;
            border-radius: 10px;
            padding: 6px 10px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .08);
            font-size: .85rem;
        }

        .currency-badge span {
            font-weight: 600;
        }

        .mode-box {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            padding-top: 10px;
        }

        .mode-box label {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            height: 38px;
            display: flex;
            align-items: center;
            border-radius: 8px;
        }

        .select2-selection__rendered {
            line-height: normal !important;
        }

        .select2-selection__arrow {
            height: 100% !important;
        }

        .card-modern {
            border: none;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header-modern {
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 18px;
        }

        .card-body-modern {
            padding: 18px;
        }

        .section-title {
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            margin: 4px 0 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title:after {
            content: "";
            height: 1px;
            background: #e2e8f0;
            flex: 1;
        }

        .form-label.small {
            font-weight: 700;
            color: #334155;
        }

        .balance-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .balance-group {
            width: 100%;
            padding: 10px 12px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .balance-group-title {
            font-size: .78rem;
            font-weight: 900;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .balance-chip {
            border-radius: 10px;
            padding: 10px 12px;
            background: #f1f5f9;
            border-left: 4px solid #3b82f6;
            min-width: 140px;
        }

        .balance-chip .cur {
            font-size: .72rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .mono {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
        }

        .balance-amount {
            font-weight: 900;
            margin-top: 2px;
        }

        .bal-neg {
            color: #dc2626;
        }

        .bal-pos {
            color: #0f172a;
        }

        .btn-pill {
            border-radius: 999px !important;
            padding: .55rem 1.05rem;
            font-weight: 800;
        }

        .mode-box {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            padding-top: 10px;
        }

        .mode-box label {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-edit,
        .btn-delete {
            border-radius: 8px;
            padding: 4px 8px;
        }
    /* Account statement bulk actions stay on one row, including on narrow screens. */
        #account-transaction-bulk-bar {
            display: flex; flex-wrap: nowrap !important; align-items: center; gap: 12px;
            width: 100%; min-width: 0; overflow-x: auto; padding: 10px 12px;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            box-shadow: 0 2px 8px rgba(15,23,42,.04); scrollbar-width: thin;
        }
        #account-transaction-bulk-bar > * { flex: 0 0 auto; white-space: nowrap; }
        #account-transaction-bulk-bar .bulk-group { display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; }
        #account-transaction-bulk-bar .bulk-selection { gap: 8px; font-size: 12px; font-weight: 600; color: #64748b; }
        #account-transaction-bulk-bar .selected-transaction-count {
            display: inline-flex; align-items: center; justify-content: center; min-width: 28px;
            height: 28px; padding: 0 7px; border-radius: 8px; background: #f1f5f9; color: #475569;
            font-variant-numeric: tabular-nums;
        }
        #account-transaction-bulk-bar.has-selection .selected-transaction-count { background: #dbeafe; color: #1d4ed8; }
        #account-transaction-bulk-bar .bulk-divider { width: 1px; height: 24px; background: #e2e8f0; }
        #account-transaction-bulk-bar .btn { height: 32px; border-radius: 7px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        #account-transaction-bulk-bar .bulk-color { width: 135px; height: 32px; font-size: 12px; border-radius: 7px; }
        #account-transaction-bulk-bar .bulk-reset { margin-inline-start: auto; }
        #account-transaction-bulk-bar .btn:disabled { opacity: .4; }
        #account-transaction-bulk-bar :focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }

        </style>
@endsection

@section('content')
    @php
        $hideRemittanceFields = (int) $account->account_sub_category_id === 1;
    @endphp
    {{-- Account Details Modal --}}
    <div class="modal fade" id="accountDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('ui.account_details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="fw-semibold">{{ __('ui.code') }}</div>
                            <div class="text-muted">{{ $account->code }}</div>
                        </div>
                        <div class="col-6">
                            <div class="fw-semibold">{{ __('ui.name') }}</div>
                            <div class="text-muted">{{ $account->name }}</div>
                        </div>
                        @if ($account->subCategory)
                            <div class="col-12">
                                <div class="fw-semibold">{{ __('ui.account_type') }}</div>
                                <div class="text-muted">{{ __('accounts.types')[$account->subCategory->name] ?? $account->subCategory->name }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header border-bottom bg-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">{{ __('ui.account_statement') }}</h5>
                    <small class="text-muted fw-bold">{{ $account->name }} ({{ $account->code }})</small>
                </div>
                <div class="d-flex gap-2">

                    <button class="btn btn-sm btn-success btn-new-transaction" data-bs-toggle="modal" data-bs-target="#transactionModal">
                        <i class="bi bi-plus-circle"></i>
                    </button>

                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#exchangeModal">
                        <i class="bi bi-arrow-left-right"></i>
                    </button>

                    <button class="btn btn-sm btn-danger btn-new-outgoing-remittance" data-bs-toggle="modal" data-bs-target="#outgoingRemittanceModal">
                        <i class="bi bi-arrow-up-right-square"></i>
                    </button>
                    @if ($account->account_sub_category_id == 2)
                        <button class="btn btn-sm btn-success btn-new-incoming-remittance" data-bs-toggle="modal" data-bs-target="#incomingRemittanceModal">
                            <i class="bi bi-arrow-down-left-square"></i>
                        </button>
                    @endif

                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-success" id="btn-whatsapp-balance" title="{{ __('ui.blade_send_balance_to_whatsapp') }}">
                        <i class="bi bi-whatsapp"></i>
                    </button>

                    <button class="btn btn-sm btn-outline-secondary" title="{{ __('ui.account_details') }}" data-bs-toggle="modal" data-bs-target="#accountDetailsModal">
                        <i class="bi bi-person-badge"></i>
                    </button>

                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#currencyModal">
                        <i class="bi bi-file-pdf"></i>
                    </button>

                    <button class="btn btn-sm btn-dark" data-bs-toggle="modal" title="{{ __('accounts.actual_report') }}" data-bs-target="#actualReportModal">
                        <i class="bi bi-bar-chart"></i>
                    </button>
                </div>
            </div>

        </div>

        <div class="card-body">

            {{-- Filters --}}
            <div class="row g-2 align-items-end mb-3 pt-3">
                <div class="col-md-2">
                    <label class="form-label mb-1">{{ __('ui.currency') }}</label>
                    <select id="filter-currency" class="form-select-sm form-select">
                        <option value="">{{ __('ui.all') }}</option>
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->id }}">{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">{{ __('ui.type') }}</label>
                    <select id="filter-type" class="form-select-sm form-select">
                        <option value="">{{ __('ui.all') }}</option>
                        <option value="credit">{{ __('ui.credit') }}</option>
                        <option value="debit">{{ __('ui.debit') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">{{ __('accounts.color') }}</label>
                    <select id="filter-color" class="form-select-sm form-select">
                        <option value="">{{ __('accounts.all_colors') }}</option><option value="none">{{ __('accounts.no_color') }}</option><option value="#FEF3C7">{{ __('ui.bulk_toolbar.yellow') }}</option><option value="#DCFCE7">{{ __('ui.bulk_toolbar.green') }}</option><option value="#DBEAFE">{{ __('ui.bulk_toolbar.blue') }}</option><option value="#FCE7F3">{{ __('ui.bulk_toolbar.pink') }}</option><option value="#FEE2E2">{{ __('ui.bulk_toolbar.red') }}</option><option value="#EDE9FE">{{ __('ui.bulk_toolbar.purple') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">{{ __('accounts.ok_status') }}</label>
                    <select id="filter-is-ok" class="form-select-sm form-select"><option value="">{{ __('ui.all') }}</option><option value="1">{{ __('accounts.ok') }}</option><option value="0">{{ __('accounts.not_ok') }}</option></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">{{ __('ui.date_range') }}</label>
                    <input id="filter-date-range" class="form-control form-control-sm">
                </div>
                <div class="col-md-1 d-grid">
                    <button id="filter-reset" class="btn btn-sm btn-outline-primary" style="height: 41px; margin-bottom:2px;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>

            <div id="account-transaction-bulk-bar" class="transaction-bulk-bar mt-2 mb-2" role="group" aria-label="{{ __('ui.bulk_toolbar.actions') }}">
                <div class="bulk-group bulk-selection" role="status" aria-live="polite" aria-atomic="true">
                    <span class="selected-transaction-count">0</span>
                    <span>{{ __('ui.bulk_toolbar.selected') }}</span>
                </div>
                <span class="bulk-divider" aria-hidden="true"></span>
                <div class="bulk-group">
                    <select class="form-select form-select-sm bulk-color" aria-label="{{ __('ui.choose_color') }}">
                        <option value="">{{ __('ui.choose_color') }}</option>
                        @foreach (['#FEF3C7' => 'yellow', '#DCFCE7' => 'green', '#DBEAFE' => 'blue', '#FCE7F3' => 'pink', '#FEE2E2' => 'red', '#EDE9FE' => 'purple'] as $color => $label)
                            <option value="{{ $color }}">{{ __('ui.bulk_toolbar.'.$label) }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-sm btn-primary apply-bulk-color" disabled><i class="bi bi-palette me-1" aria-hidden="true"></i>{{ __('ui.bulk_toolbar.apply') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary clear-bulk-color" disabled><i class="bi bi-eraser me-1" aria-hidden="true"></i>{{ __('ui.bulk_toolbar.clear') }}</button>
                </div>
                <span class="bulk-divider" aria-hidden="true"></span>
                <div class="bulk-group">
                    <button type="button" class="btn btn-sm btn-outline-success bulk-ok" data-ok="1" disabled><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>{{ __('ui.bulk_toolbar.ok') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-warning bulk-ok" data-ok="0" disabled><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ __('ui.bulk_toolbar.not_ok') }}</button>
                </div>
                <button type="button" class="btn btn-sm btn-light bulk-reset" disabled><i class="bi bi-x-lg me-1" aria-hidden="true"></i>{{ __('ui.bulk_toolbar.deselect') }}</button>
            </div>

            {{-- SUMMARY BAR --}}
            <div class="summary-bar mb-3">
                <div class="d-flex ms-auto flex-wrap gap-3" id="currency-breakdown"></div>

            </div>

            {{-- Transactions Table --}}
            <div class="table-responsive statement-table-wrap p-4">
                <table class="table-sm table-hover statement-table table text-nowrap align-middle" id="current-table">
                    <thead>
                        <tr>
                            <th style="width:3%"><input type="checkbox" class="form-check-input" id="select-all-transactions"></th>
                            <th style="width:4%">#</th>
                            <th style="width:12%">{{ __('ui.date') }}</th>
                            <th style="width:31%">{{ __('ui.note') }}</th>
                            <th style="width:8%">{{ __('ui.type') }}</th>
                            <th style="width:13%">{{ __('accounts.payment_amount') }}</th>
                            <th style="width:13%">{{ __('ui.amount') }}</th>
                            <th style="width:13%">{{ __('ui.balance') }}</th>
                            <th style="width:5%">{{ __('accounts.color') }}</th>
                            <th style="width:5%">{{ __('accounts.ok') }}</th>
                            <th style="width:160px">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>

    <div class="modal fade" id="actualReportModal" tabindex="-1">
        <div class="modal-dialog modal-xl" style="max-width: 90vw !important;">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold mb-4">{{ __('accounts.actual_report') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    {{-- TABLE --}}
                    <div class="table-responsive mb-4">
                        <table class="table-sm table-hover table align-middle" id="actual-table" style="width:100%; table-layout:fixed;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th style="width:12%">{{ __('accounts.payment_amount') }}</th>
                                    <th style="width:10%">{{ __('accounts.payment_currency') }}</th>
                                    <th style="width:15%">{{ __('accounts.actual_amount') }}</th>
                                    <th style="width:10%">{{ __('accounts.actual_currency') }}</th>
                                    <th style="width:10%">{{ __('accounts.transaction_type') }}</th>
                                    <th style="width:28%">{{ __('ui.note') }}</th>
                                    <th style="width:20%">{{ __('ui.date') }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    {{-- TOTAL CARDS --}}
                    <div class="row g-3" id="totals-row">

                        {{-- PAYMENT --}}
                        <div class="col-md-6">
                            <div class="rounded-3 h-100 p-4 shadow-sm" style="background: linear-gradient(135deg,#dcfce7,#f0fdf4);">
                                <h6 class="fw-bold text-success mb-3">{{ __('accounts.total_payment') }}</h6>
                                <div id="payment-total-cards"></div>
                            </div>
                        </div>

                        {{-- ACTUAL --}}
                        <div class="col-md-6">
                            <div class="rounded-3 h-100 p-4 shadow-sm" style="background: linear-gradient(135deg,#e0f2fe,#f0f9ff);">
                                <h6 class="fw-bold text-primary mb-3">{{ __('accounts.total_actual') }}</h6>
                                <div id="actual-total-cards"></div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>

    {{-- TRANSACTION MODAL --}}
    <div class="modal fade" id="transactionModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title">{{ __('ui.blade_new_transaction') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <form id="transactionModalForm">
                        @csrf

                        {{-- AUTO ACCOUNT --}}
                        <input type="hidden" name="account_id" value="{{ $account->id }}">

                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.account') }}</label>
                            <div class="form-control bg-light">
                                {{ $account->name }}
                            </div>
                        </div>

                        <div class="row g-2">

                            <div class="col-md-3">
                                <label>{{ __('ui.date') }}</label>
                                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>

                            <div class="col-md-3">
                                <label>{{ __('ui.type') }}</label>
                                <select name="entries[0][type]" class="form-select">
                                    <option value="credit">{{ __('ui.credit') }}</option>
                                    <option value="debit">{{ __('ui.debit') }}</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label>{{ __('ui.currency') }}</label>
                                <select name="entries[0][currency_id]" class="form-select">
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label>{{ __('ui.amount') }}</label>
                                <input type="text" step="0.01" name="entries[0][amount]" id="transaction_modal_amount" data-original-name="entries[0][amount]"
                                    class="form-control numeric-input" inputmode="decimal">
                            </div>

                            <div class="col-md-8">
                                <label>{{ __('ui.note') }}</label>
                                <input type="text" name="entries[0][note]" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label>{{ __('ui.blade_reference') }}</label>
                                <input type="text" name="reference" id="modal_reference" class="form-control" placeholder="{{ __('ui.blade_reference_voucher_no') }}">
                            </div>

                            {{-- TRACK (NEW FEATURE) --}}
                            <div class="col-md-6">
                                <label class="form-label small">{{ __('ui.mode') }}</label>
                                <div class="mode-box">
                                    <label><input type="radio" name="transaction_mode" value="track"> {{ __('ui.track') }}</label>
                                    <label><input type="radio" name="transaction_mode" value="no_track"> {{ __('ui.no_track') }}</label>
                                    <label><input type="radio" name="transaction_mode" value="treasure" checked> {{ __('ui.treasure') }}</label>
                                </div>
                            </div>

                            <div class="col-md-6 track-account-wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.track_account') }}</label>
                                <select name="track_account_id" id="track_account_id" class="select2 form-select">
                                    <option value="">{{ __('accounts.select_track_account') }}</option>
                                    @foreach ($accounts as $a)
                                        <option value="{{ $a->id }}" data-sub-category-id="{{ $a->account_sub_category_id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        <div class="mt-3 text-end">
                            <button class="btn btn-primary">{{ __('ui.save') }}</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- =========================
    EXCHANGE MODAL
    ========================= --}}
    <div class="modal fade" id="exchangeModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title">{{ __('ui.new_exchange') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <form id="exchangeModalForm">
                        @csrf

                        <input type="hidden" name="account_id" value="{{ $account->id }}">

                        {{-- ACCOUNT --}}
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.account') }}</label>
                            <div class="form-control bg-light">{{ $account->name }}</div>
                        </div>

                        {{-- DATE --}}
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>{{ __('ui.date') }}</label>
                                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        {{-- BALANCES --}}
                        <div class="exchange-balance-box mb-3">
                            <span class="text-muted small">{{ __('ui.blade_loading_balances') }}</span>
                        </div>

                        {{-- EXCHANGE CORE --}}
                        <div class="row g-3">

                            {{-- FROM --}}
                            <div class="col-md-3">
                                <label class="form-label text-danger">{{ __('ui.blade_customer_amount') }}</label>

                                <select name="from_currency_id" class="ex-currency-from ex-select2 form-select">
                                    <option value="">{{ __('ui.blade_select') }}</option>
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>

                                <input type="text" step="any" name="from_amount" class="form-control ex-from-amount numeric-input mt-2" inputmode="decimal"
                                    placeholder="0">
                            </div>

                            {{-- RATE --}}
                            <div class="col-md-3">
                                <label class="form-label">{{ __('ui.blade_calculation') }}</label>

                                <select name="operation_type" class="ex-operation form-select mb-2">
                                    <option value="multiply">{{ __('accounts.multiply') }}</option>
                                    <option value="divide">{{ __('accounts.divide') }}</option>
                                </select>

                                <label class="form-label">{{ __('ui.rate') }}</label>

                                <input type="text" step="any" name="rate" class="form-control ex-rate numeric-input" inputmode="decimal" placeholder="1.0000">

                                <div class="small text-muted ex-rate-hint mt-1">—</div>

                                <div class="mt-2">
                                    <div class="small">{{ __('ui.blade_base_rate') }} <span class="ex-base-rate">—</span></div>
                                    <div class="small">{{ __('ui.blade_spread') }} <span class="ex-spread">—</span></div>
                                    <div class="fw-bold text-success">{{ __('ui.blade_profit_2') }} <span class="ex-profit">—</span></div>
                                </div>
                            </div>

                            {{-- TO --}}
                            <div class="col-md-3">
                                <label class="form-label text-success">{{ __('ui.blade_converted_amount') }}</label>

                                <select name="to_currency_id" class="ex-currency-to ex-select2 form-select">
                                    <option value="">{{ __('ui.blade_select') }}</option>
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>

                                <input type="text" step="any" name="to_amount" class="form-control ex-to-amount numeric-input mt-2" inputmode="decimal" placeholder="0">
                            </div>

                            {{-- NOTE + MODE --}}
                            <div class="col-md-3">

                                <label>{{ __('ui.note') }}</label>
                                <textarea name="note" class="form-control mb-2"></textarea>

                                <div class="mode-box">
                                    <label><input type="radio" name="exchange_mode" value="no_track" checked> {{ __('ui.no_track') }}</label>
                                    <label>
                                        <input type="radio" name="exchange_mode" value="treasure">
                                        {{ __('ui.treasure') }}
                                    </label>
                                </div>

                                <div class="track-account-wrap mt-2" style="display:none;">
                                    <select name="track_account_id" class="ex-select2 form-select">
                                        <option value="">{{ __('ui.track_account') }}</option>
                                        @foreach ($accounts as $a)
                                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>

                        </div>

                        <div class="mt-4 text-end">
                            <button class="btn btn-primary px-4">{{ __('ui.blade_save_exchange') }}</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- =========================
    OUTGOING REMITTANCE
    ========================= --}}
    <div class="modal fade" id="outgoingRemittanceModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-send"></i> {{ __('ui.outgoing_remittances') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <form id="outgoingRemittanceForm">
                        @csrf

                        <input type="hidden" name="from_account_id" value="1">
                        <input type="hidden" name="to_account_id" value="{{ $account->id }}" id="outgoing_to_account_id">
                        <input type="hidden" name="page" value="account">
                        <input type="hidden" name="type" value="outgoing">

                        <div class="section-title">{{ __('ui.accounts') }}</div>
                        <div class="row g-3 mb-3">

                            <div class="col-md-4">
                                <label class="form-label small">{{ __('ui.selected_account') }}</label>
                                <input type="text" class="form-control bg-light" value="{{ $account->name }}" readonly>
                            </div>

                            <div class="col-md-4" id="outgoing_track_account_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.track_account') }}</label>
                                <select name="track_account_id" id="outgoing_track_account_id" class="select2 form-select">
                                    <option value="">{{ __('accounts.select_track_account') }}</option>
                                    @foreach ($accounts->whereIn('account_sub_category_id', [1, 2])->where('id', '!=', $account->id)->unique('id') as $a)
                                        <option value="{{ $a->id }}" data-sub-category-id="{{ $a->account_sub_category_id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- <div class="section-title d-none">{{ __('ui.selected_saraf_balances') }}</div>
                        <div id="outgoing-account-balance-summary d-none" class="balance-wrap mb-4">
                            <span class="text-muted small">{{ __('ui.select_from_to_account_to_view_balances') }}</span>
                        </div> --}}

                        <div class="section-title">{{ __('ui.basic') }}</div>
                        <div class="row g-3 mb-3">
                            @if ($hideRemittanceFields)
                                <input type="hidden" name="remittance_mode" value="track" id="outgoing_hidden_remittance_mode">
                            @else
                                <div class="col-md-3">
                                    <label class="form-label small">{{ __('ui.mode') }}</label>
                                    <div class="mode-box">
                                        <label>
                                            <input type="radio" name="remittance_mode" value="track">
                                            {{ __('ui.track') }}
                                        </label>

                                        <label>
                                            <input type="radio" name="remittance_mode" value="no_track" disabled>
                                            {{ __('ui.unpaid') }}
                                        </label>

                                        <label>
                                            <input type="radio" name="remittance_mode" value="treasure" checked>
                                            {{ __('ui.treasure') }}
                                        </label>
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-3">
                                <label class="form-label small">{{ __('ui.date') }}</label>
                                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>

                            @if ($hideRemittanceFields)
                                <input type="hidden" name="hawala_number" value="">
                            @else
                                <div class="col-md-3">
                                    <label class="form-label small">{{ __('ui.hawala_no') }}</label>
                                    <input type="text" name="hawala_number" class="form-control" placeholder="">
                                </div>
                            @endif

                            <div class="col-md-3" id="outgoing_hawala_no_wrap" style="display:none;">
                                <label class="form-label small">{{ __('accounts.track_hawala_no') }}</label>
                                <input type="text" name="track_hawala_number" class="form-control" placeholder="">
                            </div>
                        </div>

                        <div class="section-title">{{ __('accounts.amounts_currencies') }}</div>
                        <div class="row g-3 mb-3">

                            <div class="col-md-2">
                                <label class="form-label small text-primary">{{ __('accounts.execution_currency') }}</label>
                                <select name="partner_currency_id" id="outgoing_currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>

                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small text-primary">{{ __('accounts.execution_amount') }}</label>
                                <input type="text"  name="partner_amount" id="outgoing_partner_amount" class="form-control remittance-amount-input" inputmode="decimal"
                                    placeholder="0">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small text-danger">{{ __('accounts.saraf_currency') }}</label>
                                <select name="currency_id" id="outgoing_partner_currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>

                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small text-danger">{{ __('accounts.saraf_receipt') }}</label>
                                <input type="text"  min="0.01" name="amount" id="outgoing_amount" class="form-control remittance-amount-input" inputmode="decimal"
                                    required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small">{{ __('accounts.actual_currency') }}</label>
                                <select name="actual_currency_id" id="outgoing_actual_currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small">{{ __('accounts.actual_amount') }}</label>
                                <input type="text"  name="actual_amount" id="outgoing_actual_amount" class="form-control remittance-amount-input" inputmode="decimal"
                                    placeholder="0">
                            </div>
                        </div>

                        <div class="section-title">{{ __('ui.parties') }}</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small">{{ __('ui.sender') }}</label>
                                <input type="text" name="sender" class="form-control" placeholder="">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">{{ __('ui.receiver') }}</label>
                                <input type="text" name="receiver" class="form-control" placeholder="">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">{{ __('ui.description') }}</label>
                                <input type="text" name="description" class="form-control" placeholder="">
                            </div>
                        </div>

                        <div class="section-title">{{ __('ui.commission') }}</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label small">{{ __('ui.commission_mode') }}</label>
                                <select name="commission_mode" id="outgoing_commission_mode" class="commission-mode form-select">
                                    <option value="none">{{ __('ui.no_commission') }}</option>
                                    <option value="company">{{ __('ui.company_commission') }}</option>
                                    <option value="split">{{ __('ui.split_commission') }}</option>
                                </select>
                            </div>

                            <div class="col-md-3" id="outgoing_commission_currency_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.commission_currency') }}</label>
                                <select name="commission_currency_id" id="outgoing_commission_currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3" id="outgoing_kabul_commission_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.commission_kabul') }}</label>
                                <input type="text"  name="commission_kabul_amount" id="outgoing_commission_kabul_amount" class="form-control remittance-amount-input"
                                    inputmode="decimal" placeholder="0">
                            </div>

                            <div class="col-md-3" id="outgoing_partner_commission_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.commission_partner') }}</label>
                                <input type="text"  name="commission_partner_amount" id="outgoing_commission_partner_amount" class="form-control remittance-amount-input"
                                    inputmode="decimal" placeholder="0">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-pill px-5">
                                <i class="bi bi-check-circle me-2"></i> {{ __('ui.save') }}
                            </button>
                            <a href="{{ route('admin.remittances.outgoing') }}" class="btn btn-outline-secondary btn-pill">
                                {{ __('ui.cancel') }}
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- =========================
    INCOMING REMITTANCE
    ========================= --}}
    <div class="modal fade" id="incomingRemittanceModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-download"></i> {{ __('accounts.incoming_remittances') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <form id="incomingRemittanceForm">
                        @csrf

                        <input type="hidden" name="type" value="incoming">
                        <input type="hidden" name="to_account_id" value="1">
                        <input type="hidden" name="from_account_id" value="{{ $account->id }}" id="incoming_from_account_id">
                        <input type="hidden" name="page" value="account">

                        <div class="section-title">{{ __('ui.account') }}</div>
                        <div class="row g-3 mb-3">

                            <div class="col-md-4">
                                <label class="form-label small">{{ __('ui.selected_account') }}</label>
                                <input type="text" class="form-control bg-light" value="{{ $account->name }}" readonly>
                            </div>

                            <div class="col-md-4" id="incoming_track_account_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.track_account') }}</label>
                                <select name="track_account_id" id="incoming_track_account_id" class="select2 form-select">
                                    <option value="">{{ __('accounts.select_track_account') }}</option>
                                    @foreach ($accounts->whereIn('account_sub_category_id', [1, 2])->where('id', '!=', $account->id)->unique('id') as $a)
                                        <option value="{{ $a->id }}" data-sub-category-id="{{ $a->account_sub_category_id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- <div class="section-title">{{ __('ui.selected_saraf_balances') }}</div>
                        <div id="incoming-account-balance-summary" class="balance-wrap mb-4">
                            <span class="text-muted small">{{ __('ui.select_from_to_account_to_view_balances') }}</span>
                        </div> --}}

                        <div class="section-title">{{ __('ui.basic') }}</div>
                        <div class="row g-3 mb-3">
                            @if ($hideRemittanceFields)
                                <input type="hidden" name="remittance_mode" value="no_track">
                            @else
                                <div class="col-md-3">
                                    <label class="form-label small">{{ __('ui.mode') }}</label>
                                    <div class="mode-box">
                                        <label>
                                            <input type="radio" name="remittance_mode" value="track">
                                            {{ __('ui.track') }}
                                        </label>

                                        <label>
                                            <input type="radio" name="remittance_mode" value="no_track" checked>
                                            {{ __('ui.unpaid') }}
                                        </label>

                                        <label>
                                            <input type="radio" name="remittance_mode" value="treasure">
                                            {{ __('ui.treasure') }}
                                        </label>
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-3">
                                <label class="form-label small">{{ __('ui.date') }}</label>
                                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>

                            @if ($hideRemittanceFields)
                                <input type="hidden" name="hawala_number" value="">
                            @else
                                <div class="col-md-3">
                                    <label class="form-label small">{{ __('ui.hawala_no') }}</label>
                                    <input type="text" name="hawala_number" class="form-control" placeholder="">
                                </div>
                            @endif

                            <div class="col-md-3" id="incoming_hawala_no_wrap" style="display:none;">
                                <label class="form-label small">{{ __('accounts.track_hawala_no') }}</label>
                                <input type="text" name="track_hawala_number" class="form-control" placeholder="">
                            </div>
                        </div>

                        <div class="section-title">{{ __('accounts.amounts_currencies') }}</div>
                        <div class="row g-3 mb-3">

                            <div class="col-md-2">
                                <label class="form-label small text-primary">{{ __('accounts.execution_currency') }}</label>
                                <select name="partner_currency_id" id="incoming_currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small text-primary">{{ __('accounts.execution_amount') }}</label>
                                <input type="text"  name="partner_amount" id="incoming_partner_amount" class="form-control remittance-amount-input" inputmode="decimal"
                                    placeholder="0">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small text-danger">{{ __('accounts.saraf_currency') }}</label>
                                <select name="currency_id" id="incoming_partner_currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>

                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small text-danger">{{ __('accounts.saraf_receipt') }}</label>
                                <input type="text"  name="amount" id="incoming_amount" class="form-control remittance-amount-input" inputmode="decimal"
                                    placeholder="0">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small">{{ __('accounts.actual_currency') }}</label>
                                <select name="actual_currency_id" id="incoming_actual_currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small">{{ __('accounts.actual_amount') }}</label>
                                <input type="text"  name="actual_amount" id="incoming_actual_amount" class="form-control remittance-amount-input" inputmode="decimal"
                                    placeholder="0">
                            </div>
                        </div>

                        <div class="section-title">{{ __('ui.parties') }}</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small">{{ __('ui.sender') }}</label>
                                <input type="text" name="sender" class="form-control" placeholder="">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">{{ __('ui.receiver') }}</label>
                                <input type="text" name="receiver" class="form-control" placeholder="">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">{{ __('ui.description') }}</label>
                                <input type="text" name="description" class="form-control" placeholder="">
                            </div>
                        </div>

                        <div class="section-title">{{ __('ui.commission') }}</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label small">{{ __('ui.commission_mode') }}</label>
                                <select name="commission_mode" id="incoming_commission_mode" class="commission-mode form-select">
                                    <option value="none">{{ __('ui.no_commission') }}</option>
                                    <option value="company">{{ __('ui.company_commission') }}</option>
                                    <option value="split">{{ __('ui.split_commission') }}</option>
                                </select>
                            </div>

                            <div class="col-md-3" id="incoming_commission_currency_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.commission_currency') }}</label>
                                <select name="commission_currency_id" id="incoming_commission_currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3" id="incoming_kabul_commission_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.commission_kabul') }}</label>
                                <input type="text"  name="commission_kabul_amount" id="incoming_commission_kabul_amount" class="form-control remittance-amount-input"
                                    inputmode="decimal" placeholder="0">
                            </div>

                            <div class="col-md-3" id="incoming_partner_commission_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.commission_partner') }}</label>
                                <input type="text"  name="commission_partner_amount" id="incoming_commission_partner_amount" class="form-control remittance-amount-input"
                                    inputmode="decimal" placeholder="0">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-pill px-5">
                                <i class="bi bi-check-circle me-2"></i> {{ __('ui.save') }}
                            </button>
                            <a href="{{ route('admin.remittances.outgoing') }}" class="btn btn-outline-secondary btn-pill">
                                {{ __('ui.cancel') }}
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="currencyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-top">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title pb-3">{{ __('ui.select_currency') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- Currency -->
                    <div class="mb-3">
                        <label class="form-label">{{ __('ui.currency') }}</label>
                        <select id="currencySelect" class="form-select">
                            <option value="">{{ __('ui.all_currencies') }}</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}">{{ $currency->code }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div class="mb-3">
                        <label class="form-label">{{ __('ui.blade_from_date') }}</label>
                        <input type="date" id="startDate" class="form-control">
                    </div>

                    <!-- Date To -->
                    <div class="mb-3">
                        <label class="form-label">{{ __('ui.blade_to_date') }}</label>
                        <input type="date" id="endDate" class="form-control">
                    </div>

                </div>

                <div class="modal-footer pt-4">
                    <button class="btn btn-primary" id="openReport">
                        {{ __('accounts.generate_report') }}
                    </button>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('js')
    <!-- Select2 -->
    <script src="{{ asset('assets/offline/33859893654b9de2.js') }}"></script>

    <!-- DataTables -->
    <script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>
    <script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>
    <script src="{{ asset('assets/offline/ac82083f08b38e5c.js') }}"></script>
    <script src="{{ asset('assets/offline/00b712c3c875b8cd.js') }}"></script>

    <script>
    const accountsSwal = Swal.mixin({confirmButtonText: @json(__('accounts.ok')), cancelButtonText: @json(__('ui.cancel'))});
        let outgoingManual = false;
        let incomingManual = false;
        let outgoingTrackManual = false;
        let incomingTrackManual = false;
        let outgoingEditMode = false;
        let incomingEditMode = false;
        let actualTable = null;
        let latestAccountSummary = null;
        const ACCOUNT_SUB_CATEGORY_ID = {{ (int) $account->account_sub_category_id }};
        const HIDE_REMITTANCE_FIELDS = ACCOUNT_SUB_CATEGORY_ID === 1;

        window.fetchNextHawala = function(accountId, column, type, callback) {
            if (!accountId || !type) return;

            console.log('Fetching hawala:', {
                account_id: accountId,
                column: column,
                type: type
            });

            $.get("{{ url('/admin/modal-remittances/next-hawala') }}", {
                account_id: accountId,
                column: column,
                type: type
            }, function(res) {
                console.log('Next hawala response:', res);
                callback(res.number);
            });
        };

        window.fetchAccountBalances = function(accountId, cb) {
            if (!accountId) return cb(null);

            $.get("{{ route('admin.modal-remittances.account-balances') }}", {
                account_id: accountId
            }, function(res) {
                cb(res.balances || {});
            });
        };

        window.formatCurrency = function(amount) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2
            }).format(Number(amount || 0));
        };

        window.parseCleanNumber = function(value) {
            return parseFloat((value || '0').toString().replace(/,/g, '')) || 0;
        };

        window.syncModalCurrencies = function(prefix) {
            // Saved currency pairs are independent, including during mode changes.
            if ((prefix === 'incoming' && incomingEditMode) ||
                (prefix === 'outgoing' && outgoingEditMode)) return;
            let currencyId = $('#' + prefix + '_currency_id').val();

            if (!currencyId) return;

            $('#' + prefix + '_partner_currency_id')
                .val(currencyId)
                .trigger('change.select2');

            $('#' + prefix + '_actual_currency_id')
                .val(currencyId)
                .trigger('change.select2');
        };

        window.syncModalAmounts = function(prefix) {
            if ((prefix === 'incoming' && incomingEditMode) ||
                (prefix === 'outgoing' && outgoingEditMode)) return;
            let amount = $('#' + prefix + '_amount').val();

            if (!$('#' + prefix + '_partner_amount').data('manual')) {
                $('#' + prefix + '_partner_amount').val(amount);
            }

            if (!$('#' + prefix + '_actual_amount').data('manual')) {
                $('#' + prefix + '_actual_amount').val(amount);
            }
        };

        window.toggleCommissionFields = function(prefix) {
            let mode = $('#' + prefix + '_commission_mode').val();

            if (mode === 'none') {
                $('#' + prefix + '_commission_currency_wrap').hide();
                $('#' + prefix + '_kabul_commission_wrap').hide();
                $('#' + prefix + '_partner_commission_wrap').hide();
                return;
            }

            $('#' + prefix + '_commission_currency_wrap').show();
            $('#' + prefix + '_kabul_commission_wrap').show();
            $('#' + prefix + '_partner_commission_wrap').toggle(mode === 'split');
        };

        window.renderBalanceSummary = function(modalId, summaryId) {
            let modal = $(modalId);
            let htmlContainer = $(summaryId);

            let fromId = modal.find('[name="from_account_id"]').val();
            let toId = modal.find('[name="to_account_id"]').val();
            let mode = modal.find('input[name="remittance_mode"]:checked').val();
            let trackId = modal.find('[name="track_account_id"]').val();

            htmlContainer.html('<span class="text-muted small">{{ __('ui.blade_loading') }}</span>');

            let accountsToCheck = [];

            if (fromId) {
                accountsToCheck.push({
                    id: fromId,
                    label: @json(__('accounts.from_account'))
                });
            }

            if (toId) {
                accountsToCheck.push({
                    id: toId,
                    label: @json(__('accounts.to_account'))
                });
            }

            if (mode === 'track' && trackId) {
                accountsToCheck.push({
                    id: trackId,
                    label: @json(__('ui.track'))
                });
            }

            if (!accountsToCheck.length) {
                htmlContainer.html('<span class="text-muted small">{{ __('ui.blade_select_accounts') }}</span>');
                return;
            }

            let html = '';
            let pending = accountsToCheck.length;

            accountsToCheck.forEach(item => {
                fetchAccountBalances(item.id, function(balances) {
                    html += `<div class="balance-group">
                        <div class="balance-group-title">
                            <span>${item.label}</span>
                            <span class="text-muted mono">#${item.id}</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">`;

                    if (!balances || Object.keys(balances).length === 0) {
                        html += `<div class="text-muted small">{{ __('ui.blade_no_balance_data') }}</div>`;
                    } else {
                        Object.entries(balances).forEach(([cur, b]) => {
                            let cls = b.balance < 0 ? 'bal-neg' : 'bal-pos';

                            html += `<div class="balance-chip">
                                <div class="cur">${cur}</div>
                                <div class="balance-amount mono ${cls}">
                                    ${formatCurrency(b.balance)}
                                </div>
                            </div>`;
                        });
                    }

                    html += `</div></div>`;

                    pending--;

                    if (!pending) {
                        htmlContainer.html(html);
                    }
                });
            });
        };

        window.autoOutgoingHawala = function() {
            if (outgoingEditMode) return;

            if (HIDE_REMITTANCE_FIELDS) {
                $('#outgoingRemittanceModal input[name="hawala_number"]').val('');
                return;
            }

            let id = $('#outgoing_to_account_id').val();

            if (!id || outgoingManual) return;

            fetchNextHawala(id, 'hawala_number', 'outgoing', function(num) {
                $('#outgoingRemittanceModal input[name="hawala_number"]').val(num);
            });
        };

        window.autoIncomingHawala = function() {
            if (incomingEditMode) return;

            if (HIDE_REMITTANCE_FIELDS) {
                $('#incomingRemittanceModal input[name="hawala_number"]').val('');
                return;
            }

            let id = $('#incoming_from_account_id').val();

            if (!id || incomingManual) return;

            fetchNextHawala(id, 'hawala_number', 'incoming', function(num) {
                $('#incomingRemittanceModal input[name="hawala_number"]').val(num);
            });
        };

        window.autoOutgoingTrackHawala = function() {
            if (outgoingEditMode) return;

            let modal = $('#outgoingRemittanceModal');

            let mode = modal.find('input[name="remittance_mode"]:checked').val() ||
                modal.find('input[type="hidden"][name="remittance_mode"]').val();

            let trackId = modal.find('#outgoing_track_account_id').val();
            let trackSubCategoryId = Number(modal.find('#outgoing_track_account_id option:selected').data('sub-category-id'));

            if (mode !== 'track' || !trackId || HIDE_REMITTANCE_FIELDS || trackSubCategoryId === 1 || outgoingTrackManual) return;

            fetchNextHawala(trackId, 'track_hawala_number', 'outgoing', function(num) {
                let currentMode = modal.find('input[name="remittance_mode"]:checked').val() ||
                    modal.find('input[type="hidden"][name="remittance_mode"]').val();
                if (outgoingEditMode || outgoingTrackManual || currentMode !== 'track' ||
                    String(modal.find('#outgoing_track_account_id').val()) !== String(trackId)) return;

                modal.find('input[name="track_hawala_number"]').val(num);
            });
        };

        window.autoIncomingTrackHawala = function() {
            if (incomingEditMode) return;

            let modal = $('#incomingRemittanceModal');

            let mode = modal.find('input[name="remittance_mode"]:checked').val() ||
                modal.find('input[type="hidden"][name="remittance_mode"]').val();

            let trackId = modal.find('#incoming_track_account_id').val();
            let trackSubCategoryId = Number(modal.find('#incoming_track_account_id option:selected').data('sub-category-id'));

            if (mode !== 'track' || !trackId || HIDE_REMITTANCE_FIELDS || trackSubCategoryId === 1 || incomingTrackManual) return;

            fetchNextHawala(trackId, 'track_hawala_number', 'incoming', function(num) {
                modal.find('input[name="track_hawala_number"]').val(num);
            });
        };

        function initSelect2Inside(modalSelector) {
            let modal = $(modalSelector);

            modal.find('.select2').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }

                $(this).select2({
                language: @include('admin.accounts.partials.select-language'),
                    dropdownParent: modal,
                    width: '100%',
                    dir: 'rtl'
                });
            });
        }

        function handleOutgoingModal() {
            let modal = $('#outgoingRemittanceModal');
            let mode = modal.find('input[name="remittance_mode"]:checked').val() ||
                modal.find('input[type="hidden"][name="remittance_mode"]').val();
            let trackSubCategoryId = Number(modal.find('#outgoing_track_account_id option:selected').data('sub-category-id'));
            let showTrackHawalaNumber = mode === 'track' && !HIDE_REMITTANCE_FIELDS && trackSubCategoryId !== 1;

            modal.find('#outgoing_track_account_wrap').toggle(mode === 'track');
            modal.find('#wrap_to_account_id').toggle(mode === 'track');
            modal.find('#outgoing_hawala_no_wrap').toggle(showTrackHawalaNumber);

            if (!showTrackHawalaNumber) {
                modal.find('input[name="track_hawala_number"]').val('');
                outgoingTrackManual = false;
            }

            if (mode !== 'track' && !outgoingEditMode) {
                modal.find('#outgoing_track_account_id').val('').trigger('change.select2');
                modal.find('input[name="track_hawala_number"]').val('');
            }

            toggleCommissionFields('outgoing');
            syncModalCurrencies('outgoing');
            renderBalanceSummary('#outgoingRemittanceModal', '#outgoing-account-balance-summary');
            autoOutgoingHawala();
            autoOutgoingTrackHawala();
        }

        function handleIncomingModal() {
            let modal = $('#incomingRemittanceModal');

            let mode = modal.find('input[name="remittance_mode"]:checked').val() ||
                modal.find('input[type="hidden"][name="remittance_mode"]').val();
            let trackSubCategoryId = Number(modal.find('#incoming_track_account_id option:selected').data('sub-category-id'));
            let showTrackHawalaNumber = mode === 'track' && !HIDE_REMITTANCE_FIELDS && trackSubCategoryId !== 1;

            modal.find('#incoming_track_account_wrap').toggle(mode === 'track');
            modal.find('#incoming_hawala_no_wrap').toggle(showTrackHawalaNumber);

            if (!showTrackHawalaNumber) {
                modal.find('input[name="track_hawala_number"]').val('');
                incomingTrackManual = false;
            }

            if (mode !== 'track' && !incomingEditMode) {
                modal.find('#incoming_track_account_id').val('').trigger('change.select2');
                modal.find('input[name="track_hawala_number"]').val('');
            }

            toggleCommissionFields('incoming');
            syncModalCurrencies('incoming');
            renderBalanceSummary('#incomingRemittanceModal', '#incoming-account-balance-summary');

            autoIncomingHawala();
            autoIncomingTrackHawala();

            console.log('incoming handler executed');
        }

        function filters() {
            let p = {};

            if ($('#filter-currency').val()) {
                p.currency_id = $('#filter-currency').val();
            }

            if ($('#filter-type').val()) {
                p.transaction_type = $('#filter-type').val();
            }

            if ($('#filter-color').val()) p.color = $('#filter-color').val();
            if ($('#filter-is-ok').val() !== '') p.is_ok = $('#filter-is-ok').val();

            let drp = $('#filter-date-range').data('daterangepicker');

            if ($('#filter-date-range').val() && drp) {
                p.date_from = drp.startDate.format('YYYY-MM-DD');
                p.date_to = drp.endDate.format('YYYY-MM-DD');
            }

            return p;
        }

        function renderSummary(res) {
            latestAccountSummary = res;
            let html = '';

            Object.entries(res.per_currency || {}).forEach(([currency, v]) => {
                let balanceColor = v.balance < 0 ? 'text-danger' : 'text-primary';

                html += `
                    <div class="summary-item" style="min-width:240px;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold">${currency}</span>
                        </div>

                        <div class="d-flex justify-content-between small text-success">
                            <span>{{ __('ui.total_credit') }}</span>
                            <span>${Number(v.credit).toLocaleString()}</span>
                        </div>

                        <div class="d-flex justify-content-between small text-danger">
                            <span>{{ __('ui.total_debit') }}</span>
                            <span>${Number(v.debit).toLocaleString()}</span>
                        </div>

                        <div class="d-flex justify-content-between fw-bold ${balanceColor}">
                            <span>{{ __('ui.balance') }}</span>
                            <span>${Number(v.balance).toLocaleString()}</span>
                        </div>
                    </div>`;
            });

            $('#currency-breakdown').html(html);
        }

        function reloadSummary() {
            $.get('{{ route('admin.accounts.summary.filter', $account->id) }}', filters(), renderSummary);
        }

        function reloadAll() {
            $('#current-table').DataTable().ajax.reload();
            reloadSummary();

            if ($.fn.DataTable.isDataTable('#actual-table') && actualTable) {
                actualTable.ajax.reload();
            }
        }


        $(function() {
            $('.select2').select2({
                language: @include('admin.accounts.partials.select-language'),
                width: '100%'
            });

            $('#filter-date-range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: @json(__('accounts.clear')),
                    applyLabel: @json(__('accounts.apply')),
                    fromLabel: @json(__('accounts.from')),
                    toLabel: @json(__('accounts.to')),
                    customRangeLabel: @json(__('accounts.custom_range')),
                    weekLabel: @json(__('accounts.week')),
                    daysOfWeek: @json(__('accounts.days')),
                    monthNames: @json(__('accounts.months'))
                }
            }).on('apply.daterangepicker', function(ev, p) {
                $(this).val(p.startDate.format('YYYY-MM-DD') + ' - ' + p.endDate.format('YYYY-MM-DD'));
                reloadAll();
            }).on('cancel.daterangepicker', function() {
                $(this).val('');
                reloadAll();
            });

            $('#filter-currency,#filter-type,#filter-color,#filter-is-ok').on('change', reloadAll);

            $('#filter-reset').on('click', function() {
                $('#filter-currency').val('');
                $('#filter-type').val('');
                $('#filter-color').val('');
                $('#filter-is-ok').val('');
                $('#filter-date-range').val('');
                reloadAll();
            });

            const table = $('#current-table').DataTable({
                language: @include('admin.accounts.partials.table-language'),
                serverSide: true,
                processing: true,
                ordering: false,
                ajax: {
                    url: '{{ route('admin.accounts.transactionData', [$account->id, 'current']) }}',
                    data: d => Object.assign(d, filters())
                },
                columns: [{
                        data: 'select', orderable: false, searchable: false, className: 'text-center'
                    }, {
                        data: 'DT_RowIndex',
                        className: 'text-center'
                    },
                    {
                        data: 'created_at',
                        className: 'date-cell'
                    },
                    {
                        data: 'note',
                        className: 'note-cell'
                    },
                    {
                        data: 'transaction_type',
                        className: 'text-center'
                    },
                    {
                        data: 'payment_display',
                        className: 'amount-cell'
                    },
                    {
                        data: 'amount_display',
                        className: 'amount-cell'
                    },
                    {
                        data: 'running_balance',
                        orderable: false,
                        searchable: false,
                        className: 'balance-cell'
                    },
                    {data:'color_display',orderable:false,searchable:false,className:'text-center'},
                    {data:'ok_display',orderable:false,searchable:false,className:'text-center'},
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'action-cell'
                    }
                ],
                rowCallback:function(row,data){if(data.color){row.style.setProperty('--transaction-row-color',data.color);row.classList.add('transaction-row-colored')}else{row.classList.remove('transaction-row-colored');row.style.removeProperty('--transaction-row-color')}},
                initComplete:function(){const bar=$('#account-transaction-bulk-bar');$('#current-table_wrapper > .row').first().after(bar)},
                drawCallback:function(){$('#select-all-transactions').prop('checked',false);updateTransactionSelectionCount()}
            });

            $(document).on('click', '.btn-remittance-direct-print', function() {
                const frame = document.createElement('iframe');
                frame.setAttribute('aria-hidden', 'true');
                Object.assign(frame.style, {
                    position: 'fixed',
                    width: '1px',
                    height: '1px',
                    insetInlineEnd: '0',
                    bottom: '0',
                    border: '0',
                    opacity: '0',
                    pointerEvents: 'none'
                });

                frame.addEventListener('load', function() {
                    try {
                        frame.contentWindow.addEventListener('afterprint', () => frame.remove(), { once: true });
                    } catch (error) {
                        // The fallback timer below removes the frame.
                    }
                });

                frame.src = $(this).data('print-url');
                document.body.appendChild(frame);
                window.setTimeout(() => frame.remove(), 120000);
            });

            function selectedTransactionIds(){return $('.transaction-select:checked').map(function(){return Number(this.value)}).get()}
            function updateTransactionSelectionCount() {
                const bar = $('#account-transaction-bulk-bar');
                const count = selectedTransactionIds().length;
                const total = $('#current-table .transaction-select').length;
                bar.find('.selected-transaction-count').text(count);
                bar.toggleClass('has-selection', count > 0);
                bar.find('.clear-bulk-color, .bulk-ok, .bulk-reset').prop('disabled', count === 0);
                bar.find('.apply-bulk-color').prop('disabled', count === 0 || !bar.find('.bulk-color').val());
                $('#select-all-transactions').prop('checked', total > 0 && count === total)
                    .prop('indeterminate', count > 0 && count < total);
            }
            $('#account-transaction-bulk-bar .bulk-color').on('change', updateTransactionSelectionCount);
            $('#account-transaction-bulk-bar .bulk-reset').on('click', function() {
                $('#current-table .transaction-select').prop('checked', false);
                updateTransactionSelectionCount();
            });
            function updateTransactionMeta(ids,payload){if(!ids.length){accountsSwal.fire(@json(__('accounts.select_transactions')),@json(__('accounts.select_one_transaction')),'info');return $.Deferred().reject().promise()}return $.ajax({url:'{{ route('admin.transactions.bulk-meta') }}',type:'PATCH',data:Object.assign({_token:'{{ csrf_token() }}',ids:ids},payload)}).done(function(res){table.ajax.reload(null,false);accountsSwal.fire({icon:'success',title:res.message,timer:1100,showConfirmButton:false})}).fail(function(xhr){accountsSwal.fire(@json(__('accounts.update_failed')),xhr.responseJSON?.message||@json(__('accounts.transactions_update_failed')),'error')})}
            function confirmTransactionUpdate(title,text,callback){return accountsSwal.fire({title:title,text:text,icon:'question',showCancelButton:true,confirmButtonText:@json(__('accounts.yes_continue')),cancelButtonText:@json(__('ui.cancel'))}).then(function(result){if(result.isConfirmed)callback();return result})}
            $(document).on('change','#select-all-transactions',function(){$('.transaction-select').prop('checked',this.checked);updateTransactionSelectionCount()});
            $(document).on('change','.transaction-select',updateTransactionSelectionCount);
            $(document).on('change','.transaction-ok-toggle',function(){const checkbox=this,isOk=checkbox.checked,id=Number($(checkbox).data('id'));confirmTransactionUpdate(isOk?@json(__('accounts.mark_ok')):@json(__('accounts.mark_not_ok')),isOk?@json(__('accounts.checked_correct')):@json(__('accounts.needs_review')),function(){updateTransactionMeta([id],{is_ok:isOk?1:0})}).then(function(result){if(!result.isConfirmed)checkbox.checked=!isOk})});
            $('.apply-bulk-color').on('click',function(){const color=$('.bulk-color').val(),ids=selectedTransactionIds();if(!color){accountsSwal.fire(@json(__('accounts.choose_color')),@json(__('accounts.select_palette')),'info');return}if(!ids.length){accountsSwal.fire(@json(__('accounts.select_transactions')),@json(__('accounts.select_one_transaction')),'info');return}confirmTransactionUpdate(@json(__('accounts.apply_color')),@json(__('accounts.apply_color_count')).replace(':count', ids.length),function(){updateTransactionMeta(ids,{color:color})})});
            $('.clear-bulk-color').on('click',function(){const ids=selectedTransactionIds();if(!ids.length){accountsSwal.fire(@json(__('accounts.select_transactions')),@json(__('accounts.select_one_transaction')),'info');return}confirmTransactionUpdate(@json(__('accounts.clear_colors')),@json(__('accounts.clear_colors_count')).replace(':count', ids.length),function(){updateTransactionMeta(ids,{color:''})})});
            $('.bulk-ok').on('click',function(){const isOk=Number($(this).data('ok')),ids=selectedTransactionIds();if(!ids.length){accountsSwal.fire(@json(__('accounts.select_transactions')),@json(__('accounts.select_one_transaction')),'info');return}confirmTransactionUpdate(isOk?@json(__('accounts.mark_selected_ok')):@json(__('accounts.mark_selected_not_ok')),(isOk ? @json(__('accounts.confirm_count')) : @json(__('accounts.flag_count'))).replace(':count', ids.length),function(){updateTransactionMeta(ids,{is_ok:isOk})})});

            reloadSummary();

            $('#transactionModal').on('shown.bs.modal', function() {
                let modal = $(this);

                modal.find('.select2').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }

                    $(this).select2({
                language: @include('admin.accounts.partials.select-language'),
                        dropdownParent: modal,
                        width: '100%',
                        dir: 'rtl'
                    });
                });

                let mode = modal.find('input[name="transaction_mode"]:checked').val();
                modal.find('.track-account-wrap').toggle(mode === 'track');
            });

            $(document).on('change', '#transactionModal input[name="transaction_mode"]', function() {
                let modal = $('#transactionModal');
                let mode = modal.find('input[name="transaction_mode"]:checked').val();
                modal.find('.track-account-wrap').toggle(mode === 'track');
            });

            $(document).on('shown.bs.modal', '#exchangeModal', function() {
                let modal = $(this);

                if (modal.data('exchange-js-ready')) {
                    if (typeof modal.data('load-exchange-balances') === 'function') {
                        modal.data('load-exchange-balances')();
                    }

                    if (typeof modal.data('exchange-refresh') === 'function') {
                        setTimeout(function() {
                            modal.data('exchange-refresh')();
                        }, 300);
                    }

                    return;
                }

                modal.data('exchange-js-ready', true);

                let BASE_RATE = 0;
                let rateTouchedByUser = false;

                const currencies = @json($currencies->map(fn($c) => ['id' => $c->id, 'code' => $c->code])->values());

                function getCurrencyCode(id) {
                    const cur = currencies.find(x => String(x.id) === String(id));
                    return cur ? cur.code : '';
                }

                function parseNum(value) {
                    if (typeof parseCleanNumber === 'function') {
                        return parseCleanNumber(value);
                    }

                    return parseFloat((value || '0').toString().replace(/,/g, '')) || 0;
                }

                function cleanNumberValue(value) {
                    if (typeof convertToEnglishNumbers === 'function') {
                        value = convertToEnglishNumbers(value);
                    }

                    if (typeof cleanEditAmount === 'function') {
                        value = cleanEditAmount(value);
                    } else {
                        value = (value || '').toString().replace(/,/g, '');
                    }

                    return value;
                }

                function initExchangeSelect2() {
                    modal.find('.ex-select2').each(function() {
                        if ($(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2('destroy');
                        }

                        $(this).select2({
                language: @include('admin.accounts.partials.select-language'),
                            dropdownParent: modal,
                            width: '100%',
                            dir: 'rtl'
                        });
                    });
                }

                function loadExchangeBalances() {
                    $.get("{{ route('admin.exchanges.balances') }}", {
                        account_id: "{{ $account->id }}"
                    }).done(function(res) {
                        let html = '';

                        Object.entries(res.balances || {}).forEach(([code, val]) => {
                            let bal = Number(val.balance || 0);
                            let cls = bal < 0 ? 'text-danger' : 'text-dark';

                            html += `
                    <span class="badge bg-light ${cls} me-1 mb-1">
                        ${code}: ${bal.toLocaleString()}
                    </span>
                `;
                        });

                        modal.find('.exchange-balance-box').html(
                            html || '<span class="text-muted small">{{ __('ui.blade_no_balances_found') }}</span>'
                        );
                    }).fail(function() {
                        modal.find('.exchange-balance-box').html(
                            '<span class="text-danger small">{{ __('ui.blade_failed_to_load_balances') }}</span>'
                        );
                    });
                }

                modal.data('load-exchange-balances', loadExchangeBalances);

                function enforceCurrencyExclusion() {
                    let from = modal.find('.ex-currency-from').val();
                    let to = modal.find('.ex-currency-to').val();

                    modal.find('.ex-currency-from option, .ex-currency-to option').prop('disabled', false);

                    if (from) {
                        modal.find('.ex-currency-to option[value="' + from + '"]').prop('disabled', true);
                    }

                    if (to) {
                        modal.find('.ex-currency-from option[value="' + to + '"]').prop('disabled', true);
                    }

                    initExchangeSelect2();
                }

                function autoOperationUniversal() {
                    let from = parseInt(modal.find('.ex-currency-from').val() || 0);
                    let to = parseInt(modal.find('.ex-currency-to').val() || 0);

                    if (!from || !to) return;

                    /*
                    |--------------------------------------------------------------------------
                    | Universal pair rate rule
                    |--------------------------------------------------------------------------
                    | rate means:
                    | 1 FROM = X TO
                    |
                    | So default should be multiply:
                    | from_amount * rate = to_amount
                    |--------------------------------------------------------------------------
                    */
                    modal.find('.ex-operation').val('multiply').trigger('change.select2');
                }

                function updateRateHint() {
                    let rate = parseNum(modal.find('.ex-rate').val());
                    let from = parseInt(modal.find('.ex-currency-from').val() || 0);
                    let to = parseInt(modal.find('.ex-currency-to').val() || 0);

                    if (!rate || !from || !to) {
                        modal.find('.ex-rate-hint').text('—');
                        return;
                    }

                    let fromCode = getCurrencyCode(from);
                    let toCode = getCurrencyCode(to);

                    modal.find('.ex-rate-hint').text(`1 ${fromCode} = ${rate} ${toCode}`);
                }

                function calculateFromSource(source) {
                    let operation = modal.find('.ex-operation').val();
                    let rate = parseNum(modal.find('.ex-rate').val());

                    if (!rate || rate <= 0) return;

                    let fromAmount = parseNum(modal.find('.ex-from-amount').val());
                    let toAmount = parseNum(modal.find('.ex-to-amount').val());

                    if (source === 'to_amount') {
                        if (!toAmount || toAmount <= 0) return;

                        let result = operation === 'multiply' ?
                            toAmount / rate :
                            toAmount * rate;

                        modal.find('.ex-from-amount').val(result.toFixed(2));
                    } else {
                        if (!fromAmount || fromAmount <= 0) return;

                        let result = operation === 'multiply' ?
                            fromAmount * rate :
                            fromAmount / rate;

                        modal.find('.ex-to-amount').val(result.toFixed(2));
                    }
                }

                function calculateLiveProfit() {
                    let rate = parseNum(modal.find('.ex-rate').val());
                    let baseRate = parseFloat(BASE_RATE || 0);

                    let from = parseInt(modal.find('.ex-currency-from').val() || 0);
                    let to = parseInt(modal.find('.ex-currency-to').val() || 0);

                    let fromVal = parseNum(modal.find('.ex-from-amount').val());
                    let toVal = parseNum(modal.find('.ex-to-amount').val());

                    if (!rate || !baseRate || !from || !to || !fromVal || !toVal) {
                        modal.find('.ex-base-rate').text('—');
                        modal.find('.ex-spread').text('—');
                        modal.find('.ex-profit').text('—');
                        return;
                    }

                    let fromCode = getCurrencyCode(from);
                    let toCode = getCurrencyCode(to);

                    /*
                    |--------------------------------------------------------------------------
                    | Universal profit/loss logic
                    |--------------------------------------------------------------------------
                    | from_currency_id = currency office SOLD
                    | to_currency_id   = currency office PURCHASED / RECEIVED
                    |
                    | Base rate:
                    | 1 FROM = BASE_RATE TO
                    |
                    | Profit/Loss:
                    | actual TO received - market TO expected
                    |
                    | Profit currency:
                    | TO currency
                    |--------------------------------------------------------------------------
                    */
                    let marketToAmount = fromVal * baseRate;
                    let actualToAmount = toVal;

                    let profitAmount = marketToAmount - actualToAmount;
                    let spread = baseRate - rate;

                    modal.find('.ex-base-rate').text(`1 ${fromCode} = ${baseRate.toFixed(8)} ${toCode}`);
                    modal.find('.ex-spread').text(spread.toFixed(8));

                    let formattedProfit = new Intl.NumberFormat().format(
                        parseFloat(profitAmount.toFixed(2))
                    );

                    modal.find('.ex-profit')
                        .text(`${formattedProfit} ${toCode}`)
                        .removeClass('text-success text-danger')
                        .addClass(profitAmount < 0 ? 'text-danger' : 'text-success');
                }

                function fetchMarketRate(keepCurrentRate = false) {
                    let from = modal.find('.ex-currency-from').val();
                    let to = modal.find('.ex-currency-to').val();

                    if (!from || !to) return;

                    if (rateTouchedByUser && !keepCurrentRate) return;

                    $.get("{{ route('admin.exchanges.rate') }}", {
                        from_currency_id: from,
                        to_currency_id: to
                    }).done(function(res) {
                        if (res && res.rate) {
                            BASE_RATE = parseFloat(res.base_rate || res.rate || 0);

                            if (!keepCurrentRate) {
                                modal.find('.ex-rate').val(res.rate);
                                autoOperationUniversal();
                                calculateFromSource('from_amount');
                            }

                            updateRateHint();
                            calculateLiveProfit();

                            modal.find('.ex-rate-hint').text(@json(__('accounts.auto_market_rate')));
                        } else {
                            BASE_RATE = 0;

                            modal.find('.ex-rate-hint').text(@json(__('accounts.no_rate')));
                            modal.find('.ex-base-rate').text('—');
                            modal.find('.ex-spread').text('—');
                            modal.find('.ex-profit').text('—');
                        }
                    }).fail(function() {
                        modal.find('.ex-rate-hint').text(@json(__('accounts.rate_failed')));
                    });
                }

                function toggleExchangeTrackAccount() {
                    let mode = modal.find('input[name="exchange_mode"]:checked').val();

                    modal.find('.track-account-wrap').toggle(mode === 'track');

                    if (mode !== 'track') {
                        modal.find('select[name="track_account_id"]').val('').trigger('change.select2');
                    }
                }

                function refreshExchangePreview() {
                    enforceCurrencyExclusion();
                    updateRateHint();
                    fetchMarketRate(true);
                    calculateLiveProfit();
                }

                modal.data('exchange-refresh', refreshExchangePreview);

                initExchangeSelect2();
                loadExchangeBalances();
                enforceCurrencyExclusion();
                updateRateHint();
                toggleExchangeTrackAccount();

                modal.off('change.exchangeCurrencies').on('change.exchangeCurrencies', '.ex-currency-from, .ex-currency-to', function() {
                    rateTouchedByUser = false;
                    enforceCurrencyExclusion();
                    autoOperationUniversal();
                    fetchMarketRate(false);
                });

                modal.off('change.exchangeOperation').on('change.exchangeOperation', '.ex-operation', function() {
                    calculateFromSource('from_amount');
                    calculateLiveProfit();
                });

                modal.off('input.exchangeFrom').on('input.exchangeFrom', '.ex-from-amount', function() {
                    calculateFromSource('from_amount');
                    calculateLiveProfit();
                });

                modal.off('input.exchangeTo').on('input.exchangeTo', '.ex-to-amount', function() {
                    calculateFromSource('to_amount');
                    calculateLiveProfit();
                });

                modal.off('input.exchangeRate').on('input.exchangeRate', '.ex-rate', function() {
                    rateTouchedByUser = true;
                    updateRateHint();
                    calculateFromSource('from_amount');
                    calculateLiveProfit();
                });

                modal.off('change.exchangeDate').on('change.exchangeDate', 'input[name="date"]', function() {
                    rateTouchedByUser = false;
                    fetchMarketRate(false);
                });

                modal.off('change.exchangeMode').on('change.exchangeMode', 'input[name="exchange_mode"]', function() {
                    toggleExchangeTrackAccount();
                });
            });

            $('#actualReportModal').on('shown.bs.modal', function() {
                if ($.fn.DataTable.isDataTable('#actual-table')) {
                    actualTable.ajax.reload();
                    return;
                }

                actualTable = $('#actual-table').DataTable({
                language: @include('admin.accounts.partials.table-language'),
                    serverSide: true,
                    processing: true,
                    ordering: false,
                    ajax: {
                        url: '{{ route('admin.accounts.transactionActualData', [$account->id, 'actual']) }}',
                        data: d => Object.assign(d, filters())
                    },
                    columns: [{
                            data: 'DT_RowIndex'
                        },
                        {
                            data: 'payment_amount'
                        },
                        {
                            data: 'payment_currency'
                        },
                        {
                            data: 'actual_amount'
                        },
                        {
                            data: 'actual_currency'
                        },
                        {
                            data: 'transaction_type'
                        },
                        {
                            data: 'note'
                        },
                        {
                            data: 'created_at'
                        }
                    ],
                    drawCallback: function() {
                        let api = this.api();
                        let data = api.rows({
                            page: 'current'
                        }).data();

                        let actual = {};
                        let payment = {};

                        data.each(function(row) {
                            let type = row.transaction_type_key;

                            let aCur = row.actual_currency || 'N/A';
                            let aAmt = parseFloat(row.actual_amount_raw || 0) || 0;

                            if (!actual[aCur]) {
                                actual[aCur] = {
                                    credit: 0,
                                    debit: 0
                                };
                            }

                            if (type === 'credit') {
                                actual[aCur].credit += aAmt;
                            } else {
                                actual[aCur].debit += aAmt;
                            }

                            let pCur = row.payment_currency || 'N/A';
                            let pAmt = parseFloat(row.payment_amount_raw || 0) || 0;

                            if (!payment[pCur]) {
                                payment[pCur] = {
                                    credit: 0,
                                    debit: 0
                                };
                            }

                            if (type === 'credit') {
                                payment[pCur].credit += pAmt;
                            } else {
                                payment[pCur].debit += pAmt;
                            }
                        });

                        let actualHtml = '';

                        Object.entries(actual).forEach(([cur, val]) => {
                            let balance = val.credit - val.debit;
                            let isDebit = balance < 0;
                            let amountClass = isDebit ? 'text-danger' : 'text-success';
                            let sign = isDebit ? '-' : '+';

                            actualHtml += `
                                <div style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:6px;
                                    padding:4px 8px;
                                    margin:3px;
                                    border-radius:6px;
                                    background:#f8fafc;
                                    border:1px solid #e2e8f0;
                                    font-size:14px;
                                    width:auto;
                                ">
                                    <span class="text-muted">${cur}</span>
                                    <span class="fw-semibold ${amountClass}">
                                        ${sign}${Math.abs(balance).toLocaleString()}
                                    </span>
                                </div>`;
                        });

                        if (!actualHtml) actualHtml = '<span class="text-muted">{{ __('ui.blade_no_data') }}</span>';

                        let paymentHtml = '';

                        Object.entries(payment).forEach(([cur, val]) => {
                            let balance = val.credit - val.debit;
                            let isDebit = balance < 0;
                            let amountClass = isDebit ? 'text-danger' : 'text-success';
                            let sign = isDebit ? '-' : '+';

                            paymentHtml += `
                                <div style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:6px;
                                    padding:4px 8px;
                                    margin:3px;
                                    border-radius:6px;
                                    background:#f0fdf4;
                                    border:1px solid #bbf7d0;
                                    font-size:14px;
                                    width:auto;
                                ">
                                    <span class="text-muted">${cur}</span>
                                    <span class="fw-semibold ${amountClass}">
                                        ${sign}${Math.abs(balance).toLocaleString()}
                                    </span>
                                </div>`;
                        });

                        if (!paymentHtml) paymentHtml = '<span class="text-muted">{{ __('ui.blade_no_data') }}</span>';

                        $('#actual-total-cards').html(`
                            <div style="display:flex; flex-wrap:wrap;">
                                ${actualHtml}
                            </div>
                        `);

                        $('#payment-total-cards').html(`
                            <div style="display:flex; flex-wrap:wrap;">
                                ${paymentHtml}
                            </div>
                        `);
                    }
                });
            });

            $('#outgoing_currency_id').on('change', function() {
                syncModalCurrencies('outgoing');
            });

            $('#incoming_currency_id').on('change', function() {
                syncModalCurrencies('incoming');
            });

            $('#outgoing_amount').on('input', function() {
                syncModalAmounts('outgoing');
            });

            $('#incoming_amount').on('input', function() {
                syncModalAmounts('incoming');
            });

            $('#outgoing_partner_amount').on('input', function() {
                $(this).data('manual', true);
            });

            $('#incoming_partner_amount').on('input', function() {
                $(this).data('manual', true);
            });

            $('#outgoing_actual_amount').on('input', function() {
                $(this).data('manual', true);
            });

            $('#incoming_actual_amount').on('input', function() {
                $(this).data('manual', true);
            });

            $('#outgoing_commission_mode').on('change', function() {
                toggleCommissionFields('outgoing');
            });

            $('#incoming_commission_mode').on('change', function() {
                toggleCommissionFields('incoming');
            });

            $(document).on('input', '#outgoingRemittanceModal input[name="hawala_number"]', function() {
                outgoingManual = true;
            });

            $(document).on('input', '#incomingRemittanceModal input[name="hawala_number"]', function() {
                incomingManual = true;
            });

            $(document).on('input', '#outgoingRemittanceModal input[name="track_hawala_number"]', function() {
                outgoingTrackManual = true;
            });

            $(document).on('input', '#incomingRemittanceModal input[name="track_hawala_number"]', function() {
                incomingTrackManual = true;
            });

            $('#outgoingRemittanceModal input[name="remittance_mode"]').on('change', handleOutgoingModal);
            $('#incomingRemittanceModal input[name="remittance_mode"]').on('change', handleIncomingModal);

            $('#outgoing_to_account_id,#outgoing_track_account_id').on('change', handleOutgoingModal);
            $('#incoming_from_account_id,#incoming_track_account_id').on('change', handleIncomingModal);

            $('#outgoingRemittanceModal').on('shown.bs.modal', function() {
                let modal = $('#outgoingRemittanceModal');

                outgoingManual = outgoingEditMode;
                outgoingTrackManual = outgoingEditMode;

                initSelect2Inside('#outgoingRemittanceModal');

                $('#outgoing_partner_amount').data('manual', outgoingEditMode);
                $('#outgoing_actual_amount').data('manual', outgoingEditMode);

                if (HIDE_REMITTANCE_FIELDS && !outgoingEditMode) {
                    modal.find('input[type="hidden"][name="remittance_mode"]').val('track');

                    modal.find('#outgoing_track_account_wrap').show();

                    handleOutgoingModal();

                    setTimeout(function() {
                        autoOutgoingTrackHawala();
                    }, 300);

                    return;
                }

                if (!modal.find('input[name="remittance_mode"]:checked').length) {
                    modal.find('input[name="remittance_mode"][value="treasure"]').prop('checked', true);
                }

                handleOutgoingModal();
            });

            $(document).on('change', '#outgoing_track_account_id', function() {
                outgoingTrackManual = false;
                autoOutgoingTrackHawala();
            });
            $(document).on('input', '#outgoingRemittanceModal input[name="track_hawala_number"]', function() {
                outgoingTrackManual = true;
            });

            $('#outgoing_track_account_id').on('change', function() {
                outgoingTrackManual = false;
                autoOutgoingTrackHawala();
            });

            $('#incoming_track_account_id').on('change', function() {
                incomingTrackManual = false;
                autoIncomingTrackHawala();
            });

            $('#incomingRemittanceModal').on('shown.bs.modal', function() {
                incomingManual = incomingEditMode;
                incomingTrackManual = incomingEditMode;

                initSelect2Inside('#incomingRemittanceModal');

                $('#incoming_partner_amount').data('manual', incomingEditMode);
                $('#incoming_actual_amount').data('manual', incomingEditMode);

                if (!$('#incomingRemittanceModal input[name="remittance_mode"]:checked').length) {
                    $('#incomingRemittanceModal input[name="remittance_mode"][value="no_track"]').prop('checked', true);
                }

                handleIncomingModal();
            });

            $('#outgoingRemittanceModal').on('hidden.bs.modal', function() {
                outgoingEditMode = false;
                outgoingManual = false;
                outgoingTrackManual = false;
            });

            $('#incomingRemittanceModal').on('hidden.bs.modal', function() {
                incomingEditMode = false;
                incomingManual = false;
                incomingTrackManual = false;
            });

            $(document).on('click', '.btn-delete', function() {
                let id = $(this).data('id');

                accountsSwal.fire({
                    title: '{{{ __('ui.are_you_sure') }}}',
                    icon: 'warning',
                    showCancelButton: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/transactions/' + id,
                            type: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function() {
                                $('#current-table').DataTable().ajax.reload();
                                reloadSummary();

                                accountsSwal.fire({
                                    icon: 'success',
                                    title: '{{ __('ui.blade_deleted') }}',
                                    timer: 1000,
                                    showConfirmButton: false
                                });
                            }
                        });
                    }
                });
            });

            $(document).on('click', '#btn-whatsapp-balance', function() {
                let url = @json(route('admin.accounts.whatsappBalance', $account->id));

                let params = filters();

                let query = $.param(params);

                if (query) {
                    url += '?' + query;
                }

                window.open(url, '_blank');
            });

        });
    </script>

    <script>
        document.getElementById('openReport').addEventListener('click', function() {

            let currencyId = document.getElementById('currencySelect').value;
            let startDate = document.getElementById('startDate').value;
            let endDate = document.getElementById('endDate').value;

            let baseUrl = "{{ route('admin.accounts.exportPdf', $account->id) }}";

            let params = new URLSearchParams();

            if (currencyId) params.append('currency_id', currencyId);
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            let finalUrl = baseUrl + '?' + params.toString();

            window.open(finalUrl, '_blank');

        });
    </script>

    <script>
        function cleanEditAmount(value) {
            if (value === null || value === undefined) return '';

            value = String(value);
            value = value.replace(/,/g, '');
            value = value.replace(/[^\d.-]/g, '');

            return value;
        }

        function getDateOnly(value) {
            if (!value) return '';
            return String(value).substring(0, 10);
        }

        function setSelectValueProper(modal, selector, value) {
            let el = modal.find(selector);

            if (!el.length) return;

            el.val(value !== null && value !== undefined ? String(value) : '');
            el.trigger('change.select2');
            el.trigger('change');
        }

        function setRadioValueProper(modal, name, value) {
            if (!value) return;

            modal.find('input[name="' + name + '"][value="' + value + '"]')
                .prop('checked', true)
                .trigger('change');
        }

        function resetModalEditState(modal) {
            modal.removeData('edit-id');
            modal.removeData('linked-id');
            modal.removeData('source-transaction-id');

            // Transaction modal state
            modal.removeData('transaction-mode');
            modal.removeData('track-account-id');

            // Exchange modal state
            modal.removeData('exchange-mode');
        }

        function resetTransactionModalForm(modal) {
            let form = $('#transactionModalForm');

            if (!form.length || !form[0]) return;

            form[0].reset();

            form.find('.forced-transaction-field').remove();

            resetModalEditState(modal);

            modal.find('.modal-title').text(@json(__('accounts.new_transaction')));

            form.find('#transaction_modal_amount')
                .attr('name', 'entries[0][amount]')
                .val('');

            form.find('input[name="entries[0][note]"]').val('');
            form.find('input[name="reference"]').val('');

            form.find('input[name="transaction_mode"]').prop('checked', false);
            form.find('input[name="transaction_mode"][value="treasure"]').prop('checked', true);

            form.find('select[name="track_account_id"]').val('').trigger('change.select2');
            modal.find('.track-account-wrap').hide();

            form.find(':input').prop('disabled', false);
        }

        $(document).on('click', '.btn-new-transaction', function() {
            let modal = $('#transactionModal');

            resetTransactionModalForm(modal);

            setTimeout(function() {
                modal.find('.select2').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }

                    $(this).select2({
                language: @include('admin.accounts.partials.select-language'),
                        dropdownParent: modal,
                        width: '100%',
                        dir: 'rtl'
                    });
                });
            }, 200);
        });

        function setInputValueDirect(modal, selector, value) {
            let el = modal.find(selector).first();

            if (!el.length) {
                console.warn('Input not found:', selector);
                return;
            }

            let cleanValue = cleanEditAmount(value);
            if (el.hasClass('remittance-amount-input')) {
                cleanValue = window.formatRemittanceAmount(cleanValue.replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, ''));
            }

            el.val(cleanValue);
            el[0].value = cleanValue;

            el.trigger('input');
            el.trigger('keyup');
            el.trigger('change');
        }

        /*
        |--------------------------------------------------------------------------
        | EDIT BUTTON CLICK
        |--------------------------------------------------------------------------
        */

        $(document).off('click.editSource', '.btn-edit');

        $(document).on('click.editSource', '.btn-edit', function() {
            let transactionId = $(this).data('id');

            if (!transactionId) {
                accountsSwal.fire({
                    icon: 'error',
                    title: '{{ __('ui.blade_error') }}',
                    text: '{{ __('ui.blade_transaction_id_not_found') }}'
                });
                return;
            }

            $.get('/admin/transactions/source-edit/' + transactionId)
                .done(function(res) {
                    if (!res.success) {
                        accountsSwal.fire({
                            icon: 'error',
                            title: '{{ __('ui.blade_error') }}',
                            text: res.message || @json(__('accounts.cannot_edit'))
                        });
                        return;
                    }

                    if (res.module === 'journal') {
                        $.get('/admin/transactions/' + res.transaction.id + '/fetch')
                            .done(function(fetchRes) {
                                let transaction = fetchRes.transaction || res.transaction;

                                transaction.linked_id = fetchRes.linked_id || transaction.linked_id || transaction.id;

                                fillTransactionModal(transaction);
                            })
                            .fail(function() {
                                fillTransactionModal(res.transaction);
                            });

                        return;
                    }

                    if (res.module === 'remittance') {
                        if (res.remittance_type === 'outgoing') {
                            fillOutgoingRemittanceModal(res.remittance, res.transaction);
                        } else {
                            fillIncomingRemittanceModal(res.remittance, res.transaction);
                        }
                        return;
                    }

                    if (res.module === 'exchange') {
                        fillExchangeModal(res.exchange, res.transaction);
                        return;
                    }
                })
                .fail(function(xhr) {
                    accountsSwal.fire({
                        icon: 'error',
                        title: '{{ __('ui.blade_error') }}',
                        text: xhr.responseJSON?.message || @json(__('accounts.edit_data_failed'))
                    });
                });
        });

        /*
        |--------------------------------------------------------------------------
        | JOURNAL MODAL
        |--------------------------------------------------------------------------
        */

        function fillTransactionModal(t) {
            let modal = $('#transactionModal');
            let form = $('#transactionModalForm');

            if (form.length && form[0]) {
                form[0].reset();
            }

            resetModalEditState(modal);

            modal.data('linked-id', t.linked_id || t.id);
            modal.data('source-transaction-id', t.id);

            modal.find('.modal-title').text(@json(__('accounts.edit_transaction')));

            modal.modal('show');

            setTimeout(function() {

                /*
                |--------------------------------------------------------------------------
                | Date
                |--------------------------------------------------------------------------
                */
                modal.find('input[name="date"]').val(getDateOnly(t.date || t.created_at));

                /*
                |--------------------------------------------------------------------------
                | Type
                |--------------------------------------------------------------------------
                */
                modal.find('select[name="entries[0][type]"]')
                    .val(t.transaction_type || '')
                    .trigger('change');

                /*
                |--------------------------------------------------------------------------
                | Currency
                |--------------------------------------------------------------------------
                */
                modal.find('select[name="entries[0][currency_id]"]')
                    .val(t.currency_id ? String(t.currency_id) : '')
                    .trigger('change');

                /*
                |--------------------------------------------------------------------------
                | Amount
                |--------------------------------------------------------------------------
                */
                let amountValue =
                    t.amount_raw ??
                    t.amount ??
                    t.payment_amount ??
                    '';

                amountValue = cleanEditAmount(amountValue);

                let amountInput = modal.find('#transaction_modal_amount');

                if (!amountInput.length) {
                    amountInput = modal.find('input[data-original-name="entries[0][amount]"]');
                }

                if (!amountInput.length) {
                    amountInput = modal.find('input[name="entries[0][amount]"]');
                }

                if (!amountInput.length) {
                    amountInput = modal.find('input[name^="entries[0][amount]"]');
                }

                if (amountInput.length) {
                    amountInput
                        .attr('name', 'entries[0][amount]')
                        .val(amountValue)
                        .attr('value', amountValue)
                        .trigger('input')
                        .trigger('change');
                } else {
                    console.error('❌ Amount input still not found.', {
                        expected_id: '#transaction_modal_amount',
                        expected_name: 'entries[0][amount]',
                        available_fields: modal.find('input, select, textarea').map(function() {
                            return {
                                tag: this.tagName,
                                type: $(this).attr('type'),
                                id: $(this).attr('id'),
                                name: $(this).attr('name'),
                                data_original_name: $(this).attr('data-original-name'),
                                value: $(this).val()
                            };
                        }).get(),
                        response: t
                    });
                }

                /*
                |--------------------------------------------------------------------------
                | Note + Reference
                |--------------------------------------------------------------------------
                */
                modal.find('input[name="entries[0][note]"]')
                    .val(t.note || '');

                modal.find('input[name="reference"]')
                    .val(t.reference || '');

                /*
                |--------------------------------------------------------------------------
                | Mode
                |--------------------------------------------------------------------------
                | IMPORTANT:
                | Do not default to treasure here.
                | If backend cannot detect mode, no_track is safer than treasure.
                */
                let mode = t.transaction_mode || 'no_track';

                modal.data('transaction-mode', mode);
                modal.data('track-account-id', t.track_account_id || '');

                setRadioValueProper(modal, 'transaction_mode', mode);

                modal.find('.track-account-wrap').toggle(mode === 'track');

                if (mode === 'track') {
                    setSelectValueProper(
                        modal,
                        'select[name="track_account_id"]',
                        t.track_account_id ? String(t.track_account_id) : ''
                    );
                } else {
                    setSelectValueProper(modal, 'select[name="track_account_id"]', '');
                }

                /*
                |--------------------------------------------------------------------------
                | Second safety refill
                |--------------------------------------------------------------------------
                | Needed because the base layout may rename numeric inputs after focus/init.
                */
                setTimeout(function() {
                    let secondAmountInput = modal.find('#transaction_modal_amount');

                    if (!secondAmountInput.length) {
                        secondAmountInput = modal.find('input[data-original-name="entries[0][amount]"]');
                    }

                    if (!secondAmountInput.length) {
                        secondAmountInput = modal.find('input[name="entries[0][amount]"]');
                    }

                    if (!secondAmountInput.length) {
                        secondAmountInput = modal.find('input[name^="entries[0][amount]"]');
                    }

                    if (secondAmountInput.length) {
                        secondAmountInput
                            .attr('name', 'entries[0][amount]')
                            .val(amountValue)
                            .attr('value', amountValue)
                            .trigger('input')
                            .trigger('change');

                        console.log('✅ Transaction amount re-filled:', secondAmountInput.val());
                    } else {
                        console.error('❌ Amount input not found on second try:', $('#transactionModalForm').serializeArray());
                    }

                    let secondMode = t.transaction_mode || 'no_track';
                    modal.data('transaction-mode', secondMode);
                    modal.data('track-account-id', t.track_account_id || '');
                    setRadioValueProper(modal, 'transaction_mode', secondMode);

                    modal.find('.track-account-wrap').toggle(secondMode === 'track');

                    if (secondMode === 'track') {
                        setSelectValueProper(
                            modal,
                            'select[name="track_account_id"]',
                            t.track_account_id ? String(t.track_account_id) : ''
                        );
                    }

                }, 700);

            }, 500);
        }

        /*
        |--------------------------------------------------------------------------
        | OUTGOING REMITTANCE MODAL
        |--------------------------------------------------------------------------
        */

        function fillOutgoingRemittanceModal(r, transaction = null) {
            let modal = $('#outgoingRemittanceModal');
            let form = $('#outgoingRemittanceForm');
            outgoingEditMode = true;
            outgoingManual = true;
            outgoingTrackManual = true;

            if (form.length && form[0]) {
                form[0].reset();
            }

            resetModalEditState(modal);

            modal.data('edit-id', r.id);

            if (transaction && transaction.id) {
                modal.data('source-transaction-id', transaction.id);
            }

            modal.find('.modal-title').html(`<i class="bi bi-send"></i> {{ __('accounts.edit_outgoing') }}`);

            modal.modal('show');

            setTimeout(function() {
                initSelect2Inside('#outgoingRemittanceModal');

                outgoingManual = true;
                outgoingTrackManual = true;

                let mode = r.remittance_mode || 'treasure';

                modal.find('input[name="type"]').val('outgoing');
                modal.find('input[name="date"]').val(getDateOnly(r.date || r.created_at));

                modal.find('input[type="hidden"][name="remittance_mode"]').val(mode);
                if (!(typeof HIDE_REMITTANCE_FIELDS !== 'undefined' && HIDE_REMITTANCE_FIELDS)) {
                    setRadioValueProper(modal, 'remittance_mode', mode);
                }

                modal.find('#outgoing_track_account_wrap').toggle(mode === 'track');
                modal.find('#outgoing_hawala_no_wrap').toggle(mode === 'track');

                setSelectValueProper(modal, '#outgoing_currency_id', r.partner_currency_id);
                setSelectValueProper(modal, '#outgoing_partner_currency_id', r.currency_id);
                setSelectValueProper(modal, '#outgoing_actual_currency_id', r.actual_currency_id);

                modal.find('input[name="from_account_id"]').val(r.from_account_id);
                modal.find('input[name="to_account_id"]').val(r.to_account_id);
                setSelectValueProper(modal, '#outgoing_track_account_id', r.track_account_id);

                setInputValueDirect(modal, '#outgoing_partner_amount', r.partner_amount);
                setInputValueDirect(modal, '#outgoing_amount', r.amount);
                setInputValueDirect(modal, '#outgoing_actual_amount', r.actual_amount);

                modal.find('input[name="hawala_number"]').val(r.hawala_number || '');
                modal.find('input[name="track_hawala_number"]').val(r.track_hawala_number || '');

                modal.find('input[name="sender"]').val(r.sender || '');
                modal.find('input[name="receiver"]').val(r.receiver || '');
                modal.find('input[name="description"]').val(r.description || '');

                modal.find('#outgoing_commission_mode')
                    .val(r.commission_mode || 'none')
                    .trigger('change');

                setSelectValueProper(modal, '#outgoing_commission_currency_id', r.commission_currency_id);

                setInputValueDirect(modal, '#outgoing_commission_kabul_amount', r.commission_kabul_amount);
                setInputValueDirect(modal, '#outgoing_commission_partner_amount', r.commission_partner_amount);

                if (typeof handleOutgoingModal === 'function') {
                    handleOutgoingModal();
                }

                outgoingManual = true;
                outgoingTrackManual = true;

                setTimeout(function() {
                    if (mode === 'track') {
                        modal.find('#outgoing_track_account_wrap').show();
                        handleOutgoingModal();
                        setSelectValueProper(modal, '#outgoing_track_account_id', r.track_account_id);
                    }

                    modal.find('input[name="hawala_number"]').val(r.hawala_number || '');
                    modal.find('input[name="track_hawala_number"]').val(r.track_hawala_number || '');

                    setInputValueDirect(modal, '#outgoing_partner_amount', r.partner_amount);
                    setInputValueDirect(modal, '#outgoing_amount', r.amount);
                    setInputValueDirect(modal, '#outgoing_actual_amount', r.actual_amount);
                }, 400);

            }, 500);
        }

        /*
        |--------------------------------------------------------------------------
        | INCOMING REMITTANCE MODAL
        |--------------------------------------------------------------------------
        */

        function fillIncomingRemittanceModal(r, transaction = null) {
            let modal = $('#incomingRemittanceModal');
            let form = $('#incomingRemittanceForm');
            incomingEditMode = true;
            incomingManual = true;
            incomingTrackManual = true;

            if (form.length && form[0]) {
                form[0].reset();
            }

            resetModalEditState(modal);

            modal.data('edit-id', r.id);

            if (transaction && transaction.id) {
                modal.data('source-transaction-id', transaction.id);
            }

            modal.find('.modal-title').html(`<i class="bi bi-download"></i> {{ __('accounts.edit_incoming') }}`);

            modal.modal('show');

            setTimeout(function() {
                initSelect2Inside('#incomingRemittanceModal');

                incomingManual = true;
                incomingTrackManual = true;

                let mode = r.remittance_mode || 'no_track';

                modal.find('input[name="type"]').val('incoming');
                modal.find('input[name="date"]').val(getDateOnly(r.date || r.created_at));

                modal.find('input[type="hidden"][name="remittance_mode"]').val(mode);
                if (!(typeof HIDE_REMITTANCE_FIELDS !== 'undefined' && HIDE_REMITTANCE_FIELDS)) {
                    setRadioValueProper(modal, 'remittance_mode', mode);
                }

                modal.find('#incoming_track_account_wrap').toggle(mode === 'track');
                modal.find('#incoming_hawala_no_wrap').toggle(mode === 'track');

                setSelectValueProper(modal, '#incoming_currency_id', r.partner_currency_id);
                setSelectValueProper(modal, '#incoming_partner_currency_id', r.currency_id);
                setSelectValueProper(modal, '#incoming_actual_currency_id', r.actual_currency_id);

                modal.find('input[name="from_account_id"]').val(mode === 'track' ? r.to_account_id : r.from_account_id);
                modal.find('input[name="to_account_id"]').val(r.to_account_id);
                setSelectValueProper(modal, '#incoming_track_account_id', r.track_account_id);

                setInputValueDirect(modal, '#incoming_partner_amount', r.partner_amount);
                setInputValueDirect(modal, '#incoming_amount', r.amount);
                setInputValueDirect(modal, '#incoming_actual_amount', r.actual_amount);

                modal.find('input[name="hawala_number"]').val(r.hawala_number || '');
                modal.find('input[name="track_hawala_number"]').val(r.track_hawala_number || '');

                modal.find('input[name="sender"]').val(r.sender || '');
                modal.find('input[name="receiver"]').val(r.receiver || '');
                modal.find('input[name="description"]').val(r.description || '');

                modal.find('#incoming_commission_mode')
                    .val(r.commission_mode || 'none')
                    .trigger('change');

                setSelectValueProper(modal, '#incoming_commission_currency_id', r.commission_currency_id);

                setInputValueDirect(modal, '#incoming_commission_kabul_amount', r.commission_kabul_amount);
                setInputValueDirect(modal, '#incoming_commission_partner_amount', r.commission_partner_amount);

                if (typeof handleIncomingModal === 'function') {
                    handleIncomingModal();
                }

                incomingManual = true;
                incomingTrackManual = true;

                setTimeout(function() {
                    if (mode === 'track') {
                        modal.find('#incoming_track_account_wrap').show();
                        handleIncomingModal();
                        setSelectValueProper(modal, '#incoming_track_account_id', r.track_account_id);
                    }

                    modal.find('input[name="hawala_number"]').val(r.hawala_number || '');
                    modal.find('input[name="track_hawala_number"]').val(r.track_hawala_number || '');

                    setInputValueDirect(modal, '#incoming_partner_amount', r.partner_amount);
                    setInputValueDirect(modal, '#incoming_amount', r.amount);
                    setInputValueDirect(modal, '#incoming_actual_amount', r.actual_amount);
                }, 400);

            }, 500);
        }

        /*
        |--------------------------------------------------------------------------
        | EXCHANGE MODAL
        |--------------------------------------------------------------------------
        */

        function fillExchangeModal(e, transaction = null) {
            let modal = $('#exchangeModal');
            let form = $('#exchangeModalForm');

            if (form.length && form[0]) {
                form[0].reset();
            }

            resetModalEditState(modal);

            modal.data('edit-id', e.id);

            if (transaction && transaction.id) {
                modal.data('source-transaction-id', transaction.id);
            }

            modal.find('.modal-title').text(@json(__('accounts.edit_exchange')));

            modal.modal('show');

            setTimeout(function() {
                modal.find('.ex-select2').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }

                    $(this).select2({
                language: @include('admin.accounts.partials.select-language'),
                        dropdownParent: modal,
                        width: '100%',
                        dir: 'rtl'
                    });
                });

                modal.find('input[name="date"]').val(getDateOnly(e.date || e.created_at));

                setSelectValueProper(modal, '.ex-currency-from', e.from_currency_id);
                setSelectValueProper(modal, '.ex-currency-to', e.to_currency_id);

                setInputValueDirect(modal, '.ex-from-amount', e.from_amount);
                setInputValueDirect(modal, '.ex-to-amount', e.to_amount);
                setInputValueDirect(modal, '.ex-rate', e.rate);

                setTimeout(function() {
                    if (typeof modal.data('exchange-refresh') === 'function') {
                        modal.data('exchange-refresh')();
                    }
                }, 300);

                modal.find('.ex-operation')
                    .val(e.operation_type || 'multiply')
                    .trigger('change');

                modal.find('textarea[name="note"]').val(e.note || '');

                setRadioValueProper(modal, 'exchange_mode', e.mode || e.exchange_mode || 'no_track');

                setSelectValueProper(modal, 'select[name="track_account_id"]', e.track_account_id);

                setTimeout(function() {
                    setSelectValueProper(modal, '.ex-currency-from', e.from_currency_id);
                    setSelectValueProper(modal, '.ex-currency-to', e.to_currency_id);

                    setInputValueDirect(modal, '.ex-from-amount', e.from_amount);
                    setInputValueDirect(modal, '.ex-to-amount', e.to_amount);
                    setInputValueDirect(modal, '.ex-rate', e.rate);
                }, 400);

            }, 500);
        }
    </script>

    <script>
        $(document).on('click', '.btn-new-outgoing-remittance', function() {
            outgoingEditMode = false;
            outgoingManual = false;
            outgoingTrackManual = false;

            let modal = $('#outgoingRemittanceModal');

            modal.find('form')[0].reset();
            modal.find('input[name="hawala_number"]').val('');
            modal.find('input[name="track_hawala_number"]').val('');

            setTimeout(function() {
                handleOutgoingModal();
            }, 300);
        });

        $(document).on('click', '.btn-new-incoming-remittance', function() {
            incomingEditMode = false;
            incomingManual = false;
            incomingTrackManual = false;

            let modal = $('#incomingRemittanceModal');

            modal.find('form')[0].reset();
            modal.find('input[name="hawala_number"]').val('');
            modal.find('input[name="track_hawala_number"]').val('');

            setTimeout(function() {
                handleIncomingModal();
            }, 300);
        });
        /*
                |--------------------------------------------------------------------------
                | TRANSACTION SUBMIT
                |--------------------------------------------------------------------------
                */

        $('#transactionModalForm').off('submit').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let modal = $('#transactionModal');

            /*
            |--------------------------------------------------------------------------
            | CLEAN OLD FORCED FIELDS
            |--------------------------------------------------------------------------
            */
            form.find('.forced-transaction-field').remove();

            /*
            |--------------------------------------------------------------------------
            | FORCE AMOUNT NAME
            |--------------------------------------------------------------------------
            */
            form.find('#transaction_modal_amount').attr('name', 'entries[0][amount]');

            /*
            |--------------------------------------------------------------------------
            | CLEAN NUMBERS
            |--------------------------------------------------------------------------
            */
            form.find('.numeric-input, .remittance-amount-input').each(function() {
                let value = $(this).val();

                if (typeof convertToEnglishNumbers === 'function') {
                    value = convertToEnglishNumbers(value);
                }

                if (typeof cleanEditAmount === 'function') {
                    value = cleanEditAmount(value);
                } else {
                    value = (value || '').toString().replace(/,/g, '');
                }

                $(this).val(value);
                this.value = value;
            });

            /*
            |--------------------------------------------------------------------------
            | MODE
            |--------------------------------------------------------------------------
            */
            let linkedId = modal.data('linked-id');

            let selectedMode = modal.find('input[name="transaction_mode"]:checked').val();

            if (!selectedMode) {
                selectedMode = modal.data('transaction-mode') || 'no_track';
            }

            selectedMode = String(selectedMode || 'no_track');

            let trackAccountId = modal.find('select[name="track_account_id"]').val();

            if (!trackAccountId) {
                trackAccountId = modal.data('track-account-id') || '';
            }

            form.find('input[name="transaction_mode"]').prop('disabled', false);

            if (selectedMode === 'track') {
                if (!trackAccountId) {
                    accountsSwal.fire({
                        icon: 'warning',
                        title: '{{ __('ui.blade_track_account_required') }}',
                        text: '{{ __('ui.blade_please_select_track_account') }}'
                    });
                    return;
                }

                modal.find('.track-account-wrap').show();

                form.append(
                    `<input type="hidden" class="forced-transaction-field" name="transaction_mode" value="track">`
                );

                form.append(
                    `<input type="hidden" class="forced-transaction-field" name="track_account_id" value="${trackAccountId}">`
                );
            } else {
                form.find('select[name="track_account_id"]').val('').trigger('change.select2');

                form.append(
                    `<input type="hidden" class="forced-transaction-field" name="transaction_mode" value="${selectedMode}">`
                );
            }

            /*
            |--------------------------------------------------------------------------
            | SERIALIZE AFTER FORCED FIELDS
            |--------------------------------------------------------------------------
            */
            let data = form.serialize();

            console.log('Submitting transaction modal:', {
                linkedId: linkedId,
                selectedMode: selectedMode,
                trackAccountId: trackAccountId,
                data: data
            });

            if (linkedId) {
                $.ajax({
                    url: '/admin/transactions/' + linkedId,
                    type: 'POST',
                    data: data + '&_method=PUT',
                    success: function() {
                        modal.modal('hide');

                        resetTransactionModalForm(modal);

                        $('#current-table').DataTable().ajax.reload(null, false);
                        reloadSummary();

                        accountsSwal.fire({
                            icon: 'success',
                            title: '{{ __('ui.blade_updated') }}',
                            timer: 1000,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message || @json(__('accounts.transaction_update_failed'));

                        if (xhr.responseJSON?.errors) {
                            msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        }

                        accountsSwal.fire({
                            icon: 'error',
                            title: '{{ __('ui.blade_error') }}',
                            html: msg
                        });
                    }
                });

                return;
            }

            $.post('/admin/transactions', data)
                .done(function() {
                    modal.modal('hide');

                    resetTransactionModalForm(modal);

                    $('#current-table').DataTable().ajax.reload(null, false);
                    reloadSummary();

                    accountsSwal.fire({
                        icon: 'success',
                        title: '{{ __('ui.blade_saved') }}',
                        timer: 1000,
                        showConfirmButton: false
                    });
                })
                .fail(function(xhr) {
                    let msg = xhr.responseJSON?.message || @json(__('accounts.transaction_save_failed'));

                    if (xhr.responseJSON?.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }

                    accountsSwal.fire({
                        icon: 'error',
                        title: '{{ __('ui.blade_error') }}',
                        html: msg
                    });
                });
        });
        /*
        |--------------------------------------------------------------------------
        | EXCHANGE SUBMIT - CREATE OR UPDATE
        |--------------------------------------------------------------------------
        */

        $('#exchangeModalForm').off('submit').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let modal = $('#exchangeModal');
            let editId = modal.data('edit-id');

            form.find('.forced-exchange-field').remove();

            form.find('.ex-from-amount').attr('name', 'from_amount');
            form.find('.ex-to-amount').attr('name', 'to_amount');
            form.find('.ex-rate').attr('name', 'rate');
            form.find('.ex-operation').attr('name', 'operation_type');
            form.find('.ex-currency-from').attr('name', 'from_currency_id');
            form.find('.ex-currency-to').attr('name', 'to_currency_id');

            form.find('.numeric-input, .remittance-amount-input').each(function() {
                let value = $(this).val();

                if (typeof convertToEnglishNumbers === 'function') {
                    value = convertToEnglishNumbers(value);
                }

                if (typeof cleanEditAmount === 'function') {
                    value = cleanEditAmount(value);
                } else {
                    value = (value || '').toString().replace(/,/g, '');
                }

                $(this).val(value);
                this.value = value;
            });

            let fromAmount = parseFloat((form.find('[name="from_amount"]').val() || '0').replace(/,/g, ''));
            let rate = parseFloat((form.find('[name="rate"]').val() || '0').replace(/,/g, ''));
            let fromCurrency = form.find('[name="from_currency_id"]').val();
            let toCurrency = form.find('[name="to_currency_id"]').val();

            if (!fromCurrency) {
                accountsSwal.fire({
                    icon: 'warning',
                    title: '{{ __('ui.blade_sold_currency_required') }}',
                    text: '{{ __('ui.blade_please_select_sold_currency') }}'
                });
                return;
            }

            if (!toCurrency) {
                accountsSwal.fire({
                    icon: 'warning',
                    title: '{{ __('ui.blade_purchased_currency_required') }}',
                    text: '{{ __('ui.blade_please_select_purchased_currency') }}'
                });
                return;
            }

            if (fromCurrency === toCurrency) {
                accountsSwal.fire({
                    icon: 'warning',
                    title: '{{ __('ui.blade_invalid_currency_pair') }}',
                    text: '{{ __('ui.blade_sold_and_purchased_currency_cannot_be_the_same') }}'
                });
                return;
            }

            if (!fromAmount || fromAmount <= 0) {
                accountsSwal.fire({
                    icon: 'warning',
                    title: '{{ __('ui.blade_sold_amount_required') }}',
                    text: '{{ __('ui.blade_please_enter_sold_amount_2') }}'
                });
                return;
            }

            if (!rate || rate <= 0) {
                accountsSwal.fire({
                    icon: 'warning',
                    title: '{{ __('ui.blade_rate_required') }}',
                    text: '{{ __('ui.blade_please_enter_exchange_rate') }}'
                });
                return;
            }

            let selectedMode = form.find('input[name="exchange_mode"]:checked').val();

            if (!selectedMode) {
                selectedMode = modal.data('exchange-mode') || 'no_track';
            }

            selectedMode = String(selectedMode || 'no_track');

            let trackAccountId = form.find('select[name="track_account_id"]').val();

            if (selectedMode === 'track' && !trackAccountId) {
                accountsSwal.fire({
                    icon: 'warning',
                    title: '{{ __('ui.blade_track_account_required') }}',
                    text: '{{ __('ui.blade_please_select_track_account') }}'
                });
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT: force final exchange_mode and track_account_id
            | This prevents radio/select2 duplicated or stale values.
            |--------------------------------------------------------------------------
            */
            form.find('input[name="exchange_mode"]').prop('disabled', false);

            form.append(
                `<input type="hidden" class="forced-exchange-field" name="exchange_mode" value="${selectedMode}">`
            );

            if (selectedMode === 'track') {
                form.append(
                    `<input type="hidden" class="forced-exchange-field" name="track_account_id" value="${trackAccountId}">`
                );
            } else {
                form.find('select[name="track_account_id"]').val('').trigger('change.select2');
            }

            let url = editId ?
                '/admin/exchanges/modal-update/' + editId :
                "{{ route('admin.exchanges.modal-store') }}";

            let data = form.serialize();

            if (editId) {
                data += '&_method=PUT';
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: data,

                success: function(res) {
                    modal.modal('hide');

                    if (typeof resetModalEditState === 'function') {
                        resetModalEditState(modal);
                    } else {
                        modal.removeData('edit-id');
                        modal.removeData('source-transaction-id');
                        modal.removeData('exchange-mode');
                    }

                    accountsSwal.fire({
                        icon: 'success',
                        title: editId ? @json(__('accounts.updated')) : @json(__('accounts.saved')),
                        text: res.message || @json(__('accounts.exchange_saved')),
                        timer: 1200,
                        showConfirmButton: false
                    });

                    if ($.fn.DataTable.isDataTable('#current-table')) {
                        $('#current-table').DataTable().ajax.reload(null, false);
                    }

                    if (typeof reloadSummary === 'function') {
                        reloadSummary();
                    }

                    form[0].reset();
                    form.find('.ex-select2').val('').trigger('change.select2');
                    form.find('.ex-base-rate').text('—');
                    form.find('.ex-spread').text('—');
                    form.find('.ex-profit').text('—');
                    form.find('.ex-rate-hint').text('—');
                },

                error: function(xhr) {
                    let msg = xhr.responseJSON?.message || @json(__('accounts.exchange_failed'));

                    if (xhr.responseJSON?.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }

                    console.log('Exchange submit error:', {
                        status: xhr.status,
                        response: xhr.responseJSON,
                        sentData: data
                    });

                    accountsSwal.fire({
                        icon: 'error',
                        title: '{{ __('ui.blade_error') }}',
                        html: msg
                    });
                }
            });
        });

        /*
        |--------------------------------------------------------------------------
        | REMITTANCE SUBMIT - CREATE OR UPDATE
        |--------------------------------------------------------------------------
        */

        $(document).off('submit', '#outgoingRemittanceForm');
        $(document).off('submit', '#incomingRemittanceForm');

        $(document).on('submit', '#outgoingRemittanceForm', function(e) {
            e.preventDefault();
            handleRemittanceSubmit($(this), '#outgoingRemittanceModal');
        });

        $(document).on('submit', '#incomingRemittanceForm', function(e) {
            e.preventDefault();
            handleRemittanceSubmit($(this), '#incomingRemittanceModal');
        });

        function handleRemittanceSubmit(form, modalId) {
            let modal = $(modalId);
            let editId = modal.data('edit-id');

            let prefix = form.attr('id') === 'outgoingRemittanceForm' ? 'outgoing' : 'incoming';

            /*
            |--------------------------------------------------------------------------
            | FORCE FIELD NAMES
            |--------------------------------------------------------------------------
            */
            let amountInput = form.find('#' + prefix + '_amount');
            let partnerAmountInput = form.find('#' + prefix + '_partner_amount');
            let actualAmountInput = form.find('#' + prefix + '_actual_amount');
            let kabulCommissionInput = form.find('#' + prefix + '_commission_kabul_amount');
            let partnerCommissionInput = form.find('#' + prefix + '_commission_partner_amount');

            amountInput.attr('name', 'amount');
            partnerAmountInput.attr('name', 'partner_amount');
            actualAmountInput.attr('name', 'actual_amount');
            kabulCommissionInput.attr('name', 'commission_kabul_amount');
            partnerCommissionInput.attr('name', 'commission_partner_amount');

            /*
            |--------------------------------------------------------------------------
            | CLEAN NUMBERS BEFORE SERIALIZE
            |--------------------------------------------------------------------------
            */
            form.find('.numeric-input, .remittance-amount-input').each(function() {
                let value = $(this).val();

                if (typeof convertToEnglishNumbers === 'function') {
                    value = convertToEnglishNumbers(value);
                }

                value = cleanEditAmount(value);

                $(this).val(value);
                this.value = value;
            });

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT: FORCE MAIN AMOUNT AGAIN
            |--------------------------------------------------------------------------
            */
            let finalAmount = cleanEditAmount(amountInput.val());

            amountInput.val(finalAmount);
            amountInput[0].value = finalAmount;

            if (!finalAmount || parseFloat(finalAmount) <= 0) {
                accountsSwal.fire({
                    icon: 'warning',
                    title: '{{ __('ui.blade_amount_required') }}',
                    text: '{{ __('ui.blade_please_enter_a_valid_remittance_amount') }}'
                });

                console.log('Remittance amount debug:', {
                    prefix: prefix,
                    selector: '#' + prefix + '_amount',
                    amountValue: amountInput.val(),
                    amountName: amountInput.attr('name'),
                    formData: form.serialize()
                });

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | FIX DUPLICATE AMOUNT ISSUE
            |--------------------------------------------------------------------------
            | If any hidden/old duplicate amount input exists, remove it.
            |--------------------------------------------------------------------------
            */
            form.find('input[name="amount"]').not(amountInput).remove();
            form.find('input[name="partner_amount"]').not(partnerAmountInput).remove();
            form.find('input[name="actual_amount"]').not(actualAmountInput).remove();

            /*
            |--------------------------------------------------------------------------
            | URL: CREATE OR UPDATE
            |--------------------------------------------------------------------------
            */
            let url = editId ?
                '/admin/modal-remittances/' + editId :
                "{{ route('admin.modal-remittances.store') }}";

            let data = form.serialize();

            if (editId) {
                data += '&_method=PUT';
            }

            console.log('Submitting remittance:', {
                editId: editId,
                url: url,
                amount: finalAmount,
                data: data
            });

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(res) {
                    modal.modal('hide');

                    resetModalEditState(modal);

                    accountsSwal.fire({
                        icon: 'success',
                        title: editId ? @json(__('accounts.updated')) : @json(__('accounts.saved')),
                        text: res.message || @json(__('accounts.saved_successfully')),
                        timer: 1200,
                        showConfirmButton: false
                    });

                    $('#current-table').DataTable().ajax.reload();
                    reloadSummary();

                    form[0].reset();
                    form.find('.select2').val('').trigger('change.select2');
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON?.message || @json(__('accounts.failed'));

                    if (xhr.responseJSON?.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }

                    console.log('Remittance submit error:', {
                        status: xhr.status,
                        response: xhr.responseJSON,
                        sentData: data
                    });

                    accountsSwal.fire({
                        icon: 'error',
                        title: '{{ __('ui.blade_error') }}',
                        html: msg
                    });
                }
            });
        }
    </script>
@endsection
