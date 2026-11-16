<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['fa', 'ps', 'ar', 'ur'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('journal_receipt.title') }} #{{ $transaction->id }}</title>
    <style>
        @font-face{font-family:Vazirmatn;src:url("{{ asset('fonts/webfonts/Vazirmatn[wght].woff2') }}") format('woff2');font-weight:100 900;font-display:swap}
        @page{size:80mm auto;margin:0}*{box-sizing:border-box}
        html,body{margin:0;background:#eceff1;color:#111;font:11px/1.5 Vazirmatn,Tahoma,Arial,sans-serif}
        .toolbar{width:80mm;max-width:100%;margin:10px auto;display:flex;justify-content:center;gap:8px}.toolbar button{padding:7px 15px;border:1px solid #aaa;border-radius:5px;background:#fff;font:700 11px Vazirmatn;cursor:pointer}
        .receipt{width:80mm;max-width:100%;margin:0 auto 15px;padding:4mm;background:#fff;border:1px solid #ddd}
        header{text-align:center;border:1.5px solid #111;border-radius:5px;padding:2mm}.logo{display:block;max-width:15mm;max-height:11mm;object-fit:contain;filter:grayscale(1);margin:0 auto 1mm}.company{margin:0;font-size:14px;line-height:1.3;font-weight:900;overflow-wrap:anywhere}.company-meta{display:flex;flex-direction:column;gap:.4mm;margin-top:1.2mm;padding-top:1mm;border-top:1px dotted #999;font-size:8.5px;overflow-wrap:anywhere}
        .title-card{display:flex;align-items:center;justify-content:space-between;gap:2mm;margin:2mm 0 1mm;padding:1.5mm 2mm;border:1.5px solid #111;border-radius:5px;background:#f1f1f1}.title{font-weight:900;font-size:12px}.number{flex:0 0 auto;padding:.7mm 2mm;border:1px solid #111;border-radius:12px;font-weight:900;font-size:12px;background:#fff}.print-meta{display:flex;justify-content:space-between;gap:2mm;margin-bottom:1.3mm;padding:1mm 1.5mm;border:1px dashed #777;border-radius:4px;font-size:8.5px;font-weight:700}
        .statement{margin:1.5mm 0;padding:1.8mm;border:1px solid #555;border-radius:4px;text-align:center;font-weight:900}
        table{width:100%;border-collapse:collapse;table-layout:fixed;border:1.5px solid #111}td{padding:1.7mm 1.2mm;border:1px solid #333;vertical-align:middle}td:first-child{width:34%;font-weight:900;background:#ededed;text-align:start}td:last-child{text-align:start;font-weight:800;overflow-wrap:anywhere}.amount-row td,.balance-row td{border-block:2px solid #111}.amount-value{font-size:16px;font-weight:900}.balance-value{font-size:13px;font-weight:900}.description-row td{height:11mm;vertical-align:top}.ltr{direction:ltr;unicode-bidi:isolate}[dir="rtl"] .numeric{text-align:right}[dir="ltr"] .numeric{text-align:left}
        .customer-service{text-align:center;margin-top:3mm;padding-top:1.5mm;border-top:1px dashed #777;font-size:9px;font-weight:900}.signatures{display:flex;justify-content:space-between;gap:8mm;margin-top:13mm;font-size:8.5px}.signature{flex:1;text-align:center;border-top:1px solid #333;padding-top:1mm}.footer{text-align:center;border-top:1px dashed #777;margin-top:3mm;padding-top:1.5mm;font-size:8px}
        @media print{html,body{width:80mm!important;background:#fff}.toolbar{display:none!important}.receipt{width:80mm!important;max-width:80mm!important;margin:0!important;padding:3mm 4mm 4mm!important;border:0!important}}
    </style>
</head>
@php
    $isRtl = in_array(app()->getLocale(), ['fa', 'ps', 'ar', 'ur'], true);
    $isCredit = $transaction->transaction_type === 'credit';
    $transactionDate = $transaction->created_at ?: now();
    $months = [1 => 'حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله', 'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت'];
    $digits = fn($value) => strtr((string) $value, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
    $formatDate = function ($value) use ($isRtl, $months, $digits) {
        if (!$isRtl) return $value->format('Y-m-d');
        $jalali = \Morilog\Jalali\Jalalian::fromCarbon($value);
        return $digits($jalali->getDay()) . ' - ' . $months[$jalali->getMonth()] . ' - ' . $digits($jalali->getYear());
    };
    $formatTime = function ($value) use ($isRtl) {
        $period = $isRtl ? ($value->hour < 12 ? 'ق.ظ' : 'ب.ظ') : $value->format('A');
        return $value->format('h:i') . ' ' . $period;
    };
    $printedAt = now();
    $currency = $transaction->currency?->name ?: ($transaction->currency?->code ?? '');
    $formatAmount = fn($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ','), '0'), '.');
    $logo = $settings?->logo;
    $logoUrl = $logo ? asset(str_contains($logo, '/') ? $logo : 'images/' . $logo) : null;
@endphp
<body>
    <nav class="toolbar"><button type="button" onclick="window.print()">{{ __('journal_receipt.print') }}</button><button type="button" onclick="window.close()">{{ __('journal_receipt.close') }}</button></nav>
    <main class="receipt">
        <header>
            @if($logoUrl)<img class="logo" src="{{ $logoUrl }}" alt="">@endif
            <h1 class="company">{{ $settings?->company_name ?: config('app.name') }}</h1>
            @if($settings?->address || $settings?->contact)
                <div class="company-meta">
                    @if($settings?->address)<span>{{ $settings->address }}</span>@endif
                    @if($settings?->contact)<span class="ltr">{{ $settings->contact }}</span>@endif
                </div>
            @endif
        </header>

        <div class="title-card">
            <span class="title">{{ $isCredit ? __('journal_receipt.credit_title') : __('journal_receipt.debit_title') }}</span>
            <span class="number"><bdi>#{{ $transaction->id }}</bdi></span>
        </div>
        <div class="print-meta"><span>{{ __('journal_receipt.printed_at') }}</span><span>{{ $formatDate($printedAt) }} <bdi class="ltr">{{ $formatTime($printedAt) }}</bdi></span></div>
        <div class="statement">{{ $isCredit ? __('journal_receipt.credit_statement') : __('journal_receipt.debit_statement') }}</div>

        <table>
            <tr><td>{{ __('journal_receipt.transaction_date') }}</td><td>{{ $formatDate($transactionDate) }} <span class="ltr">- {{ $formatTime($transactionDate) }}</span></td></tr>
            <tr><td>{{ __('journal_receipt.receipt_number') }}</td><td class="numeric ltr">{{ $transaction->id }}</td></tr>
            <tr><td>{{ __('journal_receipt.account') }}</td><td>{{ $transaction->account?->name ?: '—' }}</td></tr>
            <tr class="amount-row"><td>{{ __('journal_receipt.amount') }}</td><td class="amount-value numeric ltr">{{ $formatAmount($transaction->amount) }} {{ $currency }}</td></tr>
            <tr class="description-row"><td>{{ __('journal_receipt.description') }}</td><td>{{ trim((string) $transaction->note) !== '' ? $transaction->note : '—' }}</td></tr>
            <tr class="balance-row"><td>{{ __('journal_receipt.last_balance') }}</td><td class="balance-value numeric ltr">{{ $formatAmount($balance) }} {{ $currency }}</td></tr>
        </table>

        <div class="customer-service">{{ __('journal_receipt.customer_service') }}: <bdi class="ltr">0202102121</bdi></div>
        <div class="signatures"><div class="signature">{{ __('journal_receipt.customer_signature') }}</div><div class="signature">{{ __('journal_receipt.authorized_signature') }}</div></div>
        <footer class="footer">{{ __('journal_receipt.thank_you') }}</footer>
    </main>
    <script>window.addEventListener('load',function(){const ready=document.fonts&&document.fonts.ready?document.fonts.ready:Promise.resolve();ready.then(function(){setTimeout(function(){window.print()},200)})});</script>
</body>
</html>
