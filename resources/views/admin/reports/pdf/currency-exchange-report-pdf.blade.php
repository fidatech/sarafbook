<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ps', 'fa']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.currency_exchange_report') }}</title>

    <style>
        body {
            font-family: {{ in_array(app()->getLocale(), ['ps', 'fa']) ? 'xbriyaz' : 'dejavusans' }}, sans-serif;
            font-size: 9px;
            color: #111827;
            background: #ffffff;
        }

        .header {
            background: #111827;
            color: #ffffff;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }

        .subtitle {
            color: #d1d5db;
            margin-top: 4px;
            font-size: 9px;
        }

        .filters {
            margin-bottom: 10px;
            padding: 8px 10px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 9px;
            color: #374151;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .summary-table td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            background: #f9fafb;
        }

        .summary-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: bold;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            margin-top: 3px;
            color: #111827;
        }

        .currency-title {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            font-size: 13px;
            font-weight: bold;
            margin: 14px 0 6px;
            border-radius: 6px;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.report th {
            background: #111827;
            color: #ffffff;
            padding: 6px;
            border: 1px solid #111827;
            font-size: 8px;
            text-align: left;
        }

        table.report td {
            padding: 5px;
            border: 1px solid #d1d5db;
            font-size: 8px;
            vertical-align: top;
        }

        table.report tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        table.report tfoot td,
        table.report tfoot th {
            background: #f3f4f6;
            font-weight: bold;
            border-top: 2px solid #cbd5e1;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #6b7280;
            font-size: 7px;
        }

        .positive {
            color: #15803d;
            font-weight: bold;
        }

        .negative {
            color: #b91c1c;
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
        table.report tfoot th { color: #111827; }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">{{ __('reports.currency_exchange_report') }}</div>
        <div class="subtitle">
            {{ __('reports.generated') }} {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    <div class="filters">
        <strong>{{ __('ui.filters') }}</strong>
        {{ __('reports.from_label') }} {{ $filters['date_from'] ?: __('ui.all') }},
        {{ __('reports.to_label') }} {{ $filters['date_to'] ?: __('ui.all') }},
        {{ __('reports.currency_tab') }} {{ $filters['currency_id'] ?: __('ui.all') }},
        {{ __('reports.type_label') }} {{ (['purchase' => __('ui.blade_purchase'), 'sell' => __('ui.blade_sell')][$filters['type']] ?? $filters['type']) ?: __('ui.all') }},
        {{ __('reports.mode_label') }} {{ (['cash' => __('ui.blade_cash'), 'customer' => __('ui.customer')][$filters['mode']] ?? $filters['mode']) ?: __('ui.all') }}
    </div>

    @php
        $activeTabs = collect($tabs)->filter(fn($tab) => ($tab['summary']['records'] ?? 0) > 0)->values();
    @endphp

    @forelse($activeTabs as $tabIndex => $tab)
        <div class="currency-title">
            {{ $tab['currency_code'] }} {{ __('reports.exchange_details') }}
            —
            {{ __('reports.records') }} {{ number_format($tab['summary']['records']) }},
            {{ __('reports.purchase') }} {{ number_format($tab['summary']['purchase_count']) }},
            {{ __('reports.sell') }} {{ number_format($tab['summary']['sell_count']) }},
            {{ __('reports.profit') }}
            {{ number_format($tab['summary']['profit'], 2) }}
        </div>

        <table class="report">
            <thead>
                <tr>
                    <th style="width: 16%;">{{ __('ui.blade_date_time') }}</th>
                    <th style="width: 25%;">{{ __('ui.blade_customer_name') }}</th>
                    <th style="width: 10%;">{{ __('ui.type') }}</th>
                    <th class="text-right" style="width: 16%;">{{ $tab['currency_code'] }}</th>
                    <th class="text-right" style="width: 13%;">{{ __('ui.rate') }}</th>
                    <th class="text-right" style="width: 20%;">{{ __('ui.blade_to_currency') }}</th>
                </tr>
            </thead>

            <tbody>
                @forelse($tab['rows'] as $row)
                    <tr>
                        <td>{{ $row['date_time'] }}</td>

                        <td>
                            <strong>{{ $row['customer_name'] }}</strong>
                            @if(!empty($row['customer_code']) && $row['customer_code'] !== '-')
                                <br>
                                <span class="muted">{{ $row['customer_code'] }}</span>
                            @endif
                        </td>

                        <td class="{{ $row['type'] === 'purchase' ? 'positive' : 'negative' }}">
                            {{ ['purchase' => __('ui.blade_purchase'), 'sell' => __('ui.blade_sell')][$row['type']] ?? ucfirst($row['type']) }}
                        </td>

                        <td class="text-right">
                            <strong>{{ number_format($row['from_amount'], 2) }}</strong>
                            <br>
                            <span class="muted">{{ $row['from_currency'] }}</span>
                        </td>

                        <td class="text-right">
                            <strong>{{ number_format($row['rate'], 6) }}</strong>
                        </td>

                        <td class="text-right">
                            <strong>{{ number_format($row['to_amount'], 2) }}</strong>
                            <br>
                            <span class="muted">{{ $row['to_currency'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">{{ __('ui.blade_no_records_found') }}</td>
                    </tr>
                @endforelse
            </tbody>

            @if(!empty($tab['totals']))
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">{{ __('ui.blade_totals') }}</th>
                        <th class="text-right">{{ __('ui.blade_from_total') }}</th>
                        <th class="text-right">{{ __('ui.blade_avg_rate') }}</th>
                        <th class="text-right">{{ __('ui.blade_to_total') }}</th>
                    </tr>

                    @foreach($tab['totals'] as $total)
                        <tr>
                            <td colspan="3" class="text-right">
                                <strong>{{ $total['from_currency'] }} <span style="font-family: dejavusans;">→</span> {{ $total['to_currency'] }}</strong>
                                <br>
                                <span class="muted">{{ number_format($total['records']) }} {{ __('reports.entries') }}</span>
                            </td>

                            <td class="text-right">
                                <strong>{{ number_format($total['from_total'], 2) }}</strong>
                                <br>
                                <span class="muted">{{ $total['from_currency'] }}</span>
                            </td>

                            <td class="text-right">
                                <strong>{{ number_format($total['average_rate'], 6) }}</strong>
                            </td>

                            <td class="text-right">
                                <strong>{{ number_format($total['to_total'], 2) }}</strong>
                                <br>
                                <span class="muted">{{ $total['to_currency'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tfoot>
            @endif
        </table>

        @if($tabIndex + 1 < $activeTabs->count())
            <div class="page-break"></div>
        @endif
    @empty
        <table class="report">
            <tbody>
                <tr>
                    <td class="text-center">{{ __('ui.blade_no_exchange_records_found') }}</td>
                </tr>
            </tbody>
        </table>
    @endforelse
</body>
</html>
