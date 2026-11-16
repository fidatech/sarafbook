<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['fa', 'ps', 'ar', 'ur'], true) ? 'rtl' : 'ltr' }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('remittance_receipt.title') }} #{{ $remittance->hawala_number ?: $remittance->id }}</title>
        <style>
            @font-face {
                font-family: Vazirmatn;
                src: url("{{ asset('fonts/webfonts/Vazirmatn[wght].woff2') }}") format('woff2');
                font-weight: 100 900;
                font-display: swap
            }

            @page {
                size: 80mm auto;
                margin: 0
            }

            * {
                box-sizing: border-box
            }

            html,
            body {
                margin: 0;
                background: #eceff1;
                color: #111;
                font: 11px/1.45 Vazirmatn, Tahoma, Arial, sans-serif
            }

            .toolbar {
                width: 80mm;
                max-width: 100%;
                margin: 10px auto;
                display: flex;
                justify-content: center;
                gap: 8px
            }

            .toolbar button {
                padding: 7px 15px;
                border: 1px solid #aaa;
                border-radius: 5px;
                background: #fff;
                font: 700 11px Vazirmatn;
                cursor: pointer
            }

            .receipt {
                width: 80mm;
                max-width: 100%;
                margin: 0 auto 15px;
                padding: 4mm;
                background: #fff;
                border: 1px solid #ddd
            }

            header {
                text-align: center;
                border: 1.5px solid #111;
                border-radius: 5px;
                padding: 2mm
            }

            .logo {
                display: block;
                max-width: 15mm;
                max-height: 11mm;
                object-fit: contain;
                filter: grayscale(1);
                margin: 0 auto 1mm
            }

            .company {
                margin: 0;
                font-size: 14px;
                line-height: 1.3;
                font-weight: 900;
                overflow-wrap: anywhere
            }

            .company-meta {
                display: flex;
                flex-direction: column;
                gap: .4mm;
                margin-top: 1.2mm;
                padding-top: 1mm;
                border-top: 1px dotted #999;
                font-size: 8.5px;
                color: #222;
                overflow-wrap: anywhere
            }

            .title-card {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 2mm;
                margin: 2mm 0 1mm;
                padding: 1.5mm 2mm;
                border: 1.5px solid #111;
                border-radius: 5px;
                background: #f1f1f1
            }

            .title {
                font-weight: 900;
                font-size: 12px
            }

            .hawala-badge {
                flex: 0 0 auto;
                padding: .7mm 2mm;
                border: 1px solid #111;
                border-radius: 12px;
                font-weight: 900;
                font-size: 12px;
                background: #fff
            }

            .print-meta {
                display: flex;
                justify-content: space-between;
                gap: 2mm;
                margin-bottom: 1.3mm;
                padding: 1mm 1.5mm;
                border: 1px dashed #777;
                border-radius: 4px;
                font-size: 8.5px;
                font-weight: 700
            }

            .ltr {
                direction: ltr;
                unicode-bidi: isolate
            }

            .receipt-table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
                border: 1.5px solid #111
            }

            .receipt-table td {
                padding: 1.6mm 1.2mm;
                border: 1px solid #333;
                vertical-align: middle
            }

            .receipt-table td:first-child {
                width: 30%;
                font-weight: 900;
                color: #111;
                text-align: start;
                background: #ededed
            }

            .receipt-table td:last-child {
                text-align: start;
                font-weight: 800;
                overflow-wrap: anywhere
            }

            .receipt-table .amount-row td {
                border-block: 2px solid #111
            }

            .receipt-table .amount-row td:first-child {
                background: #ededed;
                color: #111
            }

            .receipt-table .amount-cell {
                font-size: 16px;
                font-weight: 900
            }

            .receipt-table .notes-row td {
                min-height: 8mm;
                height: 8mm;
                vertical-align: top
            }

            .receipt-table .destination-row td:first-child {
                background: #ededed;
                color: #111
            }

            .receipt-table .branch-cell {
                font-size: 10.5px;
                line-height: 1.65
            }

            .branch-name {
                font-size: 11px;
                font-weight: 900
            }

            .branch-contact {
                display: block;
                margin-top: .5mm
            }

            .branch-address {
                display: block;

                border-top:1px dotted #888;margin-top:1mm;padding-top:1mm;font-weight:800}[dir="rtl"] .receipt-table .numeric-cell{text-align:right}[dir="ltr"] .receipt-table .numeric-cell {
                    text-align: left
                }

                .signatures {
                    display: flex;
                    justify-content: space-between;
                    gap: 8mm;
                    margin-top: 13mm;
                    font-size: 8.5px
                }

                .signature {
                    flex: 1;
                    text-align: center;
                    border-top: 1px solid #333;
                    padding-top: 1mm
                }

                .footer {
                    text-align: center;
                    border-top: 1px dashed #777;
                    margin-top: 3mm;
                    padding-top: 1.5mm;
                    font-size: 8px
                }

                @media print {

                    html,
                    body {
                        width: 80mm !important;
                        background: #fff
                    }

                    .toolbar {
                        display: none !important
                    }

                    .receipt {
                        width: 80mm !important;
                        max-width: 80mm !important;
                        margin: 0 !important;
                        padding: 3mm 4mm 4mm !important;
                        border: 0 !important
                    }
                }
        </style>
    </head>
    @php
        $isOutgoing = $remittance->type === 'outgoing';
        $isRtl = in_array(app()->getLocale(), ['fa', 'ps', 'ar', 'ur'], true);
        $branchAccount = $isOutgoing ? $remittance->toAccount : $remittance->fromAccount;
        $receiptDate = $remittance->date ? \Illuminate\Support\Carbon::parse($remittance->date) : ($remittance->created_at ?: now());
        $jalali = \Morilog\Jalali\Jalalian::fromCarbon($receiptDate);
        $months = [1 => 'حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله', 'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت'];
        $digits = fn($value) => strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
        $date = $digits($jalali->getDay()) . ' - ' . $months[$jalali->getMonth()] . ' - ' . $digits($jalali->getYear());
        $formatTime = function ($value) use ($isRtl) {
            $timeValue = \Illuminate\Support\Carbon::parse($value);
            $period = $isRtl ? ($timeValue->hour < 12 ? 'ق.ظ' : 'ب.ظ') : $timeValue->format('A');
            return $timeValue->format('h:i') . ' ' . $period;
        };
        $time = $formatTime($remittance->created_at ?: now());
        $printedAt = now();
        $printedJalali = \Morilog\Jalali\Jalalian::fromCarbon($printedAt);
        $printDate = $digits($printedJalali->getDay()) . ' - ' . $months[$printedJalali->getMonth()] . ' - ' . $digits($printedJalali->getYear());
        $printTime = $formatTime($printedAt);
        $currency = $remittance->currency?->name ?: $remittance->currency?->code ?? '-';
        $amount = rtrim(rtrim(number_format((float) $remittance->amount, 2, '.', ','), '0'), '.');
        $commissionAmount = (float) $remittance->commission_kabul_amount + (float) $remittance->commission_partner_amount;
        $commissionCurrency = $remittance->commissionCurrency?->name ?: $remittance->commissionCurrency?->code ?? '';
        $commission = $commissionAmount > 0 ? rtrim(rtrim(number_format($commissionAmount, 2, '.', ','), '0'), '.') . ' ' . $commissionCurrency : null;
        $logo = $settings?->logo;
        $logoUrl = $logo ? asset(str_contains($logo, '/') ? $logo : 'images/' . $logo) : null;
    @endphp

    <body>
        <nav class="toolbar"><button type="button" onclick="window.print()">{{ __('remittance_receipt.print') }}</button><button type="button"
                onclick="window.close()">{{ __('remittance_receipt.close') }}</button></nav>
        <main class="receipt">
            <header>
                @if ($logoUrl)
                    <img class="logo" src="{{ $logoUrl }}" alt="">
                @endif
                <h1 class="company">{{ $settings?->company_name ?: config('app.name') }}</h1>
                @if ($settings?->address || $settings?->contact)
                    <div class="company-meta">
                        @if ($settings?->address)
                            <span>{{ $settings->address }}</span>
                        @endif
                        @if ($settings?->contact)
                            <span class="ltr">{{ $settings->contact }}</span>
                        @endif
                    </div>
                @endif
            </header>

            <div class="title-card">
                <span class="title">{{ $isOutgoing ? __('remittance_receipt.outgoing_title') : __('remittance_receipt.incoming_title') }}</span>
                <span class="hawala-badge"><bdi>#{{ $remittance->hawala_number ?: $remittance->id }}</bdi></span>
            </div>
            <div class="print-meta"><span>{{ __('remittance_receipt.printed_at') }}</span><span>{{ $printDate }} <bdi class="ltr">{{ $printTime }}</bdi></span></div>

            <table class="receipt-table">
                <tr>
                    <td>{{ __('remittance_receipt.transfer_date') }}</td>
                    <td>{{ $date }} <span class="ltr">- {{ $time }}</span></td>
                </tr>
                <tr>
                    <td>{{ __('ui.hawala_no') }}</td>
                    <td class="numeric-cell ltr">{{ $remittance->hawala_number ?: $remittance->id }}</td>
                </tr>
                <tr>
                    <td>{{ __('ui.sender') }}</td>
                    <td>{{ $remittance->sender ?: '—' }}</td>
                </tr>
                <tr>
                    <td>{{ __('ui.receiver') }}</td>
                    <td>{{ $remittance->receiver ?: '—' }}</td>
                </tr>

                <tr class="amount-row">
                    <td>{{ __('ui.amount') }}</td>
                    <td class="amount-cell numeric-cell ltr">{{ $amount }} {{ $currency }}</td>
                </tr>
                @if ($commission)
                    <tr>
                        <td>{{ __('ui.commission') }}</td>
                        <td>{{ $commission }}</td>
                    </tr>
                @endif
                <tr class="notes-row">
                    <td>{{ __('ui.note') }}</td>
                    <td>{{ trim((string) $remittance->description) !== '' ? $remittance->description : '—' }}</td>
                </tr>
                <tr class="destination-row">
                    <td>{{ __('remittance_receipt.destination_branch') }}</td>
                    <td class="branch-cell">
                        <span class="branch-name">{{ $branchAccount?->name ?: '—' }}</span>
                        @if ($branchAccount?->contact)
                            <span class="branch-contact ltr">{{ $branchAccount->contact }}</span>
                        @endif
                        <span class="branch-address">{{ $branchAccount?->address ?: '—' }}</span>
                    </td>
                </tr>
            </table>

            <div class="signatures">
                <div class="signature">{{ __('remittance_receipt.customer_signature') }}</div>
                <div class="signature">{{ __('remittance_receipt.authorized_signature') }}</div>
            </div>
            <footer class="footer">{{ __('remittance_receipt.thank_you') }}</footer>
        </main>
        <script>
            window.addEventListener('load', function() {
                const ready = document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve();
                ready.then(function() {
                    setTimeout(function() {
                        window.print()
                    }, 200)
                })
            });
        </script>
    </body>

</html>
