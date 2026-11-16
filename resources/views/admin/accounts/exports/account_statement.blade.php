<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ps', 'fa']) ? 'rtl' : 'ltr' }}">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('ui.account_statement') }}</title>

        <style>
            @font-face {
                font-family: 'Vazirmatn';
                src: url("{{ asset('fonts/webfonts/Vazirmatn[wght].woff2') }}") format('woff2');
                font-weight: 100 900;
            }
            html[dir="rtl"] body { font-family: 'Vazirmatn', sans-serif; }

            /* -------------------- RESET & ADVANCED PRINT -------------------- */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            @page {
                size: A4 portrait;
                margin: 14mm 12mm;
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
                max-width: 1000px;
                margin: 20px auto;
                background: white;
                box-shadow: 0 12px 35px rgba(0, 0, 0, 0.05);
                padding: 28px 32px 32px 32px;
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
                    padding: 0.1in 0.15in;
                    border-radius: 0;
                    max-width: 100%;
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
            }

            .col-acc {
                font-weight: 700;
                color: #0f3b52;
                font-size: 10.5px;
            }

            .col-desc {
                color: #4b6f87;
                font-style: normal;
                font-weight: 450;
                font-size: 9.8px;
                max-width: 220px;
                word-break: break-word;
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

            <!-- HEADER -->
            <table class="header-table">
                <tr>
                    <td>
                        <div class="company-name">{{ $setting->company_name }}</div>
                    </td>
                    <td class="report-title">
                        <div class="report-main-title">{{ __('ui.account_statement') }}</div>
                        <div style="font-size:9px;">{{ $account->name }} ({{ $account->code }})</div>
                    </td>
                </tr>
            </table>

            <!-- META -->
            <div class="meta-bar">
                <span>📅 {{ __('accounts.period') }}
                    {{ request('start_date') ?? __('accounts.start') }}
                    —
                    {{ request('end_date') ?? now()->format('Y-m-d') }}
                </span>

                <span>📞 {{ __('accounts.contact') }} {{ $account->contact ?? __('accounts.not_available') }}</span>

                <span>💱 {{ __('accounts.currency') }}
                    @if (request('currency_id'))
                        {{ optional($currencies->firstWhere('id', request('currency_id')))->code ?? __('accounts.not_available') }}
                    @else
                        {{ __('accounts.multi_currency') }}
                    @endif
                </span>
            </div>

            <!-- TRANSACTIONS TABLE -->
            <table class="journal-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('ui.date') }}</th>
                        <th>{{ __('ui.description') }}</th>
                        <th>{{ __('ui.blade_ccy') }}</th>
                        <th class="text-right">{{ __('ui.credit') }}</th>
                        <th class="text-right">{{ __('ui.debit') }}</th>
                        <th class="text-right">{{ __('ui.blade_running_balance') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        $transactionRowNumber = 1;
                    @endphp

                    @forelse ($transactions as $index => $t)
                        @php
                            $rowType = $t->row_type ?? 'transaction';

                            $isOpening = $rowType === 'opening';
                            $isClosing = $rowType === 'closing';
                            $isCurrent = $rowType === 'current';
                            $isTransaction = $rowType === 'transaction';

                            $isCr = $t->transaction_type == 'credit';
                            $isDr = $t->transaction_type == 'debit';

                            $runningBalance = (float) ($t->running_balance ?? 0);
                            $runningClass = $runningBalance < 0 ? 'text-danger' : '';

                            $currencyCode = $t->currency->code ?? '';
                        @endphp

                        @if ($isOpening)
                            <tr style="background:#f8fbfe; font-weight:700;">
                                <td></td>

                                <td>
                                    {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d-M-Y') : '-' }}
                                </td>

                                <td>{{ __('ui.blade_opening_balance') }}</td>

                                <td>{{ $currencyCode }}</td>

                                <td class="text-right">0.00</td>

                                <td class="text-right">0.00</td>

                                <td class="money balance-col {{ $runningClass }} text-right">
                                    {{ format_amount($runningBalance, 2) }}
                                    {{ $currencyCode }}
                                </td>
                            </tr>
                        @elseif ($isClosing)
                            <tr style="background:#eef4fa; font-weight:800;">
                                <td></td>

                                <td>
                                    {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d-M-Y') : '-' }}
                                </td>

                                <td>{{ __('ui.blade_closing_balance') }}</td>

                                <td>{{ $currencyCode }}</td>

                                <td class="text-right">0.00</td>

                                <td class="text-right">0.00</td>

                                <td class="money balance-col {{ $runningClass }} text-right">
                                    {{ format_amount($runningBalance, 2) }}
                                    {{ $currencyCode }}
                                </td>
                            </tr>
                        @elseif ($isCurrent)
                            <tr style="background:#fbfef9; font-weight:800;">
                                <td></td>

                                <td>{{ now()->format('d-M-Y') }}</td>

                                <td>{{ __('ui.blade_current_balance') }}</td>

                                <td>{{ $currencyCode }}</td>

                                <td class="text-right">0.00</td>

                                <td class="text-right">0.00</td>

                                <td class="money balance-col {{ $runningClass }} text-right">
                                    {{ format_amount($runningBalance, 2) }}
                                    {{ $currencyCode }}
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td>{{ $transactionRowNumber++ }}</td>

                                <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d-M-Y') }}</td>

                                <td>{{ $t->note ?? '-' }}</td>

                                <td>{{ $currencyCode }}</td>

                                <td class="text-right">
                                    {{ $isCr ? format_amount($t->amount, 2) : '0.00' }}
                                </td>

                                <td class="text-right">
                                    {{ $isDr ? format_amount($t->amount, 2) : '0.00' }}
                                </td>

                                <td class="money balance-col {{ $runningClass }} text-right">
                                    {{ format_amount($runningBalance, 2) }}
                                    {{ $currencyCode }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:20px;">
                                {{ __('accounts.no_transactions') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- SUMMARY -->
            <div class="summary-container">
                @foreach ($summariesByCurrency as $currency => $summary)
                    @php
                        $balance = $summary['closing_balance'] ?? (($summary['opening_balance'] ?? 0) + $summary['credit'] - $summary['debit']);
                        $currentBalance = $summary['current_balance'] ?? $balance;
                    @endphp

                    <table class="summary-table" style="margin-left:10px;">
                        <tr>
                            <td>{{ __('ui.currency') }}</td>
                            <td class="text-right">{{ $currency }}</td>
                        </tr>

                        <tr>
                            <td>{{ __('ui.blade_opening') }}</td>
                            <td class="text-right">{{ format_amount($summary['opening_balance'] ?? 0, 2) }}</td>
                        </tr>

                        <tr>
                            <td>{{ __('ui.credit') }}</td>
                            <td class="text-right">{{ format_amount($summary['credit'], 2) }}</td>
                        </tr>

                        <tr>
                            <td>{{ __('ui.debit') }}</td>
                            <td class="text-right">{{ format_amount($summary['debit'], 2) }}</td>
                        </tr>

                        <tr class="grand-total">
                            <td>{{ __('ui.blade_closing') }}</td>
                            <td class="text-right">{{ format_amount($balance, 2) }}</td>
                        </tr>

                        <tr class="grand-total">
                            <td>{{ __('ui.blade_current') }}</td>
                            <td class="text-right">{{ format_amount($currentBalance, 2) }}</td>
                        </tr>
                    </table>
                @endforeach
            </div>

            <div class="footer-note">
                {{ __('accounts.system_document') }}
            </div>

        </div>

        <script>
            window.onload = function() {
                setTimeout(() => {
                    window.print();
                }, 400);
            };
        </script>

    </body>

</html>
