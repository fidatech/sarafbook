@php(\App\Models\AedReportEntry::refreshLiveBalances($entries))
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ps', 'fa']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('aed.title') }}</title>
    <style>
        body { font-family: {{ in_array(app()->getLocale(), ['ps', 'fa']) ? 'xbriyaz' : 'dejavusans' }}, sans-serif; color: #1f2937; font-size: 11px; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 22px; }
        .company-name { font-size: 20px; font-weight: bold; color: #2f2b43; margin-bottom: 4px; }
        .report-title { font-size: 16px; color: #2f2b43; margin-bottom: 4px; }
        .muted { color: #64748b; font-size: 10px; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 8px; margin-bottom: 18px; }
        .summary td { width: 50%; border: 1px solid #dbeafe; padding: 12px; background: #f8fafc; }
        .summary-label { font-size: 10px; color: #64748b; font-weight: bold; }
        .summary-value { font-size: 20px; font-weight: bold; color: #0284c7; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th { background: #f1f5f9; color: #475569; padding: 8px 6px; border-bottom: 1px solid #cbd5e1; border-top: 1px solid #e2e8f0; font-size: 10px; }
        .report-table td { padding: 8px 6px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        .report-table .total td { border-top: 2px solid #334155; font-weight: bold; background: #f8fafc; }
        .number { text-align: right; direction: ltr; white-space: nowrap; }
        .center { text-align: center; }
        .negative { color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company?->company_name }}</div>
        <div class="report-title">{{ __('aed.title') }}</div>
        <div class="muted">{{ __('aed.generated') }}: <span dir="ltr">{{ $generatedAt->format('Y-m-d H:i') }}</span></div>
    </div>
    <table class="summary"><tr>
        <td><div class="summary-label">{{ __('aed.total_aed') }}</div><div class="summary-value" dir="ltr">{{ number_format($entries->sum('aed_balance'), 2) }} AED</div></td>
        <td><div class="summary-label">{{ __('aed.total_usd') }}</div><div class="summary-value" dir="ltr">{{ number_format($entries->sum('converted_amount'), 2) }} USD</div></td>
    </tr></table>
    <table class="report-table">
        <thead><tr><th width="5%">#</th><th width="28%">{{ __('aed.customer') }}</th><th width="16%">{{ __('aed.date') }}</th><th width="19%">{{ __('aed.balance') }}</th><th width="13%">{{ __('aed.rate') }}</th><th width="19%">{{ __('aed.converted') }}</th></tr></thead>
        <tbody>
        @forelse($entries as $entry)
            <tr>
                <td class="center">{{ $loop->iteration }}</td>
                <td>{{ $entry->account->name }} ({{ $entry->account->code }})</td>
                <td class="center" dir="ltr">{{ now()->format('Y-m-d') }}</td>
                <td class="number {{ $entry->aed_balance < 0 ? 'negative' : '' }}">{{ number_format($entry->aed_balance, 2) }}</td>
                <td class="number">{{ $entry->calculated_exchange_rate === null ? '—' : rtrim(rtrim(number_format($entry->calculated_exchange_rate, 8, '.', ''), '0'), '.') }}</td>
                <td class="number">{{ number_format($entry->converted_amount, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="center">{{ __('aed.empty') }}</td></tr>
        @endforelse
            <tr class="total"><td colspan="3">{{ __('aed.grand_total') }}</td><td class="number">{{ number_format($entries->sum('aed_balance'), 2) }} AED</td><td></td><td class="number">{{ number_format($entries->sum('converted_amount'), 2) }} USD</td></tr>
        </tbody>
    </table>
</body>
</html>
