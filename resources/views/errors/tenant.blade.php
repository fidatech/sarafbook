{{-- Shown before any Sarafi is selected, so it uses no database and no session. --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{{ $state === 'suspended' ? 'This Sarafi is suspended' : 'No Sarafi at this address' }} | {{ config('app.name') }}</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f3f5f9;color:#232a3a;font:16px/1.6 system-ui,-apple-system,"Segoe UI",Tahoma,sans-serif}
main{max-width:460px;margin:24px;padding:36px 32px;background:#fff;border:1px solid #e9edf3;border-top:4px solid #c9a227;border-radius:18px;text-align:center}
h1{font-size:22px;margin:0 0 10px}p{margin:0 0 6px;color:#4b5468}small{color:#6b7385}
</style>
</head>
<body>
<main>
@if ($state === 'suspended')
    <h1>This Sarafi is suspended</h1>
    <p dir="rtl" lang="fa">این صرافی به حالت تعلیق درآمده است.</p>
    <p>Please contact Fida Technologies to reactivate it. Your books are kept safely.</p>
@else
    <h1>No Sarafi at this address</h1>
    <p dir="rtl" lang="fa">در این آدرس صرافی ثبت نشده است.</p>
    <p>Please check the address, or contact Fida Technologies.</p>
@endif
<small>{{ config('app.name') }} · fida.af</small>
</main>
</body>
</html>
