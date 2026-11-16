@extends('layouts.admin.base')

@section('title', __('reports.balance_title'))

@section('css')

    <style>
        :root {
            --primary: #16a34a;
            --primary-dark: #15803d;
            --danger: #dc2626;
            --warning: #f59e0b;
            --info: #2563eb;
            --border: #e2e8f0;
            --bg: #f5f7fb;
            --card: #ffffff;
            --text: #334155;
            --muted: #64748b;
        }

        body {
            background: var(--bg);
        }

        .report-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid var(--border);
            box-shadow: 0 8px 20px rgba(0, 0, 0, .04);
            overflow: hidden;
        }

        .report-header {
            padding: 25px;
            border-bottom: 1px solid var(--border);
            text-align: center;
        }

        .report-header h3 {
            font-weight: 800;
            color: #374151;
            margin-bottom: 5px;
        }

        .report-header p {
            margin: 0;
            color: #64748b;
        }

        .filter-card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .05);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid var(--border);
            padding: 20px;
            transition: .2s;
        }

        .summary-card:hover {
            transform: translateY(-2px);
        }

        .summary-title {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .summary-value {
            font-size: 30px;
            font-weight: 800;
        }

        .cash {
            color: var(--info);
        }

        .profit {
            color: var(--primary);
        }

        .unpaid {
            color: var(--danger);
        }

        .total {
            color: #111827;
        }

        .section-box {
            background: #fff;
            border-radius: 18px;
            border: 1px solid var(--border);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .section-header {
            background: #f8fafc;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
        }

        .section-header h5 {
            margin: 0;
            font-weight: 700;
        }

        .balance-table {
            margin: 0;
        }

        .balance-table thead th {
            background: #fafafa;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            font-weight: 700;
            white-space: nowrap;
        }

        .balance-table tbody td {
            vertical-align: middle;
            font-weight: 600;
        }

        .text-credit {
            color: #16a34a;
            font-weight: 700;
        }

        .text-debit {
            color: #dc2626;
            font-weight: 700;
        }

        .text-balance {
            color: #2563eb;
            font-weight: 800;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: end;
        }

        .btn-close-day {
            background: #dc2626;
            color: #fff;
        }

        .btn-close-day:hover {
            background: #b91c1c;
            color: #fff;
        }

        .rate-box {
            background: #fff;
            border-radius: 18px;
            border: 1px solid var(--border);
            padding: 18px;
            margin-bottom: 20px;
        }

        .rates-grid {
            display: grid;
            gap: 1rem;
        }

        .rates-grid.rate-count-1 {
            grid-template-columns: minmax(0, 220px);
        }

        .rates-grid.rate-count-2 {
            grid-template-columns: repeat(2, minmax(0, 220px));
        }

        .rates-grid.rate-count-3 {
            grid-template-columns: repeat(3, minmax(0, 220px));
        }

        .rates-grid.rate-count-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .rates-grid.rate-count-5 {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .rates-grid.rate-count-6 {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }

        .currency-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .currency-tab {
            border: none;
            background: #f3f4f6;
            border-radius: 30px;
            padding: 8px 20px;
            font-weight: 700;
        }

        .currency-tab.active {
            background: #16a34a;
            color: #fff;
        }

        .footer-total {
            font-size: 18px;
            font-weight: 800;
        }

        @media(max-width:1199px) {
            .rates-grid.rate-count-4,
            .rates-grid.rate-count-5,
            .rates-grid.rate-count-6 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media(max-width:992px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width:767px) {
            .rates-grid[class*="rate-count-"] {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media(max-width:576px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .rates-grid[class*="rate-count-"] {
                grid-template-columns: 1fr;
            }
        }
    </style>

@endsection

@section('content')
    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1">{{ __('ui.blade_daily_balance_report') }}</h4>
                <p class="text-muted mb-0">
                    {{ __('reports.balance_intro') }}
                </p>
            </div>

            <div class="action-buttons">
                <button type="button" class="btn btn-outline-secondary" id="reloadReportBtn">
                    <i class="bi bi-arrow-repeat me-1"></i>
                    {{ __('reports.reload') }}
                </button>

                <button type="button" class="btn btn-outline-dark" id="printBtn">
                    <i class="bi bi-printer me-1"></i>
                    {{ __('ui.print') }}
                </button>

                <button type="button" class="btn btn-close-day" id="openCloseDayModalBtn">
                    <i class="bi bi-lock me-1"></i>
                    {{ __('reports.close_day') }}
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card filter-card mb-4">
            <div class="card-body p-4">
                <div class="row g-3 align-items-end">

                    <div class="col-lg-3 col-md-6">
                        <label for="report_date" class="form-label fw-bold">
                            {{ __('reports.report_date') }}
                        </label>

                        <input type="date" id="report_date" class="form-control" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-bold">
                            {{ __('reports.final_currency') }}
                        </label>

                        <select id="target_currency_id" class="form-select">
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}" data-code="{{ $currency->code }}" data-symbol="{{ $currency->symbol }}" @selected(optional($defaultCurrency)->id === $currency->id)>
                                    {{ $currency->code }}
                                    @if ($currency->name)
                                        - {{ $currency->name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-bold">
                            {{ __('reports.rate_mode') }}
                        </label>

                        <select id="rate_type" class="form-select">
                            <option value="">{{ __('ui.blade_latest_available_rate') }}</option>
                            <option value="purchase">{{ __('ui.blade_purchase_rate') }}</option>
                            <option value="sell">{{ __('ui.blade_sell_rate') }}</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <button type="button" class="btn btn-primary w-100" id="calculateBtn">
                            <i class="bi bi-calculator me-1"></i>
                            {{ __('reports.calculate') }}
                        </button>
                    </div>

                </div>
            </div>
        </div>

        {{-- Report Paper --}}
        <div class="report-card">

            <div class="report-header">
                <h3>{{ $company->company_name ?? 'Sarafi GH' }}</h3>
                <h5 class="mb-2">{{ __('ui.blade_daily_balance_and_closing_report') }}</h5>

                <p>
                    {{ __('reports.balance_as_of') }}
                    <strong id="reportDateText">
                        {{ now()->format('Y-m-d') }}
                    </strong>
                </p>

                <div class="mt-2">
                    <span class="badge bg-secondary" id="dayStatusBadge">
                        {{ __('reports.checking') }}
                    </span>
                </div>
            </div>

            <div class="p-4">

                {{-- Summary Cards --}}
                <div class="summary-grid">

                    <div class="summary-card">
                        <div class="summary-title">
                            {{ __('reports.main_balance') }}
                        </div>

                        <div class="summary-value cash" id="mainBalanceTotal">
                            0.00
                        </div>

                        <div class="small text-muted mt-2">
                            {{ __('reports.normal_accounts') }}
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-title">
                            {{ __('reports.profit_loss') }}
                        </div>

                        <div class="summary-value profit" id="profitLossTotal">
                            0.00
                        </div>

                        <div class="small text-muted mt-2" id="profitLossStatus">
                            {{ __('reports.no_profit_loss') }}
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-title">
                            {{ __('reports.unpaid_account') }}
                        </div>

                        <div class="summary-value unpaid" id="unpaidTotal">
                            0.00
                        </div>

                        <div class="small text-muted mt-2">
                            {{ __('reports.account_id') }} 4
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-title">
                            {{ __('reports.final_balance') }}
                        </div>

                        <div class="summary-value total" id="grandTotal">
                            0.00
                        </div>

                        <div class="small text-muted mt-2">
                            {{ __('reports.converted_to') }}
                            <strong id="targetCurrencyCode">
                                {{ optional($defaultCurrency)->code ?? '-' }}
                            </strong>
                        </div>
                    </div>

                </div>

                {{-- Previous Closing / Carry Forward --}}
                <div class="section-box">
                    <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5>{{ __('ui.blade_previous_closing') }}</h5>
                            <small class="text-muted">
                                {{ __('reports.previous_intro') }}
                            </small>
                        </div>

                        <span class="badge bg-light text-dark border" id="previousClosingDate">
                            {{ __('reports.no_previous') }}
                        </span>
                    </div>

                    <div class="p-3">
                        <div class="row g-3">

                            <div class="col-lg-3 col-md-6">
                                <div class="h-100 rounded border p-3">
                                    <div class="small text-muted fw-bold text-uppercase">
                                        {{ __('reports.carried_main') }}
                                    </div>

                                    <div class="fs-5 fw-bold mt-2" id="previousMainBalance">
                                        0.00
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <div class="h-100 rounded border p-3">
                                    <div class="small text-muted fw-bold text-uppercase">
                                        {{ __('reports.carried_profit') }}
                                    </div>

                                    <div class="fs-5 fw-bold mt-2" id="previousProfitLoss">
                                        0.00
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <div class="h-100 rounded border p-3">
                                    <div class="small text-muted fw-bold text-uppercase">
                                        {{ __('reports.carried_unpaid') }}
                                    </div>

                                    <div class="fs-5 fw-bold mt-2" id="previousUnpaid">
                                        0.00
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <div class="h-100 rounded border p-3">
                                    <div class="small text-muted fw-bold text-uppercase">
                                        {{ __('reports.carried_final') }}
                                    </div>

                                    <div class="fs-5 fw-bold mt-2" id="previousFinalBalance">
                                        0.00
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Exchange Rates --}}
                <div class="rate-box">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1">{{ __('ui.exchange_rates') }}</h5>
                            <p class="small text-muted mb-0">
                                {{ __('reports.rates_intro') }}
                            </p>
                        </div>

                        <button type="button" class="btn btn-outline-secondary btn-sm" id="reloadRatesBtn">
                            <i class="bi bi-arrow-repeat me-1"></i>
                            {{ __('reports.reload_rates') }}
                        </button>
                    </div>

                    @php
                        $rateCardCount = min($currencies->count(), 6);
                    @endphp
                    <div class="rates-grid rate-count-{{ $rateCardCount }}" id="ratesArea">
                        @foreach ($currencies as $currency)
                            <div>
                                <div class="rate-item rounded border p-3" id="rate-card-{{ $currency->id }}">

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>{{ $currency->code }}</strong>

                                        <span class="badge bg-light text-dark rate-source border" id="rate-source-{{ $currency->id }}">
                                            -
                                        </span>
                                    </div>

                                    <div class="input-group">
                                        <select class="operator-input form-select" id="operator-{{ $currency->id }}" data-currency-id="{{ $currency->id }}"
                                            style="max-width: 85px;">
                                            <option value="multiply">×</option>
                                            <option value="divide">÷</option>
                                            <option value="same">=</option>
                                            <option value="missing">?</option>
                                        </select>

                                        <input type="text" inputmode="decimal" class="form-control rate-input" id="rate-{{ $currency->id }}" data-currency-id="{{ $currency->id }}"
                                            step="0.000001" min="0" value="1">
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Main Balance Table --}}
                <div class="section-box">
                    <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5>{{ __('ui.blade_main_balance') }}</h5>
                            <small class="text-muted">
                                {{ __('reports.main_intro') }}
                            </small>
                        </div>

                        <strong id="mainBalanceSectionTotal">
                            0.00
                        </strong>
                    </div>

                    <div class="table-responsive">
                        <table class="balance-table table align-middle" id="mainBalanceTable">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.currency') }}</th>
                                    <th class="text-end">{{ __('ui.credit') }}</th>
                                    <th class="text-end">{{ __('ui.debit') }}</th>
                                    <th class="text-end">{{ __('ui.blade_real_balance') }}</th>
                                    <th class="text-center">{{ __('ui.rate') }}</th>
                                    <th class="text-end">{{ __('ui.blade_converted_balance') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-muted py-4 text-center">
                                        {{ __('reports.click_calculate') }}
                                    </td>
                                </tr>
                            </tbody>

                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">
                                        {{ __('reports.main_total') }}
                                    </th>

                                    <th class="footer-total text-end" id="mainBalanceFooterTotal">
                                        0.00
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Profit and Loss Table --}}
                <div class="section-box">
                    <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5>{{ __('ui.blade_profit_and_loss') }}</h5>
                            <small class="text-muted">
                                {{ __('reports.profit_intro') }}
                            </small>
                        </div>

                        <strong id="profitLossSectionTotal">
                            0.00
                        </strong>
                    </div>

                    <div class="table-responsive">
                        <table class="balance-table table align-middle" id="profitLossTable">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.currency') }}</th>
                                    <th class="text-end">{{ __('ui.blade_income') }}</th>
                                    <th class="text-end">{{ __('ui.blade_expense') }}</th>
                                    <th class="text-end">{{ __('ui.blade_profit_loss') }}</th>
                                    <th class="text-center">{{ __('ui.rate') }}</th>
                                    <th class="text-end">{{ __('ui.blade_converted') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-muted py-4 text-center">
                                        {{ __('reports.click_calculate') }}
                                    </td>
                                </tr>
                            </tbody>

                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">
                                        {{ __('reports.net_profit_loss') }}
                                    </th>

                                    <th class="footer-total text-end" id="profitLossFooterTotal">
                                        0.00
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Unpaid Table --}}
                <div class="section-box">
                    <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5>{{ __('ui.blade_unpaid_account') }}</h5>
                            <small class="text-muted">
                                {{ __('reports.unpaid_intro') }}
                            </small>
                        </div>

                        <strong id="unpaidSectionTotal">
                            0.00
                        </strong>
                    </div>

                    <div class="table-responsive">
                        <table class="balance-table table align-middle" id="unpaidTable">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.currency') }}</th>
                                    <th class="text-end">{{ __('ui.credit') }}</th>
                                    <th class="text-end">{{ __('ui.debit') }}</th>
                                    <th class="text-end">{{ __('ui.blade_real_balance') }}</th>
                                    <th class="text-center">{{ __('ui.rate') }}</th>
                                    <th class="text-end">{{ __('ui.blade_converted_balance') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-muted py-4 text-center">
                                        {{ __('reports.click_calculate') }}
                                    </td>
                                </tr>
                            </tbody>

                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">
                                        {{ __('reports.unpaid_total') }}
                                    </th>

                                    <th class="footer-total text-end" id="unpaidFooterTotal">
                                        0.00
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Final Totals --}}
                <div class="section-box">
                    <div class="section-header">
                        <h5>{{ __('ui.blade_final_balance_summary') }}</h5>
                    </div>

                    <div class="table-responsive">
                        <table class="balance-table mb-0 table">
                            <tbody>
                                <tr>
                                    <td class="fw-bold">{{ __('ui.blade_main_balance') }}</td>
                                    <td class="fw-bold text-primary text-end" id="summaryMainBalance">
                                        0.00
                                    </td>
                                </tr>

                                <tr>
                                    <td class="fw-bold">{{ __('ui.blade_profit_loss') }}</td>
                                    <td class="fw-bold text-end" id="summaryProfitLoss">
                                        0.00
                                    </td>
                                </tr>

                                <tr>
                                    <td class="fw-bold">{{ __('ui.unpaid') }}</td>
                                    <td class="fw-bold text-danger text-end" id="summaryUnpaid">
                                        0.00
                                    </td>
                                </tr>

                                <tr class="table-light">
                                    <td class="fw-bold fs-5">
                                        {{ __('reports.final_balance') }}
                                    </td>

                                    <td class="fw-bold fs-5 text-end" id="summaryGrandTotal">
                                        0.00
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Closing Information --}}
                <div class="alert alert-warning mb-0" id="closingNotice">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>

                        <div>
                            <strong>{{ __('ui.blade_day_closing') }}</strong>

                            {{ __('reports.closing_intro') }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Close Day Modal --}}
    <div class="modal fade" id="closeDayModal" tabindex="-1" aria-labelledby="closeDayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="closeDayModalLabel">
                        {{ __('reports.confirm_closing') }}
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>{{ __('ui.blade_important') }}</strong>
                        {{ __('reports.closing_warning') }}
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            {{ __('reports.closing_date') }}
                        </label>

                        <input type="date" id="closing_date" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="carry_forward_account_id" class="form-label fw-bold">
                            {{ __('reports.carry_main') }}
                        </label>

                        <select id="carry_forward_account_id" class="form-select">
                            <option value="">
                                {{ __('reports.select_account') }}
                            </option>

                            @foreach ($accounts ?? [] as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->name }}
                                    @if ($account->code)
                                        ({{ $account->code }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="profit_loss_carry_account_id" class="form-label fw-bold">
                            {{ __('reports.carry_profit') }}
                        </label>

                        <select id="profit_loss_carry_account_id" class="form-select">
                            <option value="">
                                {{ __('reports.select_account') }}
                            </option>

                            @foreach ($accounts ?? [] as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->name }}
                                    @if ($account->code)
                                        ({{ $account->code }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="closing_remarks" class="form-label fw-bold">
                            {{ __('reports.remarks') }}
                        </label>

                        <textarea id="closing_remarks" class="form-control" rows="3" placeholder="{{ __('ui.blade_optional_closing_notes') }}"></textarea>
                    </div>

                    <div class="bg-light rounded border p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('ui.blade_main_balance') }}</span>
                            <strong id="modalMainBalance">0.00</strong>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('ui.blade_profit_loss') }}</span>
                            <strong id="modalProfitLoss">0.00</strong>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('ui.unpaid') }}</span>
                            <strong id="modalUnpaid">0.00</strong>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">{{ __('ui.blade_final_balance') }}</span>
                            <strong id="modalFinalBalance">0.00</strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('ui.cancel') }}
                    </button>

                    <button type="button" class="btn btn-danger" id="confirmCloseDayBtn">
                        <i class="bi bi-lock-fill me-1"></i>
                        {{ __('reports.confirm_close') }}
                    </button>
                </div>

            </div>
        </div>
    </div>
