<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ps', 'fa']) ? 'rtl' : 'ltr' }}">

    <head>
        <meta charset="utf-8">
        <title>{{ __('safe.title') }}</title>

        <style>
            @font-face {
                font-family: 'Vazirmatn';
                src: url("{{ asset('fonts/webfonts/Vazirmatn[wght].woff2') }}") format('woff2');
                font-weight: 100 900;
            }

            html[dir="rtl"] body {
                font-family: 'Vazirmatn', DejaVu Sans, sans-serif;
            }

            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 11px;
                margin: 0;
                padding: 0;
                color: #111;
            }

            .container {
                padding: 20px;
            }

            /* HEADER */
            .header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
                margin-bottom: 15px;
            }

            .logo img {
                height: 50px;
            }

            .company {
                text-align: right;
            }

            .company h2 {
                margin: 0;
                font-size: 16px;
            }

            .company p {
                margin: 2px 0;
                font-size: 11px;
            }

            /* TITLE */
            .title {
                text-align: center;
                font-weight: bold;
                font-size: 14px;
                margin-bottom: 10px;
            }

            /* TABLE */
            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 4px;
            }

            th {
                background: #f3f4f6;
                font-size: 10px;
            }

            td {
                font-size: 10px;
            }

            .text-right {
                text-align: right;
            }

            .credit {
                color: green;
                font-weight: bold;
            }

            .debit {
                color: red;
                font-weight: bold;
            }

            .balance {
                font-weight: bold;
            }

            .opening {
                background: #f9fafb;
                font-weight: bold;
            }

            .closing {
                background: #fff7ed;
                font-weight: bold;
            }

            /* FOOTER */
            .footer {
                position: fixed;
                bottom: 10px;
                left: 20px;
                right: 20px;
                font-size: 10px;
                display: flex;
                justify-content: space-between;
                border-top: 1px solid #ddd;
                padding-top: 5px;
            }

            @media print {
                @page {
                    size: A4 landscape;
                    margin: 10mm;
                }

                body {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                table {
                    font-size: 9px;
                }

                th,
                td {
                    padding: 3px;
                }
            }
        </style>
    </head>

    <body>

        <div class="container">

            <!-- HEADER -->
            <div class="header">
                <div class="logo">
                    @if ($settings['logo'])
                        <img src="{{ $settings['logo'] }}">
                    @endif
                </div>

                <div class="company">
                    <h2>{{ $settings['company_name'] }}</h2>
                    <p>{{ $settings['address'] }}</p>
                    <p>{{ $settings['phone'] }}</p>
                </div>
            </div>

            <!-- TITLE -->
            <div class="title">
                {{ __('safe.report_title') }}
            </div>

            <!-- TABLE -->
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">#</th>
                        <th rowspan="2">{{ __('ui.date') }}</th>
                        <th rowspan="2">{{ __('ui.account') }}</th>
                        <th rowspan="2">{{ __('ui.note') }}</th>
                        <th rowspan="2">{{ __('ui.type') }}</th>

                        @foreach ($data['currencies'] as $c)
                            <th colspan="2">{{ $c['code'] }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($data['currencies'] as $c)
                            <th>{{ __('ui.amount') }}</th>
                            <th>{{ __('ui.balances') }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>

                    <!-- OPENING -->
                    <tr class="opening">
                        <td colspan="4">{{ __('ui.blade_opening_balance') }}</td>

                        @foreach ($data['currencies'] as $c)
                            <td></td>
                            <td class="text-right">
                                {{ format_amount($data['opening_balances'][$c['id']] ?? 0, 2) }}
                            </td>
                        @endforeach
                    </tr>

                    <!-- ROWS -->
                    @foreach ($data['rows'] as $row)
                        <tr>
                            <td>{{ $row['sr'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td>
                                {{ $row['account_code'] ?? '-' }}
                                -
                                {{ $row['account_name'] ?? '-' }}
                            </td>
                            <td>{{ $row['note'] }}</td>
                            <td>{{ ['credit' => __('ui.credit'), 'debit' => __('ui.debit')][$row['type']] ?? ucfirst($row['type']) }}</td>

                            @foreach ($data['currencies'] as $c)
                                @php
                                    $d = $row['data'][$c['code']];
                                @endphp

                                <td class="{{ $d['amount'] > 0 ? 'credit' : ($d['amount'] < 0 ? 'debit' : '') }} text-right">
                                    {{ $d['amount'] !== null ? format_amount($d['amount'], 2) : '' }}
                                </td>

                                <td class="balance text-right">
                                    {{ $d['balance'] !== null ? format_amount($d['balance'], 2) : '' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach

                    <!-- CLOSING -->
                    <tr class="closing">
                        <td colspan="4">{{ __('ui.blade_final_balance') }}</td>

                        @foreach ($data['currencies'] as $c)
                            <td></td>
                            <td class="text-right">
                                {{ format_amount($data['closing_balances'][$c['id']] ?? 0, 2) }}
                            </td>
                        @endforeach
                    </tr>

                </tbody>
            </table>

        </div>

        <!-- FOOTER -->
        <div class="footer">
            <div>
                {{ __('safe.printed_at') }} {{ now()->format('Y-m-d H:i') }}
            </div>
            <div>
                {{ __('safe.generated_by', ['company' => $settings['company_name']]) }}
            </div>
        </div>

        <script>
            window.onload = function() {
                window.print();
            };
        </script>

    </body>

</html>
