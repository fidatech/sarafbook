<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ps', 'fa']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('reports.balance_title') }}</title>

    <style>
        body {
            font-family: {{ in_array(app()->getLocale(), ['ps', 'fa']) ? 'xbriyaz' : 'dejavusans' }}, sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 22px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2f2b43;
            margin-bottom: 4px;
        }

        .report-title {
            font-size: 16px;
            color: #2f2b43;
            margin-bottom: 4px;
        }

        .report-date {
            font-size: 11px;
            color: #64748b;
        }

        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin-bottom: 18px;
        }

        .summary-card {
            border: 1px solid #dbeafe;
            border-radius: 10px;
            padding: 12px;
            background: #f8fafc;
            vertical-align: top;
        }

        .summary-card.main {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .summary-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .summary-value {
            font-size: 22px;
            font-weight: bold;
            color: #0284c7;
        }

        .summary-value-sm {
            font-size: 15px;
            font-weight: bold;
        }

        .green {
            color: #16a34a;
        }

        .red {
            color: #dc2626;
        }

        .blue {
            color: #0284c7;
        }

        .muted {
            color: #64748b;
        }

        .small {
            font-size: 9px;
        }

        .real-list {
            margin-top: 4px;
        }

        .real-row {
            width: 100%;
            border-bottom: 1px dotted #e2e8f0;
            padding: 2px 0;
        }

        .real-code {
            font-weight: bold;
            color: #64748b;
        }

        .real-amount {
            float: right;
            font-weight: bold;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 18px 0 8px;
            color: #334155;
            text-transform: uppercase;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        table.report-table th {
            background: #f1f5f9;
            color: #475569;
            font-size: 9px;
            text-transform: uppercase;
            padding: 8px 6px;
            border-bottom: 1px solid #cbd5e1;
            border-top: 1px solid #e2e8f0;
        }

        table.report-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }

        table.report-table tfoot th {
            border-top: 2px solid #334155;
            background: #fff;
            font-size: 10px;
            padding: 9px 6px;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .currency-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            background: #eef2ff;
            color: #3730a3;
            font-weight: bold;
        }

        .rate-pill {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            background: #f1f5f9;
            font-weight: bold;
        }

        .note {
            margin-top: 22px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 9px;
            color: #64748b;
            font-style: italic;
        }

        .footer {
            margin-top: 12px;
            font-size: 9px;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>

<body>

<div class="header">
    <div class="company-name">
        {{ $company->name ?? $company->company_name ?? __('reports.company') }}
    </div>

    <div class="report-title">
        {{ __('reports.balance_valuation') }}
    </div>

    <div class="report-date">
        {{ __('reports.as_of') }} {{ $date->format('Y-m-d') }}
    </div>
</div>

<table class="summary-table">
    <tr>
        <td class="summary-card main" width="40%">
            <div class="summary-label">{{ __('ui.blade_estimated_final_balance') }}</div>
            <div class="summary-value">
                {{ number_format($finalTotal, 2) }}
            </div>
            <div class="small muted">
                {{ __('reports.converted_to') }} <strong>{{ $targetCurrency->code }}</strong>
            </div>
        </td>

        <td class="summary-card" width="20%">
            <div class="summary-label">{{ __('ui.total_credit') }}</div>

            <div class="small muted">{{ __('ui.blade_real_amounts') }}</div>
            <div class="real-list">
                @forelse($creditRealByCurrency as $code => $amount)
                    <div class="real-row">
                        <span class="real-code">{{ $code }}</span>
                        <span class="real-amount green">{{ number_format($amount, 2) }}</span>
                    </div>
                @empty
                    <span class="muted">0.00</span>
                @endforelse
            </div>

            <div class="small muted" style="margin-top: 6px;">{{ __('ui.blade_converted') }}</div>
            <div class="summary-value-sm green">
                {{ number_format($totalCreditConverted, 2) }} {{ $targetCurrency->code }}
            </div>
        </td>

        <td class="summary-card" width="20%">
            <div class="summary-label">{{ __('ui.total_debit') }}</div>

            <div class="small muted">{{ __('ui.blade_real_amounts') }}</div>
            <div class="real-list">
                @forelse($debitRealByCurrency as $code => $amount)
                    <div class="real-row">
                        <span class="real-code">{{ $code }}</span>
                        <span class="real-amount red">{{ number_format($amount, 2) }}</span>
                    </div>
                @empty
                    <span class="muted">0.00</span>
                @endforelse
            </div>

            <div class="small muted" style="margin-top: 6px;">{{ __('ui.blade_converted') }}</div>
            <div class="summary-value-sm red">
                {{ number_format($totalDebitConverted, 2) }} {{ $targetCurrency->code }}
            </div>
        </td>

        <td class="summary-card" width="20%">
            <div class="summary-label">{{ __('ui.blade_active_currencies') }}</div>
            <div class="summary-value-sm blue">
                {{ $activeCurrencies }}
            </div>
            <div class="small muted">
                {{ __('reports.non_zero') }}
            </div>
        </td>
    </tr>
</table>

<div class="section-title">{{ __('ui.blade_currency_balance_details') }}</div>

<table class="report-table">
    <thead>
    <tr>
        <th class="text-left">{{ __('ui.currency') }}</th>
        <th class="text-right">{{ __('ui.credit') }}</th>
        <th class="text-right">{{ __('ui.debit') }}</th>
        <th class="text-right">{{ __('ui.blade_real_balance') }}</th>
        <th class="text-center">{{ __('ui.blade_rate_used') }}</th>
        <th class="text-right">{{ __('ui.blade_converted_credit') }}</th>
        <th class="text-right">{{ __('ui.blade_converted_debit') }}</th>
        <th class="text-right">{{ __('ui.blade_converted_balance') }}</th>
    </tr>
    </thead>

    <tbody>
    @forelse($rows as $row)
        <tr>
            <td class="text-left">
                <span class="currency-badge">{{ $row['currency_code'] }}</span>
            </td>

            <td class="text-right">
                {{ number_format($row['total_credit'], 2) }}
            </td>

            <td class="text-right">
                {{ number_format($row['total_debit'], 2) }}
            </td>

            <td class="text-right {{ $row['balance'] < 0 ? 'red' : 'blue' }}">
                {{ $row['balance'] < 0 ? '(' . number_format(abs($row['balance']), 2) . ')' : number_format($row['balance'], 2) }}
            </td>

            <td class="text-center">
                <span class="rate-pill">
                    @if($row['operator'] === 'multiply')
                        ×
                    @elseif($row['operator'] === 'divide')
                        ÷
                    @elseif($row['operator'] === 'same')
                        =
                    @else
                        ?
                    @endif

                    {{ number_format($row['rate'], 6) }}
                </span>
            </td>

            <td class="text-right green">
                {{ number_format($row['converted_credit'], 2) }}
            </td>

            <td class="text-right red">
                {{ number_format($row['converted_debit'], 2) }}
            </td>

            <td class="text-right {{ $row['converted_amount'] < 0 ? 'red' : 'blue' }}">
                {{ $row['converted_amount'] < 0 ? '(' . number_format(abs($row['converted_amount']), 2) . ')' : number_format($row['converted_amount'], 2) }}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="text-center muted">
                {{ __('reports.no_balance') }}
            </td>
        </tr>
    @endforelse
    </tbody>

    <tfoot>
    <tr>
        <th colspan="5" class="text-right">{{ __('reports.totals_in') }} {{ $targetCurrency->code }}</th>
        <th class="text-right green">{{ number_format($totalCreditConverted, 2) }}</th>
        <th class="text-right red">{{ number_format($totalDebitConverted, 2) }}</th>
        <th class="text-right blue">{{ number_format($finalTotal, 2) }}</th>
    </tr>
    </tfoot>
</table>

<div class="note">
    {{ __('reports.valuation_note') }}
</div>

<div class="footer">
    {{ __('reports.generated_at') }} {{ now()->format('Y-m-d H:i') }}
</div>

</body>
</html>