@endsection

@php
    $reportCurrencies = $currencies
        ->map(function ($currency) {
            return [
                'id' => $currency->id,
                'code' => $currency->code,
                'symbol' => $currency->symbol,
            ];
        })
        ->values();
@endphp

@section('js')
    <script>
        const reportsSwal = Swal.mixin({confirmButtonText: @json(__('ui.blade_ok')), cancelButtonText: @json(__('ui.cancel'))});
        const reportCurrencies = @json($reportCurrencies);

        let currentReport = {
            main_balance_total: 0,
            profit_loss_total: 0,
            unpaid_total: 0,
            final_total: 0,
            is_closed: false,
            rows: {
                main: [],
                profit_loss: [],
                unpaid: []
            }
        };

        $(document).ready(function() {
            $('#targetCurrencyCode').text(getTargetCurrencyCode());

            loadRates();

            $('#calculateBtn').on('click', function() {
                calculateBalance();
            });

            $('#reloadReportBtn').on('click', function() {
                loadRates();
            });

            $('#reloadRatesBtn').on('click', function() {
                loadRates();
            });

            $('#target_currency_id').on('change', function() {
                $('#targetCurrencyCode').text(getTargetCurrencyCode());
                loadRates();
            });

            $('#rate_type').on('change', function() {
                loadRates();
            });

            $('#report_date').on('change', function() {
                const selectedDate = $(this).val();

                $('#reportDateText').text(selectedDate);
                $('#closing_date').val(selectedDate);

                loadRates();
            });

            $('.rate-input, .operator-input').on('change', function() {
                calculateBalance();
            });

            $('#openCloseDayModalBtn').on('click', function() {
                openCloseDayModal();
            });

            $('#confirmCloseDayBtn').on('click', function() {
                closeDay();
            });

            $('#printBtn').on('click', function() {
                printReport();
            });
        });

        function loadRates() {
            $.ajax({
                url: "{{ route('admin.reports.balance.rates') }}",
                type: "GET",
                data: {
                    target_currency_id: $('#target_currency_id').val(),
                    date: $('#report_date').val(),
                    type: $('#rate_type').val()
                },
                beforeSend: function() {
                    $('#reloadRatesBtn').prop('disabled', true);

                    $('.rate-source')
                        .removeClass('bg-success bg-danger bg-warning text-white')
                        .addClass('bg-light text-dark')
                        .text(@json(__('reports.loading')));

                    setCalculateButtonLoading(true);
                },
                success: function(response) {
                    const rates = response.rates || {};

                    reportCurrencies.forEach(function(currency) {
                        const item = rates[currency.id] || {
                            rate: 0,
                            operator: 'missing',
                            source: 'missing'
                        };

                        const rateInput = $(`#rate-${currency.id}`);
                        const operatorInput = $(`#operator-${currency.id}`);
                        const sourceBadge = $(`#rate-source-${currency.id}`);
                        const rateCard = $(`#rate-card-${currency.id}`);

                        rateInput.val(item.rate ?? 0);
                        operatorInput.val(item.operator ?? 'missing');

                        sourceBadge
                            .removeClass(
                                'bg-light text-dark bg-success bg-danger bg-warning text-white'
                            );

                        rateCard.removeClass(
                            'border-success border-danger border-warning'
                        );

                        if (item.source === 'same_currency') {
                            sourceBadge
                                .addClass('bg-success text-white')
                                .text(@json(__('reports.same')));

                            rateCard.addClass('border-success');

                            rateInput.prop('readonly', true);
                            operatorInput.prop('disabled', true);
                        } else if (item.source === 'direct') {
                            sourceBadge
                                .addClass('bg-success text-white')
                                .text(@json(__('reports.direct')));

                            rateCard.addClass('border-success');

                            rateInput.prop('readonly', false);
                            operatorInput.prop('disabled', false);
                        } else if (item.source === 'reverse') {
                            sourceBadge
                                .addClass('bg-warning text-dark')
                                .text(@json(__('reports.reverse')));

                            rateCard.addClass('border-warning');

                            rateInput.prop('readonly', false);
                            operatorInput.prop('disabled', false);
                        } else {
                            sourceBadge
                                .addClass('bg-danger text-white')
                                .text(@json(__('reports.missing')));

                            rateCard.addClass('border-danger');

                            rateInput.prop('readonly', false);
                            operatorInput.prop('disabled', false);
                        }
                    });

                    calculateBalance();
                },
                error: function(xhr) {
                    showAjaxError(
                        xhr,
                        @json(__('reports.rates_failed'))
                    );

                    setCalculateButtonLoading(false);
                },
                complete: function() {
                    $('#reloadRatesBtn').prop('disabled', false);
                }
            });
        }

        function calculateBalance() {
            const rates = collectRates();

            $.ajax({
                url: "{{ route('admin.reports.balance.data') }}",
                type: "GET",
                data: {
                    target_currency_id: $('#target_currency_id').val(),
                    date: $('#report_date').val(),
                    type: $('#rate_type').val(),
                    rates: rates
                },
                beforeSend: function() {
                    setCalculateButtonLoading(true);
                    showTableLoading('#mainBalanceTable', 6);
                    showTableLoading('#profitLossTable', 6);
                    showTableLoading('#unpaidTable', 6);

                    $('#dayStatusBadge')
                        .removeClass(
                            'bg-success bg-danger bg-warning bg-secondary'
                        )
                        .addClass('bg-secondary')
                        .text(@json(__('reports.calculating')));
                },
                success: function(response) {
                    updateReport(response);
                },
                error: function(xhr) {
                    showAjaxError(
                        xhr,
                        @json(__('reports.balance_failed'))
                    );

                    showTableError(
                        '#mainBalanceTable',
                        6,
                        @json(__('reports.main_failed'))
                    );

                    showTableError(
                        '#profitLossTable',
                        6,
                        @json(__('reports.profit_failed'))
                    );

                    showTableError(
                        '#unpaidTable',
                        6,
                        @json(__('reports.unpaid_failed'))
                    );
                },
                complete: function() {
                    setCalculateButtonLoading(false);
                }
            });
        }

        function updateReport(response) {
            const mainRows = response.main_rows || [];
            const profitLossRows = response.profit_loss_rows || [];
            const unpaidRows = response.unpaid_rows || [];

            const summary = response.summary || {};
            const previousClosing = response.previous_closing || null;
            const closing = response.closing || {};

            currentReport = {
                main_balance_total: parseNumber(
                    summary.main_balance_total
                ),
                profit_loss_total: parseNumber(
                    summary.profit_loss_total
                ),
                unpaid_total: parseNumber(
                    summary.unpaid_total
                ),
                final_total: parseNumber(
                    summary.final_total
                ),
                is_closed: Boolean(closing.is_closed),
                rows: {
                    main: mainRows,
                    profit_loss: profitLossRows,
                    unpaid: unpaidRows
                }
            };

            renderStandardBalanceTable(
                '#mainBalanceTable',
                mainRows,
                @json(__('reports.no_main'))
            );

            renderProfitLossTable(
                profitLossRows
            );

            renderStandardBalanceTable(
                '#unpaidTable',
                unpaidRows,
                @json(__('reports.no_unpaid'))
            );

            updateSummaryCards(summary);
            updatePreviousClosing(previousClosing);
            updateClosingStatus(closing);

            $('#reportDateText').text(
                response.date || $('#report_date').val()
            );

            $('#closing_date').val(
                response.date || $('#report_date').val()
            );
        }

        function renderStandardBalanceTable(
            tableSelector,
            rows,
            emptyMessage
        ) {
            const tbody = $(`${tableSelector} tbody`);
            let html = '';

            if (!rows.length) {
                html = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            ${escapeHtml(emptyMessage)}
                        </td>
                    </tr>
                `;

                tbody.html(html);
                return;
            }

            rows.forEach(function(row) {
                const credit = parseNumber(row.total_credit);
                const debit = parseNumber(row.total_debit);
                const balance = parseNumber(row.balance);
                const converted = parseNumber(row.converted_amount);

                html += `
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark border">
                                ${escapeHtml(row.currency_code || '-')}
                            </span>
                        </td>

                        <td class="text-end text-credit">
                            ${formatNumber(credit)}
                        </td>

                        <td class="text-end text-debit">
                            ${formatNumber(debit)}
                        </td>

                        <td class="text-end ${valueClass(balance)}">
                            ${formatSignedNumber(balance)}
                        </td>

                        <td class="text-center">
                            ${formatRate(row)}
                        </td>

                        <td class="text-end ${valueClass(converted)}">
                            ${formatSignedNumber(converted)}
                        </td>
                    </tr>
                `;
            });

            tbody.html(html);
        }

        function renderProfitLossTable(rows) {
            const tbody = $('#profitLossTable tbody');
            let html = '';

            if (!rows.length) {
                tbody.html(`
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            {{ __('reports.no_profit_found') }}
                        </td>
                    </tr>
                `);

                return;
            }

            rows.forEach(function(row) {
                const income = parseNumber(row.total_income);
                const expense = parseNumber(row.total_expense);
                const profitLoss = parseNumber(row.profit_loss);
                const converted = parseNumber(row.converted_amount);

                html += `
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark border">
                                ${escapeHtml(row.currency_code || '-')}
                            </span>
                        </td>

                        <td class="text-end text-credit">
                            ${formatNumber(income)}
                        </td>

                        <td class="text-end text-debit">
                            ${formatNumber(expense)}
                        </td>

                        <td class="text-end ${valueClass(profitLoss)}">
                            ${formatSignedNumber(profitLoss)}
                        </td>

                        <td class="text-center">
                            ${formatRate(row)}
                        </td>

                        <td class="text-end ${valueClass(converted)}">
                            ${formatSignedNumber(converted)}
                        </td>
                    </tr>
                `;
            });

            tbody.html(html);
        }

        function updateSummaryCards(summary) {
            const mainBalance = parseNumber(
                summary.main_balance_total
            );

            const profitLoss = parseNumber(
                summary.profit_loss_total
            );

            const unpaid = parseNumber(
                summary.unpaid_total
            );

            const finalBalance = parseNumber(
                summary.final_total
            );

            setValue(
                '#mainBalanceTotal',
                mainBalance
            );

            setValue(
                '#profitLossTotal',
                profitLoss
            );

            setValue(
                '#unpaidTotal',
                unpaid
            );

            setValue(
                '#grandTotal',
                finalBalance
            );

            setValue(
                '#mainBalanceSectionTotal',
                mainBalance
            );

            setValue(
                '#mainBalanceFooterTotal',
                mainBalance
            );

            setValue(
                '#profitLossSectionTotal',
                profitLoss
            );

            setValue(
                '#profitLossFooterTotal',
                profitLoss
            );

            setValue(
                '#unpaidSectionTotal',
                unpaid
            );

            setValue(
                '#unpaidFooterTotal',
                unpaid
            );

            setValue(
                '#summaryMainBalance',
                mainBalance
            );

            setValue(
                '#summaryProfitLoss',
                profitLoss
            );

            setValue(
                '#summaryUnpaid',
                unpaid
            );

            setValue(
                '#summaryGrandTotal',
                finalBalance
            );

            setValue(
                '#modalMainBalance',
                mainBalance
            );

            setValue(
                '#modalProfitLoss',
                profitLoss
            );

            setValue(
                '#modalUnpaid',
                unpaid
            );

            setValue(
                '#modalFinalBalance',
                finalBalance
            );

            const profitStatus = $('#profitLossStatus');
            const profitValue = $('#profitLossTotal');
            const summaryProfit = $('#summaryProfitLoss');

            profitValue
                .removeClass('profit unpaid cash total text-success text-danger');

            summaryProfit
                .removeClass('text-success text-danger text-muted');

            if (profitLoss > 0) {
                profitStatus.text(@json(__('reports.net_profit')));
                profitValue.addClass('text-success');
                summaryProfit.addClass('text-success');
            } else if (profitLoss < 0) {
                profitStatus.text(@json(__('reports.net_loss')));
                profitValue.addClass('text-danger');
                summaryProfit.addClass('text-danger');
            } else {
                profitStatus.text(@json(__('reports.no_profit_loss')));
                profitValue.addClass('text-muted');
                summaryProfit.addClass('text-muted');
            }

            $('#targetCurrencyCode').text(
                summary.target_currency_code ||
                getTargetCurrencyCode()
            );
        }

        function updatePreviousClosing(previousClosing) {
            if (!previousClosing) {
                $('#previousClosingDate').text(
                    @json(__('reports.no_previous'))
                );

                setValue('#previousMainBalance', 0);
                setValue('#previousProfitLoss', 0);
                setValue('#previousUnpaid', 0);
                setValue('#previousFinalBalance', 0);

                return;
            }

            $('#previousClosingDate').text(
                previousClosing.closing_date ||
                @json(__('reports.previous_closing'))
            );

            setValue(
                '#previousMainBalance',
                previousClosing.main_balance_total ??
                previousClosing.cash_balance
            );

            setValue(
                '#previousProfitLoss',
                previousClosing.profit_loss_total ??
                previousClosing.profit_balance
            );

            setValue(
                '#previousUnpaid',
                previousClosing.unpaid_total ??
                previousClosing.unpaid_balance
            );

            setValue(
                '#previousFinalBalance',
                previousClosing.final_total ??
                previousClosing.final_balance
            );
        }

        function updateClosingStatus(closing) {
            const isClosed = Boolean(closing && closing.is_closed);
            const badge = $('#dayStatusBadge');
            const closeButton = $('#openCloseDayModalBtn');
            const notice = $('#closingNotice');

            badge.removeClass(
                'bg-success bg-danger bg-warning bg-secondary'
            );

            if (isClosed) {
                badge
                    .addClass('bg-success')
                    .text(
                        closing.closed_at ?
                        `{{ __('reports.closed_at') }} ${closing.closed_at}` :
                        @json(__('reports.day_closed'))
                    );

                closeButton
                    .prop('disabled', true)
                    .html(`
                        <i class="bi bi-lock-fill me-1"></i>
                        {{ __('reports.day_closed') }}
                    `);

                notice
                    .removeClass('alert-warning')
                    .addClass('alert-success')
                    .html(`
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill mt-1"></i>

                            <div>
                                <strong>{{ __('ui.blade_this_day_has_been_closed') }}</strong>
                                {{ __('reports.no_extra_closing') }}
                                ${escapeHtml($('#report_date').val())}.
                            </div>
                        </div>
                    `);
            } else {
                badge
                    .addClass('bg-warning text-dark')
                    .text(@json(__('reports.open_day')));

                closeButton
                    .prop('disabled', false)
                    .html(`
                        <i class="bi bi-lock me-1"></i>
                        {{ __('reports.close_day') }}
                    `);

                notice
                    .removeClass('alert-success')
                    .addClass('alert-warning')
                    .html(`
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>

                            <div>
                                <strong>{{ __('ui.blade_day_closing') }}</strong>
                                {{ __('reports.closing_short') }}
                            </div>
                        </div>
                    `);
            }
        }

        function openCloseDayModal() {
            if (currentReport.is_closed) {
                showWarning(
                    @json(__('reports.already_closed'))
                );

                return;
            }

            const reportDate = $('#report_date').val();

            if (!reportDate) {
                showWarning(
                    @json(__('reports.select_date'))
                );

                return;
            }

            const today = "{{ now()->format('Y-m-d') }}";

            if (reportDate > today) {
                showWarning(
                    @json(__('reports.future_date'))
                );

                return;
            }

            $('#closing_date').val(reportDate);

            setValue(
                '#modalMainBalance',
                currentReport.main_balance_total
            );

            setValue(
                '#modalProfitLoss',
                currentReport.profit_loss_total
            );

            setValue(
                '#modalUnpaid',
                currentReport.unpaid_total
            );

            setValue(
                '#modalFinalBalance',
                currentReport.final_total
            );

            const modalElement = document.getElementById(
                'closeDayModal'
            );

            const modal = bootstrap.Modal.getOrCreateInstance(
                modalElement
            );

            modal.show();
        }

        function closeDay() {
            const carryForwardAccountId = $(
                '#carry_forward_account_id'
            ).val();

            const profitLossCarryAccountId = $(
                '#profit_loss_carry_account_id'
            ).val();

            if (!carryForwardAccountId) {
                showWarning(
                    @json(__('reports.select_main'))
                );

                return;
            }

            if (!profitLossCarryAccountId) {
                showWarning(
                    @json(__('reports.select_profit'))
                );

                return;
            }

            reportsSwal.fire({
                title: '{{ __('ui.blade_close_this_business_day') }}',
                text: '{{ __('ui.blade_this_creates_financial_transactions_and_a_permanent_daily_closing_snap') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('ui.blade_yes_close_the_day') }}',
                cancelButtonText: '{{ __('ui.cancel') }}',
                confirmButtonColor: '#dc2626'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                submitClosing();
            });
        }

        function submitClosing() {
            const button = $('#confirmCloseDayBtn');

            $.ajax({
                url: "{{ route('admin.reports.balance.close') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    closing_date: $('#closing_date').val(),
                    target_currency_id: $('#target_currency_id').val(),
                    carry_forward_account_id: $('#carry_forward_account_id').val(),
                    profit_loss_carry_account_id: $('#profit_loss_carry_account_id').val(),
                    remarks: $('#closing_remarks').val(),
                    rates: collectRates()
                },
                beforeSend: function() {
                    button
                        .prop('disabled', true)
                        .html(`
                            <span
                                class="spinner-border spinner-border-sm me-1"
                                role="status"
                            ></span>
                            {{ __('reports.closing') }}
                        `);
                },
                success: function(response) {
                    const modalElement = document.getElementById(
                        'closeDayModal'
                    );

                    const modal = bootstrap.Modal.getInstance(
                        modalElement
                    );

                    if (modal) {
                        modal.hide();
                    }

                    reportsSwal.fire({
                        icon: 'success',
                        title: '{{ __('ui.blade_day_closed') }}',
                        text: response.message ||
                            @json(__('reports.closed_success'))
                    });

                    $('#closing_remarks').val('');

                    calculateBalance();
                },
                error: function(xhr) {
                    showAjaxError(
                        xhr,
                        @json(__('reports.close_failed'))
                    );
                },
                complete: function() {
                    button
                        .prop('disabled', false)
                        .html(`
                            <i class="bi bi-lock-fill me-1"></i>
                            {{ __('reports.confirm_close') }}
                        `);
                }
            });
        }

        function printReport() {
            const form = $('<form>', {
                method: 'POST',
                action: "{{ route('admin.reports.balance.print') }}",
                target: '_blank'
            });

            form.append(
                $('<input>', {
                    type: 'hidden',
                    name: '_token',
                    value: "{{ csrf_token() }}"
                })
            );

            form.append(
                $('<input>', {
                    type: 'hidden',
                    name: 'date',
                    value: $('#report_date').val()
                })
            );

            form.append(
                $('<input>', {
                    type: 'hidden',
                    name: 'target_currency_id',
                    value: $('#target_currency_id').val()
                })
            );

            form.append(
                $('<input>', {
                    type: 'hidden',
                    name: 'type',
                    value: $('#rate_type').val()
                })
            );

            const rates = collectRates();

            Object.keys(rates).forEach(function(currencyId) {
                form.append(
                    $('<input>', {
                        type: 'hidden',
                        name: `rates[${currencyId}][rate]`,
                        value: rates[currencyId].rate
                    })
                );

                form.append(
                    $('<input>', {
                        type: 'hidden',
                        name: `rates[${currencyId}][operator]`,
                        value: rates[currencyId].operator
                    })
                );
            });

            $('body').append(form);
            form.trigger('submit');
            form.remove();
        }

        function collectRates() {
            const rates = {};

            reportCurrencies.forEach(function(currency) {
                rates[currency.id] = {
                    rate: $(`#rate-${currency.id}`).val() || 0,
                    operator: $(`#operator-${currency.id}`).val() ||
                        'missing'
                };
            });

            return rates;
        }

        function formatRate(row) {
            const operator = row.operator || 'missing';
            const rate = parseNumber(row.rate);

            if (operator === 'same') {
                return `
                    <span class="badge bg-success">
                        = 1
                    </span>
                `;
            }

            if (operator === 'multiply') {
                return `
                    <span class="badge bg-light text-dark border">
                        × ${formatRateNumber(rate)}
                    </span>
                `;
            }

            if (operator === 'divide') {
                return `
                    <span class="badge bg-light text-dark border">
                        ÷ ${formatRateNumber(rate)}
                    </span>
                `;
            }

            return `
                <span class="badge bg-danger">
                    {{ __('reports.missing') }}
                </span>
            `;
        }

        function setCalculateButtonLoading(isLoading) {
            const button = $('#calculateBtn');

            if (isLoading) {
                button
                    .prop('disabled', true)
                    .html(`
                        <span
                            class="spinner-border spinner-border-sm me-1"
                            role="status"
                        ></span>
                        {{ __('reports.calculating') }}
                    `);

                return;
            }

            button
                .prop('disabled', false)
                .html(`
                    <i class="bi bi-calculator me-1"></i>
                    {{ __('reports.calculate') }}
                `);
        }

        function showTableLoading(tableSelector, colspan) {
            $(`${tableSelector} tbody`).html(`
                <tr>
                    <td
                        colspan="${colspan}"
                        class="text-center text-muted py-4"
                    >
                        <span
                            class="spinner-border spinner-border-sm me-2"
                            role="status"
                        ></span>
                        {{ __('reports.calculating') }}
                    </td>
                </tr>
            `);
        }

        function showTableError(
            tableSelector,
            colspan,
            message
        ) {
            $(`${tableSelector} tbody`).html(`
                <tr>
                    <td
                        colspan="${colspan}"
                        class="text-center text-danger py-4"
                    >
                        ${escapeHtml(message)}
                    </td>
                </tr>
            `);
        }

        function setValue(selector, value) {
            const number = parseNumber(value);
            const element = $(selector);

            element
                .removeClass(
                    'text-success text-danger text-muted text-primary'
                )
                .text(formatSignedNumber(number));

            if (number > 0) {
                element.addClass('text-success');
            } else if (number < 0) {
                element.addClass('text-danger');
            } else {
                element.addClass('text-muted');
            }
        }

        function valueClass(value) {
            const number = parseNumber(value);

            if (number > 0) {
                return 'text-credit';
            }

            if (number < 0) {
                return 'text-debit';
            }

            return 'text-muted';
        }

        function formatNumber(value) {
            return parseNumber(value).toLocaleString(
                undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );
        }

        function formatSignedNumber(value) {
            const number = parseNumber(value);

            if (number < 0) {
                return `-${formatNumber(Math.abs(number))}`;
            }

            return formatNumber(number);
        }

        function formatRateNumber(value) {
            return parseNumber(value).toLocaleString(
                undefined, {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 6
                }
            );
        }

        function parseNumber(value) {
            const number = parseFormattedNumber(value);

            return Number.isFinite(number) ?
                number :
                0;
        }

        function getTargetCurrencyCode() {
            return $('#target_currency_id option:selected')
                .data('code') || '-';
        }

        function showAjaxError(xhr, defaultMessage) {
            let message = defaultMessage;

            if (
                xhr.responseJSON &&
                xhr.responseJSON.message
            ) {
                message = xhr.responseJSON.message;
            }

            if (
                xhr.status === 422 &&
                xhr.responseJSON &&
                xhr.responseJSON.errors
            ) {
                const errors = Object.values(
                    xhr.responseJSON.errors
                ).flat();

                if (errors.length) {
                    message = errors[0];
                }
            }

            reportsSwal.fire({
                icon: 'error',
                title: '{{ __('ui.blade_error') }}',
                text: message
            });
        }

        function showWarning(message) {
            reportsSwal.fire({
                icon: 'warning',
                title: '{{ __('ui.blade_attention') }}',
                text: message
            });
        }

        function escapeHtml(value) {
            return $('<div>')
                .text(value ?? '')
                .html();
        }
    </script>
@endsection
