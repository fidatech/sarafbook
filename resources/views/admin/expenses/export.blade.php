@php
    $pdfArabic = new \ArPHP\I18N\Arabic();
    $pdfLabel = static function ($text) use ($pdfArabic) {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text)
            ? $pdfArabic->utf8Glyphs($text, 1000, false) : $text;
    };
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ps', 'fa']) ? 'rtl' : 'ltr' }}">

    <head>
        <meta charset="UTF-8">
        <title>{{ $pdfLabel(__('ui.blade_expenses_report')) }}</title>

        <style>
            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 12px;
                color: #111827;
                margin: 0;
                padding: 0;
            }

            .container {
                padding: 22px;
            }

            .header {
                display: flex;
                justify-content: space-between;
                border-bottom: 2px solid #e5e7eb;
                padding-bottom: 12px;
                margin-bottom: 18px;
            }

            .header img {
                max-height: 50px;
            }

            .title {
                font-size: 18px;
                font-weight: bold;
                text-align: center;
                margin: 15px 0;
                text-transform: uppercase;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
            }

            th,
            td {
                border: 1px solid #e5e7eb;
                padding: 8px 6px;
                word-break: break-word;
            }

            th {
                background: #f3f4f6;
                font-weight: bold;
            }

            tr:nth-child(even) {
                background: #fafafa;
            }

            .totals-table {
                margin-top: 25px;
                font-size: 13px;
                border-collapse: collapse;
            }

            .totals-table td {
                border: 1px solid #d1d5db;
                padding: 6px 8px;
                font-weight: bold;
            }

            .footer {
                margin-top: 30px;
                padding-top: 10px;
                border-top: 2px solid #e5e7eb;
                font-size: 10px;
            }
        </style>
    </head>

    <body>
        <div class="container">

            {{-- Date --}}
            <div style="text-align:right;font-size:10px;color:#6b7280;">
                {{ $pdfLabel(__('expenses.generated_on')) }} {{ now()->format('Y-m-d H:i') }}
            </div>

            {{-- Header --}}
            <div class="header">
                <img src="{{ public_path('images/' . $setting->logo) }}">
                <div style="text-align:right;">
                    <div style="font-size:20px;font-weight:bold;color:#2563eb;">
                        {{ $setting->company_name }}
                    </div>
                    <div style="font-size:12px;">{{ $pdfLabel(__('ui.blade_expenses_report')) }}</div>
                </div>
            </div>

            <div class="title">{{ $pdfLabel(__('ui.blade_expenses_summary')) }}</div>

            <table>
                <thead>
                    <tr>
                        <th style="width:5%;">#</th>
                        <th style="width:15%;">{{ $pdfLabel(__('ui.date')) }}</th>
                        <th style="width:15%;">{{ $pdfLabel(__('ui.category')) }}</th>
                        <th>{{ $pdfLabel(__('ui.description')) }}</th>
                        <th style="width:20%;">{{ $pdfLabel(__('ui.amount')) }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($expenses as $i => $e)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $e->expense_date }}</td>
                            <td>{{ $e->expenseCategory->name ?? '—' }}</td>
                            <td>{{ $e->description ?: '—' }}</td>
                            <td>{{ format_amount($e->amount, 2) . ' ' . $e->currency->code }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- ========================= --}}
            {{-- TOTALS PER CURRENCY       --}}
            {{-- ========================= --}}
            @php
                $totalsByCurrency = $expenses->groupBy('currency.code')->map(function ($items) {
                    return $items->sum('amount');
                });
            @endphp

            <table class="totals-table" style="width: 100%; margin-top: 30px;">
                @foreach ($totalsByCurrency as $currency => $sum)
                    <tr>
                        <td style="width: 60%;">{{ $pdfLabel(__('expenses.total')) }} ({{ $currency }})</td>
                        <td style="text-align:right;">{{ format_amount($sum, 2) }} {{ $currency }}</td>
                    </tr>
                @endforeach
            </table>

            {{-- Footer --}}
            <div class="footer">
                <div><strong>{{ $pdfLabel(__('ui.blade_phone')) }}</strong> {{ $setting->contact }}</div>
                <div><strong>{{ $pdfLabel(__('ui.email')) }}</strong> {{ $setting->email }}</div>
                <div><strong>{{ $pdfLabel(__('ui.address')) }}</strong> {{ $setting->address }}</div>
            </div>

        </div>
    </body>

</html>
