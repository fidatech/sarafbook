<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ps', 'fa']) ? 'rtl' : 'ltr' }}">

    <head>
        <meta charset="UTF-8">
        <title>{{ __('ui.blade_customers_balance_summary') }}</title>

        <style>
            :root {
                --qb-green: #2ca01c;
                --qb-text: #393a3d;
                --qb-border: #d4d7dc;
                --qb-muted: #6b7280;
                --qb-light: #f4f5f8;
                --qb-blue: #0077c5;
                --qb-red: #d52d1e;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                background: var(--qb-light);
                color: var(--qb-text);
                font-family: Arial, Helvetica, sans-serif;
                font-size: 13px;
            }

            .screen-toolbar {
                position: sticky;
                top: 0;
                z-index: 99;
                background: #ffffff;
                border-bottom: 1px solid var(--qb-border);
                padding: 12px 22px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .toolbar-title {
                font-size: 16px;
                font-weight: 700;
            }

            .toolbar-actions {
                display: flex;
                gap: 8px;
            }

            .btn {
                border: 1px solid var(--qb-border);
                background: #ffffff;
                color: var(--qb-text);
                padding: 8px 14px;
                border-radius: 4px;
                font-weight: 700;
                font-size: 13px;
                cursor: pointer;
                text-decoration: none;
            }

            .btn:hover {
                background: #f4f5f8;
            }

            .btn-primary {
                background: var(--qb-green);
                border-color: var(--qb-green);
                color: #ffffff;
            }

            .btn-primary:hover {
                background: #238816;
            }

            .page-wrap {
                max-width: 1180px;
                margin: 24px auto;
                padding: 0 16px;
            }

            .report-paper {
                background: #ffffff;
                border: 1px solid var(--qb-border);
                box-shadow: 0 1px 3px rgba(0, 0, 0, .10);
                padding: 42px 46px;
                min-height: 760px;
            }

            .report-header {
                text-align: center;
                margin-bottom: 42px;
            }

            .company-name {
                font-size: 22px;
                line-height: 1.3;
                font-weight: 800;
                color: #111827;
                margin-bottom: 4px;
            }

            .report-title {
                font-size: 20px;
                line-height: 1.3;
                font-weight: 400;
                color: #111827;
                margin-bottom: 4px;
            }

            .report-date {
                color: var(--qb-muted);
                font-size: 13px;
            }

            .table-scroll {
                overflow-x: auto;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }

            thead th {
                border-top: 1px solid var(--qb-text);
                border-bottom: 2px solid var(--qb-text);
                padding: 8px;
                background: #ffffff;
                font-weight: 800;
                color: #111827;
                white-space: nowrap;
            }

            tbody td {
                padding: 6px 8px;
                border-bottom: 1px solid #eceef1;
                height: 31px;
                vertical-align: middle;
            }

            tfoot th {
                padding: 10px 8px;
                border-top: 2px solid var(--qb-text);
                background: #ffffff;
                font-weight: 900;
                color: #111827;
            }

            .text-start {
                text-align: left;
            }

            .text-end {
                text-align: right;
            }

            .account-name {
                font-weight: 700;
                color: #111827;
            }

            .amount-positive {
                color: var(--qb-blue);
                font-weight: 700;
            }

            .amount-negative {
                color: var(--qb-red);
                font-weight: 700;
            }

            .amount-zero {
                color: var(--qb-muted);
                font-weight: 600;
            }

            .double-underline {
                border-bottom: 3px double var(--qb-text);
                padding-bottom: 2px;
                display: inline-block;
                min-width: 70px;
            }

            .report-footer {
                border-top: 1px solid var(--qb-border);
                margin-top: 44px;
                padding-top: 12px;
                font-size: 12px;
                color: var(--qb-muted);
                font-style: italic;
            }

            @page {
                size: A4 landscape;
                margin: 12mm;
            }

            @media print {
                body {
                    background: #ffffff;
                    font-size: 11px;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                .screen-toolbar {
                    display: none !important;
                }

                .page-wrap {
                    max-width: none;
                    margin: 0;
                    padding: 0;
                }

                .report-paper {
                    border: none;
                    box-shadow: none;
                    padding: 0;
                    min-height: auto;
                }

                .report-header {
                    margin-bottom: 28px;
                }

                .company-name {
                    font-size: 18px;
                }

                .report-title {
                    font-size: 16px;
                }

                table {
                    font-size: 10.5px;
                }

                thead {
                    display: table-header-group;
                }

                tfoot {
                    display: table-footer-group;
                }

                tr {
                    page-break-inside: avoid;
                }

                thead th,
                tbody td,
                tfoot th {
                    padding: 5px 6px;
                }

                .table-scroll {
                    overflow: visible;
                }
            }
        </style>
    </head>

    <body>

        <div class="screen-toolbar">
            <div class="toolbar-title">{{ __('ui.blade_customers_balance_summary_print_preview') }}</div>

            <div class="toolbar-actions">
                <button type="button" class="btn" onclick="window.close()">{{ __('ui.close') }}</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">{{ __('ui.blade_print_save_pdf') }}</button>
            </div>
        </div>

        <div class="page-wrap">
            <div class="report-paper">

                <div class="report-header">
                    <div class="company-name">
                        {{ $company->name ?? ($company->company_name ?? __('reports.company')) }}
                    </div>

                    <div class="report-title">
                        {{ __('reports.customers_summary') }}
                    </div>

                    <div class="report-date">
                        {{ __('reports.as_of') }} {{ now()->locale(app()->getLocale())->translatedFormat('F d, Y') }}
                    </div>

                    <div style="margin-top: 8px; color: #6b7280; font-size: 12px;">
                        <strong>{{ __('ui.account_type') }}</strong>
                        {{ $selectedSubCategory->name ?? __('reports.all_types') }}

                        &nbsp; | &nbsp;

                        <strong>{{ __('ui.account') }}</strong>
                        @if (!empty($selectedAccount))
                            {{ $selectedAccount->name }}{{ $selectedAccount->code ? ' (' . $selectedAccount->code . ')' : '' }}
                        @else
                            {{ __('ui.all_accounts') }}
                        @endif
                    </div>
                </div>

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-start">{{ __('ui.accounts') }}</th>

                                @foreach ($currencies as $currency)
                                    <th class="text-end">{{ $currency->code }}</th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($data as $row)
                                <tr>
                                    <td class="text-start">
                                        <span class="account-name">
                                            {{ $row->name }}{{ $row->code ? ' (' . $row->code . ')' : '' }}
                                        </span>
                                    </td>

                                    @foreach ($currencies as $currency)
                                        @php
                                            $column = strtolower($currency->code) . '_balance';
                                            $amount = (float) ($row->{$column} ?? 0);
                                            $formatted = number_format(abs($amount), 2);
                                        @endphp

                                        <td class="text-end">
                                            @if ($amount < 0)
                                                <span class="amount-negative">({{ $formatted }})</span>
                                            @elseif ($amount > 0)
                                                <span class="amount-positive">{{ $formatted }}</span>
                                            @else
                                                <span class="amount-zero">0.00</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 1 + $currencies->count() }}" style="text-align: center; padding: 24px;">
                                        {{ __('reports.no_customers') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        <tfoot>
                            <tr>
                                <th class="text-start">{{ __('ui.total') }}</th>

                                @foreach ($currencies as $currency)
                                    @php
                                        $column = strtolower($currency->code) . '_balance';
                                        $amount = (float) ($totals[$column]['amount'] ?? 0);
                                        $formatted = number_format(abs($amount), 2);
                                    @endphp

                                    <th class="text-end">
                                        <span class="double-underline">
                                            @if ($amount < 0)
                                                <span class="amount-negative">({{ $formatted }})</span>
                                            @elseif ($amount > 0)
                                                <span class="amount-positive">{{ $formatted }}</span>
                                            @else
                                                <span class="amount-zero">0.00</span>
                                            @endif
                                        </span>
                                    </th>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="report-footer">
                    {{ __('reports.accrual') }} {{ now()->locale(app()->getLocale())->translatedFormat('l, F d, Y h:i A') }}
                </div>

            </div>
        </div>

    </body>

</html>
