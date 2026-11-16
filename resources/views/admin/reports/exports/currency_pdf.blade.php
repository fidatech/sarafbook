@php
    $pdfArabic = new \ArPHP\I18N\Arabic();
    $pdfLabel = static function ($text) use ($pdfArabic) {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text)
            ? $pdfArabic->utf8Glyphs($text, 1000, false, true) : $text;
    };
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ps', 'fa']) ? 'rtl' : 'ltr' }}">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>{{ $pdfLabel(__('reports.currency_title')) }} - {{ $currency->code }}</title>

        <style>
            body {
                font-family: "DejaVu Sans", sans-serif;
                font-size: 11pt;
                direction: rtl;
                text-align: right;
                color: #334155;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th {
                background: #f8fafc;
                color: #64748b;
                font-size: 9pt;
                padding: 10px;
                border-bottom: 1px solid #e2e8f0;
            }

            td {
                padding: 10px;
                border-bottom: 1px solid #f1f5f9;
            }

            .col-index {
                width: 30px;
                text-align: center;
            }

            .col-amount {
                width: 140px;
                text-align: left;
                direction: ltr;
                font-weight: bold;
            }

            .header-table {
                border-bottom: 2px solid #e2e8f0;
                margin-bottom: 30px;
            }

            .title-main {
                font-size: 18pt;
                font-weight: bold;
            }

            .currency-badge {
                font-size: 12pt;
                color: #4f46e5;
                font-weight: bold;
            }

            .section-title {
                font-size: 13pt;
                font-weight: bold;
                padding: 8px 0;
            }

            .credit-title {
                color: #4f46e5;
                border-right: 4px solid #4f46e5;
                padding-right: 8px;
            }

            .debit-title {
                color: #e11d48;
                border-right: 4px solid #e11d48;
                padding-right: 8px;
            }

            .totals-row td {
                font-weight: bold;
                background: #fcfcfd;
            }

            .balance-box {
                margin-top: 35px;
                padding: 20px;
                background: #f8fafc;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                text-align: center;
            }

            .balance-value {
                font-size: 16pt;
                font-weight: bold;
                direction: ltr;
            }

            .footer {
                position: fixed;
                bottom: 0;
                width: 100%;
                font-size: 9pt;
                color: #94a3b8;
                border-top: 1px solid #f1f5f9;
                padding-top: 10px;
                text-align: left;
            }
        </style>
    </head>

    <body>

        <!-- HEADER -->
        <table class="header-table">
            <tr>

                <td style="border:none">

                    <table style="border:none">
                        <tr>
                            <td style="border:none" class="title-main">
                                <span dir="rtl">{{ $pdfLabel($labels['report_title']) }}</span>
                                
                            </td>
                        </tr>

                        <tr>
                            <td style="border:none" class="currency-badge">
                                <span dir="rtl">{{ $pdfLabel($labels['currency']) }}:</span>
                                <span dir="ltr">{{ $currency->code }}</span>
                            </td>
                        </tr>
                    </table>

                </td>

                <td style="border:none;text-align:left">
                    <img src="{{ public_path('logo.png') }}" style="height:60px">
                </td>

            </tr>
        </table>

        <!-- CREDIT TITLE -->
        <table>
            <tr>
                <td class="section-title credit-title">
                    <span dir="rtl">{{ $pdfLabel($labels['credits']) }}</span>
                    
                </td>
            </tr>
        </table>

        <!-- CREDIT TABLE -->
        <table>

            <thead>
                <tr>
                    <th class="col-index">#</th>

                    <th>
                        <span dir="rtl">{{ $pdfLabel($labels['customer']) }}</span>
                        
                    </th>

                    <th class="col-amount">
                        <span dir="rtl">{{ $pdfLabel($labels['amount']) }}</span>
                        
                    </th>
                </tr>
            </thead>

            <tbody>

                @foreach ($credits as $row)
                    <tr>
                        <td class="col-index">{{ $loop->iteration }}</td>

                        <td>
                            {{ $row->account->name }}
                            <span style="color:#94a3b8;font-size:9pt">({{ $row->account->code }})</span>
                        </td>

                        <td class="col-amount" style="color:#4f46e5">
                            {{ format_amount($row->total_credit, 2) }}
                        </td>

                    </tr>
                @endforeach

            </tbody>

            <tfoot>
                <tr class="totals-row">
                    <td colspan="2">{{ $pdfLabel($labels['total']) }}</td>
                    <td class="col-amount" style="color:#4f46e5">{{ format_amount($totalCredit, 2) }}</td>
                </tr>
            </tfoot>

        </table>

        <!-- DEBIT TITLE -->
        <table style="margin-top:25px">
            <tr>
                <td class="section-title debit-title">
                    <span dir="rtl">{{ $pdfLabel($labels['debits']) }}</span>
                    
                </td>
            </tr>
        </table>

        <!-- DEBIT TABLE -->
        <table>

            <thead>
                <tr>
                    <th class="col-index">#</th>

                    <th>
                        <span dir="rtl">{{ $pdfLabel($labels['customer']) }}</span>
                        
                    </th>

                    <th class="col-amount">
                        <span dir="rtl">{{ $pdfLabel($labels['amount']) }}</span>
                        
                    </th>
                </tr>
            </thead>

            <tbody>

                @foreach ($debits as $row)
                    <tr>
                        <td class="col-index">{{ $loop->iteration }}</td>

                        <td>
                            {{ $row->account->name }}
                            <span style="color:#94a3b8;font-size:9pt">({{ $row->account->code }})</span>
                        </td>

                        <td class="col-amount" style="color:#e11d48">
                            {{ format_amount($row->total_debit, 2) }}
                        </td>

                    </tr>
                @endforeach

            </tbody>

            <tfoot>
                <tr class="totals-row">
                    <td colspan="2">{{ $pdfLabel($labels['total']) }}</td>
                    <td class="col-amount" style="color:#e11d48">{{ format_amount($totalDebit, 2) }}</td>
                </tr>
            </tfoot>

        </table>

        <!-- BALANCE -->
        <div class="balance-box">

            <div style="color:#64748b;font-size:10pt;margin-bottom:5px">
                <span dir="rtl">{{ $pdfLabel($labels['final_balance']) }}</span>
                
            </div>

            <div class="balance-value {{ $balance >= 0 ? 'pos' : 'neg' }}">

                {{ format_amount($balance, 2) }}

            </div>

        </div>

        <div class="footer">
            {{ $pdfLabel(__('reports.generated_on')) }}: {{ now()->format('Y-m-d H:i') }}
        </div>

    </body>

</html>
