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
        background: linear-gradient(135deg, #0f172a, #334155);
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
        background: #f1f5f9;
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

    .currency-tabs {
        border-bottom: 1px solid #e5e7eb;
        gap: 8px;
    }

    .currency-tabs .nav-link {
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        color: #334155;
        border-radius: 999px;
        padding: 8px 14px;
        font-weight: 900;
        margin-bottom: 10px;
    }

    .currency-tabs .nav-link.active {
        background: #111827;
        color: #fff;
        border-color: #111827;
    }

    .tab-count {
        font-size: 11px;
        opacity: .75;
        margin-left: 4px;
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

    .table tfoot th,
    .table tfoot td {
        background: #f3f4f6;
        font-size: 12px;
        font-weight: 900;
        border-top: 2px solid #cbd5e1;
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

    .empty-box {
        border: 1px dashed #cbd5e1;
        border-radius: 16px;
        padding: 18px;
        color: #64748b;
        text-align: center;
        background: #f8fafc;
    }

    @media(max-width: 992px) {
        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media(max-width: 576px) {
        .summary-grid {
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
                <h2 class="text-white">{{ __('reports.currency_exchange_report') }}</h2>
                <p>{{ __('ui.blade_tab_wise_exchange_report_for_each_currency_directly_from_the_exchanges') }}</p>
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
                        <label class="form-label">{{ __('ui.blade_currency_tab') }}</label>
                        <select name="currency_id" class="form-select">
                            <option value="">{{ __('ui.blade_all_currency_tabs') }}</option>
                            @foreach($currencies as $currency)
                                <option value="{{ $currency->id }}">
                                    {{ $currency->code ?? $currency->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">{{ __('ui.type') }}</label>
                        <select name="type" class="form-select">
                            <option value="">{{ __('ui.all') }}</option>
                            <option value="purchase">{{ __('ui.blade_purchase') }}</option>
                            <option value="sell">{{ __('ui.blade_sell') }}</option>
                        </select>
                    </div>

                    <div class="col-md-3">
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
                    <h5 class="table-section-title">{{ __('ui.blade_currency_tabs') }}</h5>
                    <div class="table-section-subtitle">
                        {{ __('reports.currency_tabs_intro') }}
                    </div>
                </div>
            </div>

            <ul class="nav nav-pills currency-tabs flex-wrap" id="currencyTabs" role="tablist"></ul>

            <div class="tab-content pt-3" id="currencyTabContent">
                <div class="empty-box">
                    {{ __('reports.loading_tabs') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    const dataUrl = "{{ route('admin.reports.currency-exchange-report.data') }}";
    const printUrl = "{{ route('admin.reports.currency-exchange-report.print') }}";

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

    function renderTabs(tabs) {
        tabs = tabs || [];

        let activeTabs = tabs.filter(tab => parseInt(tab.summary.records || 0) > 0);

        if (!activeTabs.length) {
            $('#currencyTabs').html('');
            $('#currencyTabContent').html(`
                <div class="empty-box">
                    {{ __('reports.no_exchanges') }}
                </div>
            `);
            return;
        }

        let tabsHtml = '';
        let contentHtml = '';

        activeTabs.forEach((tab, index) => {
            let isActive = index === 0 ? 'active' : '';
            let tabId = `currency-tab-${tab.currency_id}`;

            tabsHtml += `
                <li class="nav-item" role="presentation">
                    <button class="nav-link ${isActive}"
                            id="${tabId}-button"
                            data-bs-toggle="pill"
                            data-bs-target="#${tabId}"
                            type="button"
                            role="tab">
                        ${tab.currency_code}
                        <span class="tab-count">(${tab.summary.records || 0})</span>
                    </button>
                </li>
            `;

            let bodyHtml = '';

            if (!tab.rows.length) {
                bodyHtml = `
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            {{ __('reports.no_records') }}
                        </td>
                    </tr>
                `;
            } else {
                tab.rows.forEach(row => {
                    let typeBadge = row.type === 'purchase'
                        ? `<span class="badge-purchase">{{ __('ui.blade_purchase') }}</span>`
                        : `<span class="badge-sell">{{ __('ui.blade_sell') }}</span>`;

                    bodyHtml += `
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
            }

            let footerHtml = '';

            if (tab.totals && tab.totals.length) {
                footerHtml += `
                    <tr>
                        <th colspan="3" class="text-end">{{ __('ui.blade_totals') }}</th>
                        <th>{{ __('ui.blade_from_total') }}</th>
                        <th>{{ __('ui.blade_avg_rate') }}</th>
                        <th>{{ __('ui.blade_to_total') }}</th>
                    </tr>
                `;

                tab.totals.forEach(total => {
                    footerHtml += `
                        <tr>
                            <td colspan="3" class="text-end">
                                <strong>${total.from_currency} → ${total.to_currency}</strong>
                                <div class="small text-muted">${total.records || 0} {{ __('reports.entries') }}</div>
                            </td>

                            <td>
                                <strong>${numberFormat(total.from_total)}</strong>
                                <div class="small text-muted">${total.from_currency}</div>
                            </td>

                            <td>
                                <strong>${numberFormat(total.average_rate, 6)}</strong>
                            </td>

                            <td>
                                <strong>${numberFormat(total.to_total)}</strong>
                                <div class="small text-muted">${total.to_currency}</div>
                            </td>
                        </tr>
                    `;
                });
            }

            let profit = parseFloat(tab.summary.profit || 0);

            contentHtml += `
                <div class="tab-pane fade show ${isActive}" id="${tabId}" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <h5 class="mb-1">${tab.currency_code} {{ __('reports.exchange_details') }}</h5>
                            <div class="small text-muted">
                                {{ __('reports.records') }} ${tab.summary.records || 0}
                                |
                                {{ __('reports.purchase') }} ${tab.summary.purchase_count || 0}
                                |
                                {{ __('reports.sell') }} ${tab.summary.sell_count || 0}
                            </div>
                        </div>

                        <div class="${profit >= 0 ? 'amount-positive' : 'amount-negative'}">
                            {{ __('reports.profit') }} ${numberFormat(profit, 2)}
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.blade_date_time') }}</th>
                                    <th>{{ __('ui.blade_customer_name') }}</th>
                                    <th>{{ __('ui.type') }}</th>
                                    <th>${tab.currency_code}</th>
                                    <th>{{ __('ui.rate') }}</th>
                                    <th>{{ __('ui.blade_to_currency') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                ${bodyHtml}
                            </tbody>

                            <tfoot>
                                ${footerHtml}
                            </tfoot>
                        </table>
                    </div>
                </div>
            `;
        });

        $('#currencyTabs').html(tabsHtml);
        $('#currencyTabContent').html(contentHtml);
    }

    function loadReport() {
        $('#currencyTabs').html('');
        $('#currencyTabContent').html(`<div class="empty-box">{{ __('ui.blade_loading_currency_tabs') }}</div>`);

        $.ajax({
            url: dataUrl,
            data: serializeForm(),
            type: 'GET',
            success: function (response) {
                let tabs = response.tabs || [];
                let summary = response.summary || {};

                $('#activeTabs').text(summary.active_tabs || 0);
                $('#totalRecords').text(summary.total_records || 0);
                $('#purchaseCount').text(summary.purchase_count || 0);
                $('#sellCount').text(summary.sell_count || 0);

                let profit = parseFloat(summary.total_profit || 0);

                $('#totalProfit')
                    .text(numberFormat(profit, 2))
                    .removeClass('amount-positive amount-negative amount-dark')
                    .addClass(profit >= 0 ? 'amount-positive' : 'amount-negative');

                renderTabs(tabs);
            },
            error: function () {
                $('#currencyTabs').html('');
                $('#currencyTabContent').html(`
                    <div class="empty-box text-danger">
                        {{ __('reports.exchange_failed') }}
                    </div>
                `);
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
