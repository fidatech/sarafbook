@extends('layouts.admin.base')

@section('css')
<style>
    .report-page {
        background: #f6f8fb;
        min-height: calc(100vh - 100px);
        padding: 1px;
        border-radius: 18px;
    }

    .report-main-card {
        border: 0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 12px 35px rgba(15, 23, 42, .08);
    }

    .report-header {
        background: linear-gradient(135deg, #111827, #1f2937);
        padding: 24px;
        color: #fff;
    }

    .report-header h2 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -.4px;
        font-size: 24px;
    }

    .report-header p {
        margin: 7px 0 0;
        color: #cbd5e1;
        font-size: 14px;
    }

    .section-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
    }

    .filter-card {
        padding: 18px;
    }

    .filter-title {
        font-size: 15px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-title i {
        color: #2563eb;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .summary-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 17px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
    }

    .summary-card:before {
        content: "";
        width: 78px;
        height: 78px;
        border-radius: 50%;
        background: #eef2ff;
        position: absolute;
        right: -22px;
        top: -22px;
    }

    .summary-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .4px;
        position: relative;
        z-index: 2;
    }

    .summary-value {
        font-size: 23px;
        font-weight: 900;
        color: #111827;
        margin-top: 7px;
        position: relative;
        z-index: 2;
    }

    .summary-sub {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 3px;
        position: relative;
        z-index: 2;
    }

    .table-card {
        padding: 16px;
    }

    .table-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 13px;
    }

    .table-section-title {
        font-size: 16px;
        font-weight: 900;
        color: #111827;
        margin: 0;
    }

    .table-section-subtitle {
        font-size: 12px;
        color: #64748b;
        margin-top: 3px;
    }

    .table thead th {
        background: #f8fafc;
        color: #334155;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .35px;
        border-bottom: 1px solid #e5e7eb !important;
        white-space: nowrap;
    }

    .table td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .badge-purchase {
        background: #dcfce7;
        color: #166534;
        padding: 5px 9px;
        border-radius: 999px;
        font-weight: 800;
        font-size: 12px;
    }

    .badge-sell {
        background: #fee2e2;
        color: #991b1b;
        padding: 5px 9px;
        border-radius: 999px;
        font-weight: 800;
        font-size: 12px;
    }

    .amount-positive {
        color: #15803d !important;
        font-weight: 900;
    }

    .amount-negative {
        color: #b91c1c !important;
        font-weight: 900;
    }

    .amount-dark {
        color: #111827 !important;
        font-weight: 900;
    }

    .pair-total-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .pair-total-card {
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        padding: 16px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
    }

    .pair-total-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
    }

    .currency-pair-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 7px 12px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 14px;
        font-weight: 900;
    }

    .pair-count {
        font-size: 12px;
        color: #64748b;
        font-weight: 700;
    }

    .pair-line {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 0;
        border-top: 1px dashed #e5e7eb;
    }

    .pair-line:first-of-type {
        border-top: 0;
    }

    .pair-line-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .pair-line-value {
        color: #111827;
        font-size: 13px;
        font-weight: 900;
        text-align: right;
    }

    .empty-box {
        border: 1px dashed #cbd5e1;
        border-radius: 16px;
        padding: 18px;
        color: #64748b;
        text-align: center;
        background: #f8fafc;
    }

    @media(max-width: 1200px) {
        .pair-total-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media(max-width: 992px) {
        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media(max-width: 576px) {
        .summary-grid,
        .pair-total-grid {
            grid-template-columns: 1fr;
        }

        .report-header {
            padding: 18px;
        }
    }
</style>
@endsection

@section('content')
<div class="report-page">
    <div class="card report-main-card">
        <div class="report-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="text-white">{{ __('reports.customer_exchange_report') }}</h2>
                <p>{{ __('ui.blade_customer_wise_exchange_report_directly_from_the_exchanges_table') }}</p>
            </div>

            <button type="button" class="btn btn-light btn-sm" id="printBtn">
                <i class="bi bi-printer"></i> {{ __('ui.print') }}
            </button>
        </div>

        <div class="section-card filter-card m-3">
            <div class="filter-title">
                <i class="bi bi-funnel"></i>
                {{ __('reports.report_filters') }}
            </div>

            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">{{ __('ui.blade_from_date') }}</label>
                        <input type="date" name="date_from" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">{{ __('ui.blade_to_date') }}</label>
                        <input type="date" name="date_to" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.blade_customer_account') }}</label>
                        <select name="account_id" class="form-select">
                            <option value="">{{ __('ui.all_accounts') }}</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->name }} @if($account->code) - {{ $account->code }} @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">{{ __('ui.currency') }}</label>
                        <select name="currency_id" class="form-select">
                            <option value="">{{ __('ui.all_currencies') }}</option>
                            @foreach($currencies as $currency)
                                <option value="{{ $currency->id }}">
                                    {{ $currency->code ?? $currency->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">{{ __('ui.type') }}</label>
                        <select name="type" class="form-select">
                            <option value="">{{ __('ui.all') }}</option>
                            <option value="purchase">{{ __('ui.blade_purchase') }}</option>
                            <option value="sell">{{ __('ui.blade_sell') }}</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">{{ __('ui.mode') }}</label>
                        <select name="mode" class="form-select">
                            <option value="">{{ __('ui.all') }}</option>
                            <option value="cash">{{ __('ui.blade_cash') }}</option>
                            <option value="customer">{{ __('ui.customer') }}</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> {{ __('reports.filter_report') }}
                    </button>

                    <button type="button" class="btn btn-outline-secondary" id="resetBtn">
                        <i class="bi bi-arrow-clockwise"></i> {{ __('ui.reset') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="section-card table-card m-3">
            <div class="table-section-header">
                <div>
                    <h5 class="table-section-title">{{ __('ui.blade_exchange_transactions') }}</h5>
                    <div class="table-section-subtitle">
                        {{ __('reports.exchange_columns') }}
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="reportTable">
                    <thead>
                        <tr>
                            <th>{{ __('ui.blade_date_time') }}</th>
                            <th>{{ __('ui.blade_customer_name') }}</th>
                            <th>{{ __('ui.type') }}</th>
                            <th>{{ __('ui.blade_from') }}</th>
                            <th>{{ __('ui.rate') }}</th>
                            <th>{{ __('ui.blade_to') }}</th>
                        </tr>
                    </thead>
                    <tbody id="reportBody">
                        <tr>
                            <td colspan="6" class="text-center text-muted">{{ __('ui.blade_loading') }}</td>
                        </tr>
                    </tbody>
                    <tfoot id="reportFooter"></tfoot>
                </table>
            </div>
        </div>

        <div class="section-card table-card m-3">
            <div class="table-section-header">
                <div>
                    <h5 class="table-section-title">{{ __('ui.blade_currency_pair_summary') }}</h5>
                    <div class="table-section-subtitle">
                        {{ __('reports.pair_intro') }}
                    </div>
                </div>
            </div>

            <div id="currencyPairTotalsBox" class="pair-total-grid">
                <div class="empty-box">
                    {{ __('reports.loading_pairs') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    const dataUrl = "{{ route('admin.reports.customer-exchange-report.data') }}";
    const printUrl = "{{ route('admin.reports.customer-exchange-report.print') }}";

    function numberFormat(value, decimals = 2) {
        value = parseFloat(value || 0);

        return value.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function serializeForm() {
        return $('#filterForm').serialize();
    }

    function renderPairTotals(pairTotals) {
        pairTotals = pairTotals || [];

        if (!pairTotals.length) {
            $('#currencyPairTotalsBox').html(`
                <div class="empty-box">
                    {{ __('reports.no_currency_summary') }}
                </div>
            `);

            $('#reportFooter').html('');
            return;
        }

        let cardsHtml = '';
        let footerHtml = `
            <tr>
                <th colspan="3" class="text-end">{{ __('ui.blade_currency_pair_totals') }}</th>
                <th>{{ __('ui.blade_from_total') }}</th>
                <th>{{ __('ui.blade_avg_rate') }}</th>
                <th>{{ __('ui.blade_to_total') }}</th>
            </tr>
        `;

        pairTotals.forEach(item => {
            cardsHtml += `
                <div class="pair-total-card">
                    <div class="pair-total-header">
                        <span class="currency-pair-pill">${item.from_currency} → ${item.to_currency}</span>
                        <span class="pair-count">${item.records || 0} {{ __('reports.entries') }}</span>
                    </div>

                    <div class="pair-line">
                        <span class="pair-line-label">{{ __('ui.blade_from_total') }}</span>
                        <span class="pair-line-value">
                            ${numberFormat(item.from_total)} ${item.from_currency}
                        </span>
                    </div>

                    <div class="pair-line">
                        <span class="pair-line-label">{{ __('ui.blade_average_rate') }}</span>
                        <span class="pair-line-value">
                            ${numberFormat(item.average_rate, 6)}
                        </span>
                    </div>

                    <div class="pair-line">
                        <span class="pair-line-label">{{ __('ui.blade_to_total') }}</span>
                        <span class="pair-line-value">
                            ${numberFormat(item.to_total)} ${item.to_currency}
                        </span>
                    </div>
                </div>
            `;

            footerHtml += `
                <tr>
                    <td colspan="3" class="text-end">
                        <strong>${item.from_currency} → ${item.to_currency}</strong>
                    </td>
                    <td>
                        <strong>${numberFormat(item.from_total)}</strong>
                        <div class="small text-muted">${item.from_currency}</div>
                    </td>
                    <td>
                        <strong>${numberFormat(item.average_rate, 6)}</strong>
                    </td>
                    <td>
                        <strong>${numberFormat(item.to_total)}</strong>
                        <div class="small text-muted">${item.to_currency}</div>
                    </td>
                </tr>
            `;
        });

        $('#currencyPairTotalsBox').html(cardsHtml);
        $('#reportFooter').html(footerHtml);
    }

    function loadReport() {
        $('#reportBody').html(`<tr><td colspan="6" class="text-center text-muted">{{ __('ui.blade_loading') }}</td></tr>`);
        $('#reportFooter').html('');
        $('#currencyPairTotalsBox').html(`<div class="empty-box">{{ __('ui.blade_loading_currency_pair_totals') }}</div>`);

        $.ajax({
            url: dataUrl,
            data: serializeForm(),
            type: 'GET',
            success: function (response) {
                let rows = response.rows || [];
                let summary = response.summary || {};
                let pairTotals = response.currency_pair_totals || [];

                $('#totalRecords').text(summary.total_records || 0);
                $('#purchaseCount').text(summary.purchase_count || 0);
                $('#sellCount').text(summary.sell_count || 0);
                $('#cashCount').text(summary.cash_count || 0);
                $('#customerCount').text(summary.customer_count || 0);

                let profit = parseFloat(summary.total_profit || 0);

                $('#totalProfit')
                    .text(numberFormat(profit, 2))
                    .removeClass('amount-positive amount-negative amount-dark')
                    .addClass(profit >= 0 ? 'amount-positive' : 'amount-negative');

                renderPairTotals(pairTotals);

                if (!rows.length) {
                    $('#reportBody').html(`<tr><td colspan="6" class="text-center text-muted">{{ __('ui.blade_no_records_found') }}</td></tr>`);
                    return;
                }

                let html = '';

                rows.forEach(row => {
                    let typeBadge = row.type === 'purchase'
                        ? `<span class="badge-purchase">{{ __('ui.blade_purchase') }}</span>`
                        : `<span class="badge-sell">{{ __('ui.blade_sell') }}</span>`;

                    html += `
                        <tr>
                            <td>
                                <strong>${row.date_time}</strong>
                            </td>
                            <td>
                                <strong>${row.customer_name}</strong>
                                <div class="small text-muted">${row.customer_code || '-'}</div>
                            </td>
                            <td>${typeBadge}</td>
                            <td>
                                <strong>${numberFormat(row.from_amount)}</strong>
                                <div class="small text-muted">${row.from_currency}</div>
                            </td>
                            <td>
                                <strong>${numberFormat(row.rate, 6)}</strong>
                            </td>
                            <td>
                                <strong>${numberFormat(row.to_amount)}</strong>
                                <div class="small text-muted">${row.to_currency}</div>
                            </td>
                        </tr>
                    `;
                });

                $('#reportBody').html(html);
            },
            error: function () {
                $('#reportBody').html(`<tr><td colspan="6" class="text-center text-danger">{{ __('ui.blade_failed_to_load_report') }}</td></tr>`);
                $('#currencyPairTotalsBox').html(`<div class="empty-box text-danger">{{ __('ui.blade_failed_to_load_currency_summary') }}</div>`);
            }
        });
    }

    $('#filterForm').on('submit', function (e) {
        e.preventDefault();
        loadReport();
    });

    $('#resetBtn').on('click', function () {
        $('#filterForm')[0].reset();
        loadReport();
    });

    $('#printBtn').on('click', function () {
        window.open(printUrl + '?' + serializeForm(), '_blank');
    });

    $(document).ready(function () {
        loadReport();
    });
</script>
@endsection
