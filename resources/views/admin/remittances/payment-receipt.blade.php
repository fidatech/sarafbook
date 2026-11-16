<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('remittance_payment.payment_receipt') }} #{{ $remittance->hawala_number ?: $remittance->id }}</title>
    <style>
        @font-face{font-family:Vazirmatn;src:url("{{ asset('fonts/webfonts/Vazirmatn[wght].woff2') }}") format('woff2');font-weight:100 900;font-display:swap}
        @page{size:80mm auto;margin:0}
        *{box-sizing:border-box}body{margin:0;background:#eef2f5;color:#111;font:12px/1.65 Vazirmatn,Arial,sans-serif}
        .toolbar{max-width:520px;margin:20px auto;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;padding:0 12px}
        .toolbar button,.toolbar a{font:inherit;border:1px solid #bac6d0;border-radius:6px;padding:8px 14px;background:white;color:#243a4a;text-decoration:none;cursor:pointer}
        .receipt{width:80mm;max-width:100%;padding:6mm 4mm 8mm;margin:20px auto;background:white;border:1px solid #dde4ea}
        header{text-align:center;border-bottom:1px dashed #777;padding-bottom:12px;margin-bottom:14px}h1{font-size:18px;margin:0 0 4px}h2{font-size:14px;margin:12px 0 4px}p{margin:3px 0;overflow-wrap:anywhere}
        .reference{font-weight:700;font-size:14px}.paid-amount{text-align:center;border-block:2px solid #222;padding:12px 0;margin:14px 0}.paid-amount strong{display:block;font-size:24px;line-height:1.5;overflow-wrap:anywhere}
        dl{margin:0}dl>div{display:flex;justify-content:space-between;gap:10px;margin:8px 0;break-inside:avoid}dt{font-weight:400;flex:0 0 43%}dd{margin:0;text-align:end;font-weight:600;overflow-wrap:anywhere;min-width:0}.notes{border-top:1px dashed #777;margin-top:14px;padding-top:10px;white-space:pre-wrap}
        .signature{margin-top:22px;border-bottom:1px solid #333;padding-bottom:28px}.acknowledgment{font-size:10px;margin-top:14px}
        @media print{html,body{width:80mm;max-width:100%;background:#fff}.toolbar{display:none!important}.receipt{border:0;margin:0;padding:4mm;width:80mm;max-width:100%}}
    </style>
</head>
<body>
    <nav class="toolbar" aria-label="{{ __('remittance_payment.payment_receipt') }}">
        <button type="button" id="print-receipt">{{ __('remittance_payment.print_receipt') }}</button>
        <a href="{{ route('admin.remittances.payment', $remittance) }}">{{ __('remittance_payment.back_record') }}</a>
    </nav>
    <main class="receipt">
        <header>
            <h1>{{ $setting?->company_name ?: config('app.name') }}</h1>
            @if($setting?->address)<p>{{ $setting->address }}</p>@endif
            @if($setting?->contact)<p dir="auto">{{ $setting->contact }}</p>@endif
            <h2>{{ __('remittance_payment.payment_receipt') }}</h2>
            <p class="reference">{{ __('remittance_payment.hawala_number') }} <bdi>#{{ $remittance->hawala_number ?: $remittance->id }}</bdi></p>
            <p>{{ __('remittance_payment.paid') }}</p>
        </header>
        <div class="paid-amount"><span>{{ __('remittance_payment.amount_paid') }}</span><strong dir="ltr">{{ number_format((float) $remittance->partner_amount, 2) }} {{ $remittance->partnerCurrency?->code }}</strong></div>
        <dl>
            @foreach([
                __('remittance_payment.paid_at') => $remittance->paid_at->format('Y-m-d H:i'),
                __('remittance_payment.paid_by') => $remittance->paidBy?->name,
                __('ui.receiver') => $remittance->receiver,
                __('ui.sender') => $remittance->sender,
                __('remittance_payment.from') => $remittance->fromAccount?->name,
                __('remittance_payment.to') => $remittance->toAccount?->name,
            ] as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value ?: '—' }}</dd></div>
            @endforeach
        </dl>
        @if($remittance->description)<div class="notes"><b>{{ __('ui.description') }}</b><p>{{ $remittance->description }}</p></div>@endif
        <p class="acknowledgment">{{ __('remittance_payment.received_confirmation') }}</p>
        <div class="signature">{{ __('remittance_payment.receiver_signature') }}</div>
    </main>
    <script src="{{ asset('assets/js/remittance-payment-receipt.js') }}"></script>
</body>
</html>
