@extends('layouts.admin.base')

@section('title', __('reports.profit_loss_title'))

@section('css')
    <style>
        .pnl-wrapper {
            max-width: 1000px;
            margin: auto;
        }

        .pnl-report {
            background: #fff;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.06);
            margin-bottom: 24px;
            position: relative;
        }

        .pnl-header {
            text-align: center;
            margin-bottom: 10px;
        }

        .pnl-logo {
            max-height: 60px;
            margin-bottom: 10px;
        }

        .pnl-title {
            font-size: 22px;
            font-weight: 700;
        }

        .pnl-sub {
            font-size: 13px;
            color: #6c757d;
        }

        .pnl-meta {
            font-size: 12px;
            text-align: center;
            margin-top: 5px;
            color: #777;
        }

        .pnl-currency {
            text-align: center;
            font-weight: 700;
            margin: 15px 0;
        }

        .pnl-table {
            width: 100%;
            font-size: 14px;
            border-collapse: collapse;
        }

        .pnl-table td {
            padding: 7px 4px;
        }

        .pnl-section td {
            font-weight: bold;
            border-bottom: 2px solid #111;
            padding-top: 14px;
        }

        .pnl-total td {
            font-weight: bold;
            border-top: 2px solid #111;
        }

        .pnl-net td {
            font-size: 16px;
            font-weight: bold;
            border-top: 3px double #111;
        }

        .text-right {
            text-align: right;
        }

        .page-number {
            position: absolute;
            bottom: 10px;
            right: 20px;
            font-size: 12px;
            color: #777;
        }

        @media print {
            body * {
                visibility: hidden !important;
            }

            #printArea,
            #printArea * {
                visibility: visible !important;
            }

            #printArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .no-print {
                display: none !important;
            }

            .pnl-report {
                page-break-after: always;
                box-shadow: none !important;
            }

            .pnl-report:last-child {
                page-break-after: auto;
            }
        }
    </style>
@endsection

@section('content')

    <div class="card no-print rounded-4 mb-3 border-0 shadow">
        <div class="card-body">

            <div class="d-flex mb-2 gap-2">
                <button class="btn btn-outline-primary btn-sm" onclick="setThisMonth()">{{ __('ui.this_month') }}</button>
                <button class="btn btn-outline-secondary btn-sm" onclick="setLastMonth()">{{ __('ui.blade_last_month') }}</button>
                <button class="btn btn-outline-success btn-sm" onclick="setThisYear()">{{ __('ui.this_year') }}</button>
            </div>

            <div class="row g-2">
                <div class="col-md-3">
                    <input type="month" id="from_month" class="form-control">
                </div>

                <div class="col-md-3">
                    <input type="month" id="to_month" class="form-control">
                </div>

                <div class="col-md-3">
                    <select id="currency_id" class="form-select">
                        <option value="">{{ __('ui.all_currencies') }}</option>
                        @foreach ($currencies as $c)
                            <option value="{{ $c->id }}">{{ $c->code }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary w-100" id="filterBtn">{{ __('ui.blade_filter') }}</button>
                    <button class="btn btn-dark w-100" id="printBtn">{{ __('ui.print') }}</button>
                </div>
            </div>

        </div>
    </div>

    <div id="printArea" class="pnl-wrapper"></div>

@endsection

@section('js')
    <script>
        function formatNumber(val) {
            return Number(val || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function buildStatement(report, company, period, generatedAt, index, logo) {

            let incomeHtml = '';
            let expenseHtml = '';

            report.income.forEach(row => {
                incomeHtml += `
            <tr>
                <td>${row.name}</td>
                <td class="text-right">${formatNumber(row.amount)}</td>
            </tr>
        `;
            });

            report.expenses.forEach(row => {
                expenseHtml += `
            <tr>
                <td>${row.name}</td>
                <td class="text-right">${formatNumber(row.amount)}</td>
            </tr>
        `;
            });

            let netClass = report.net_profit >= 0 ? 'text-success' : 'text-danger';

            return `
        <div class="pnl-report">

            <div class="pnl-header">
                ${logo ? `<img src="/images/${logo}" class="pnl-logo">` : ''}
                <div class="pnl-title">${company}</div>
                <div class="pnl-sub">{{ __('ui.blade_overall_profit_loss') }}</div>
                <div class="pnl-sub">{{ __('ui.blade_accrual_basis') }}</div>
                <div class="pnl-meta">${period} | {{ __('reports.generated') }} ${generatedAt}</div>
            </div>

            <div class="pnl-currency">{{ __('reports.currency_label') }} ${report.currency}</div>

            <table class="pnl-table">
                <tbody>

                    <tr class="pnl-section">
                        <td colspan="2">{{ __('ui.blade_income') }}</td>
                    </tr>

                    ${incomeHtml}

                    <tr class="pnl-total">
                        <td>{{ __('ui.blade_total_income') }}</td>
                        <td class="text-right">${formatNumber(report.total_income)}</td>
                    </tr>

                    <tr class="pnl-section">
                        <td colspan="2">{{ __('ui.blade_expense') }}</td>
                    </tr>

                    ${expenseHtml}

                    <tr class="pnl-total">
                        <td>{{ __('ui.blade_total_expense') }}</td>
                        <td class="text-right">${formatNumber(report.total_expense)}</td>
                    </tr>

                    <tr class="pnl-net">
                        <td>{{ __('ui.blade_net_profit_loss') }}</td>
                        <td class="text-right ${netClass}">
                            ${formatNumber(report.net_profit)}
                        </td>
                    </tr>

                </tbody>
            </table>

            <div class="page-number">{{ __('reports.page') }} ${index + 1}</div>

        </div>
    `;
        }

        function loadPL() {

            $.get('{{ route('admin.reports.profit-loss.data') }}', {
                from_month: $('#from_month').val(),
                to_month: $('#to_month').val(),
                currency_id: $('#currency_id').val()
            }, function(res) {

                let html = '';

                if (res.reports && res.reports.length) {

                    res.reports.forEach((report, index) => {
                        html += buildStatement(
                            report,
                            res.company,
                            res.period,
                            res.generated_at,
                            index,
                            res.logo ?? null // pass logo from backend if available
                        );
                    });

                } else {

                    html = `
                <div class="pnl-report text-center text-muted">
                    {{ __('reports.no_data') }}
                </div>
            `;
                }

                $('#printArea').html(html);
            });
        }

        $('#filterBtn').on('click', loadPL);
        $('#printBtn').on('click', () => window.print());

        function setThisMonth() {
            let now = new Date();
            let m = now.toISOString().slice(0, 7);
            $('#from_month').val(m);
            $('#to_month').val(m);
            loadPL();
        }

        function setLastMonth() {
            let d = new Date();
            d.setMonth(d.getMonth() - 1);
            let m = d.toISOString().slice(0, 7);
            $('#from_month').val(m);
            $('#to_month').val(m);
            loadPL();
        }

        function setThisYear() {
            let y = new Date().getFullYear();
            $('#from_month').val(y + '-01');
            $('#to_month').val(y + '-12');
            loadPL();
        }

        loadPL();
    </script>
@endsection
