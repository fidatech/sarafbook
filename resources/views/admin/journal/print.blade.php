<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ps', 'fa']) ? 'rtl' : 'ltr' }}">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
        <title>{{ __('journal.print_title') }}</title>
        <style>
            @font-face {
                font-family: 'Vazirmatn';
                src: url("{{ asset('fonts/webfonts/Vazirmatn[wght].woff2') }}") format('woff2');
                font-weight: 100 900;
            }
            html[dir="rtl"] body,
            html[dir="rtl"] .report-title,
            html[dir="rtl"] .meta-bar,
            html[dir="rtl"] .journal-table th,
            html[dir="rtl"] .footer-note {
                font-family: 'Vazirmatn', sans-serif;
            }

            /* -------------------- RESET & ADVANCED PRINT -------------------- */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            @page {
                size: A4 landscape;
                margin: 10mm 8mm;
            }

            body {
                background: #eef2f5;
                /* subtle page context for screen, pure white for print */
                font-family: 'Inter', 'Segoe UI', 'Roboto', system-ui, -apple-system, 'Helvetica Neue', sans-serif;
                color: #0a2540;
                font-size: 10.5px;
                line-height: 1.45;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* main report card style */
            .report-page {
                max-width: 1400px;
                margin: 20px auto;
                background: white;
                box-shadow: 0 12px 35px rgba(0, 0, 0, 0.05);
                padding: 24px 28px 28px 28px;
                border-radius: 20px;
                transition: all 0.2s;
            }

            @media print {
                body {
                    background: white;
                    margin: 0;
                    padding: 0;
                }

                .report-page {
                    box-shadow: none;
                    margin: 0 auto;
                    padding: 0;
                    border-radius: 0;
                    max-width: 100%;
                    width: 100%;
                }

                .meta-bar {
                    break-inside: avoid;
                }

                .journal-table tr,
                .journal-table td,
                .journal-table th {
                    break-inside: avoid;
                }
            }

            /* refined double lines with modern accent */
            .double-rule-top {
                border-top: 3px double #1f6392;
                padding-top: 18px;
            }

            .double-rule-bottom {
                border-bottom: 3px double #1f6392;
                padding-bottom: 14px;
            }

            /* HEADER SECTION — elegant, modern, aligned */
            .header-table {
                width: 100%;
                margin-bottom: 22px;
            }

            .company-name {
                font-size: 28px;
                font-weight: 700;
                letter-spacing: -0.3px;
                background: linear-gradient(135deg, #133e5c 0%, #1f6392 100%);
                background-clip: text;
                -webkit-background-clip: text;
                color: #133e5c;
                margin-bottom: 6px;
            }

            .company-sub {
                font-size: 9px;
                font-weight: 500;
                letter-spacing: 0.3px;
                color: #5b7f95;
                border-left: 2px solid #cbdde9;
                padding-left: 10px;
                margin-top: 4px;
            }

            .report-title {
                text-align: right;
                font-family: 'Inter', 'Segoe UI', sans-serif;
            }

            .report-main-title {
                font-size: 20px;
                font-weight: 700;
                color: #1e4a6e;
                letter-spacing: -0.2px;
                text-transform: uppercase;
                background: #f0f6fa;
                display: inline-block;
                padding: 4px 14px;
                border-radius: 40px;
                line-height: 1.3;
            }

            .report-id {
                font-size: 9px;
                font-weight: 500;
                color: #6c8eaa;
                margin-top: 8px;
                letter-spacing: 0.2px;
            }

            /* METADATA BAR — refined card style */
            .meta-bar {
                background: #f8fbfe;
                border: 1px solid #e2edf2;
                border-radius: 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                padding: 12px 20px;
                margin-bottom: 28px;
                font-family: 'Inter', monospace;
                font-size: 9.5px;
                font-weight: 600;
                color: #1a485f;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            }

            .meta-bar span {
                background: white;
                padding: 4px 12px;
                border-radius: 40px;
                letter-spacing: 0.2px;
            }

            /* MAIN JOURNAL TABLE — professional, clean, airy */
            .journal-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
                font-size: 10px;
                border-radius: 18px;
                overflow: hidden;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            }

            .journal-table th {
                background: #123e5a;
                color: white;
                font-weight: 600;
                font-size: 9px;
                text-transform: uppercase;
                letter-spacing: 0.7px;
                padding: 14px 8px;
                border: none;
                font-family: 'Inter', 'Segoe UI', sans-serif;
            }

            .journal-table td {
                padding: 12px 8px;
                border-bottom: 1px solid #e9f0f3;
                vertical-align: middle;
                transition: background 0.1s ease;
            }

            .journal-table tr:hover td {
                background-color: #fafdff;
            }

            /* Column style refinements */
            .col-date {
                font-weight: 500;
                color: #1c5a7a;
                font-family: 'JetBrains Mono', 'SF Mono', monospace;
                font-size: 10px;
                text-align: center;
            }

            .col-acc {
                font-weight: 700;
                color: #0f3b52;
                font-size: 10.5px;
                text-align: center;
            }

            .col-desc {
                color: #4b6f87;
                font-style: normal;
                font-weight: 450;
                font-size: 9.8px;
                max-width: 220px;
                word-break: break-word;
                text-align: center;
            }

            .col-cur {
                text-align: center;
                font-weight: 700;
                background: #f1f6fa;
                border-radius: 40px;
                font-size: 9px;
                letter-spacing: 0.3px;
                color: #22648b;
            }

            .text-right {
                text-align: right;
            }

            .money {
                font-family: 'JetBrains Mono', 'SF Mono', 'Courier New', monospace;
                font-weight: 600;
                font-size: 10.2px;
            }

            .balance-col {
                background: #fbfef9;
                border-left: 1px solid #dce9f0;
                font-weight: 600;
                color: #1e6f5c;
            }

            /* summary container - modern glassmorphism touch */
            .summary-container {
                display: flex;
                justify-content: flex-end;
                margin-top: 20px;
                margin-bottom: 20px;
            }

            .summary-table {
                width: 320px;
                border-collapse: collapse;
                background: #ffffff;
                border-radius: 20px;
                box-shadow: 0 8px 20px rgba(0, 20, 30, 0.05);
                overflow: hidden;
            }

            .summary-table td {
                padding: 12px 16px;
                border-bottom: 1px solid #eef3f7;
                font-size: 10px;
            }

            .total-label {
                font-weight: 600;
                color: #2c6280;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-size: 9px;
            }

            .total-value {
                text-align: right;
                font-family: 'JetBrains Mono', monospace;
                font-weight: 700;
                font-size: 12px;
                color: #1c4e6e;
            }

            .grand-total {
                background: #eef4fa;
                border-top: 2px solid #1f6392;
            }

            .grand-total td {
                font-weight: 800;
                font-size: 11px;
                color: #0e4b6e;
            }

            /* signatures refined */
            .sig-section {
                margin-top: 50px;
                display: flex;
                justify-content: space-between;
                gap: 30px;
                text-align: center;
            }

            .sig-item {
                flex: 1;
            }

            .sig-line {
                border-top: 1.5px solid #234e6b;
                margin-bottom: 8px;
                width: 80%;
                margin-left: auto;
                margin-right: auto;
            }

            .sig-text {
                font-family: 'Inter', sans-serif;
                font-size: 8px;
                font-weight: 600;
                text-transform: uppercase;
                color: #587e9c;
                letter-spacing: 0.6px;
            }

            /* footer watermark style */
            .footer-note {
                margin-top: 45px;
                border-top: 1px solid #e2edf2;
                padding-top: 14px;
                text-align: center;
                font-family: 'Inter', monospace;
                font-size: 7.5px;
                font-weight: 500;
                color: #97b7ce;
                letter-spacing: 0.4px;
            }

            /* index number */
            .row-index {
                color: #97b8cf;
                font-weight: 500;
                font-size: 9px;
                text-align: center;
            }

            /* responsiveness */
            @media (max-width: 700px) {
                .report-page {
                    padding: 16px;
                }

                .meta-bar {
                    flex-direction: column;
                    gap: 8px;
                    align-items: flex-start;
                }

                .journal-table th,
                .journal-table td {
                    padding: 8px 4px;
                }
            }
        </style>
    </head>

    <body>

        <div class="report-page">
            <table class="header-table double-rule-bottom">
                <tr>
                    <td style="width: 65%;">
                        <div class="company-name">{{ $setting->company_name ?? 'PRESTIGE FINANCIAL CORP' }}</div>
                        <div class="company-sub">{{ __('journal.division') }}</div>
                    </td>
                    <td class="report-title">
                        <div class="report-main-title">{{ __('ui.blade_journal_report') }}</div>
                        <div class="report-id">{{ __('journal.report_id') }} #{{ strtoupper(substr(md5(now()), 0, 8)) }}</div>
                    </td>
                </tr>
            </table>

            <div class="meta-bar">
                <span>📅 {{ __('journal.period') }} {{ $dateFrom ?? __('journal.initiation') }} — {{ $dateTo ?? now()->format('Y-m-d') }}</span>
                <span>💱 {{ __('journal.report_currency') }} {{ $currencyInfo ?? __('journal.multi_currency') }}</span>
                <span>⚡ {{ __('journal.generated') }} {{ now()->format('d M Y | H:i') }}</span>
            </div>

            <table class="journal-table">
                <thead>
                    <tr>
                        <th style="width: 36px;">#</th>
                        <th class="col-date">{{ __('ui.date') }}</th>
                        <th class="col-acc">{{ __('ui.blade_account_reference') }}</th>
                        <th class="col-desc">{{ __('ui.blade_transaction_details') }}</th>
                        <th class="col-cur">{{ __('ui.blade_ccy') }}</th>
                        <th class="text-right">{{ __('ui.credit') }} ({{ __('ui.cr') }})</th>
                        <th class="text-right">{{ __('ui.debit') }} ({{ __('ui.dr') }})</th>
                        <th class="balance-col text-right">{{ __('ui.blade_running_balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $index => $t)
                        @php
                            $isCr = ($t->transaction_type ?? $t->type) == 'credit';
                            $isDr = ($t->transaction_type ?? $t->type) == 'debit';
                            $amount = $t->amount ?? 0;
                        @endphp
                        <tr>
                            <td class="row-index">{{ $index + 1 }}</td>
                            <td class="col-date">{{ \Carbon\Carbon::parse($t->created_at)->format('d-M-Y') }}</td>
                            <td class="col-acc">{{ $t->account_name }}</td>
                            <td class="col-desc">{{ $t->note ?? __('journal.official_entry') }}</td>
                            <td class="col-cur">{{ $t->currency_code ?? 'USD' }}</td>
                            <td class="money text-right" style="color: #2a7f6e; font-weight: 600;">{{ $isCr ? format_amount($amount, 2) : '0.00' }}</td>
                            <td class="money text-right" style="color: #c0423a; font-weight: 600;">{{ $isDr ? format_amount($amount, 2) : '0.00' }}</td>
                            <td class="money balance-col text-right" style="font-weight: 600;">{{ format_amount($t->running_balance ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 48px; font-style: normal; background: #fafdff; color: #8aadc7; font-weight: 500;">
                                📄 {{ __('journal.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="summary-container">
                @foreach ($summary as $row)
                    <table class="summary-table" style="margin-left: 10px;">
                        <tr>
                            <td class="total-label">{{ __('ui.currency') }}</td>
                            <td class="total-value">{{ $row['currency'] }}</td>
                        </tr>
                        <tr>
                            <td class="total-label">{{ __('ui.blade_period_credit_total') }}</td>
                            <td class="total-value">{{ format_amount($row['credit'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="total-label">{{ __('ui.blade_period_debit_total') }}</td>
                            <td class="total-value">{{ format_amount($row['debit'], 2) }}</td>
                        </tr>
                        <tr class="grand-total">
                            <td class="total-label" style="font-weight: 800; color: #144d6b;">{{ __('ui.blade_net_ledger_change') }}</td>
                            <td class="total-value" style="color: #1c6e58; font-weight: 800;">{{ format_amount(abs($row['credit'] - $row['debit']), 2) }}</td>
                        </tr>
                    </table>
                @endforeach
            </div>

            <div class="footer-note">
                🔒 {{ __('journal.footer') }}
            </div>
        </div>

        <script>
            window.onload = function() {
                setTimeout(() => {
                    if (window.matchMedia) {
                        const isPrintMedia = window.matchMedia('print').matches;
                        if (!isPrintMedia) {
                            window.print();
                        } else {
                            window.print();
                        }
                    } else {
                        window.print();
                    }
                }, 450);
            };
        </script>

    </body>

</html>
