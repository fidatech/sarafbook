{{-- The SarafBook mark: an open ledger with a coin, on the gradient tile of the template. --}}
@props(['size' => 32, 'inverse' => false])
@php
    $gradient = 'lg'.\Illuminate\Support\Str::random(6);
@endphp
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
@if ($inverse)
<rect width="40" height="40" rx="11" fill="#fff"/>
<path d="M8 12.5c4-1.6 8-1.3 11 .9v15.2c-3-2.2-7-2.5-11-.9V12.5zm24 0c-4-1.6-8-1.3-11 .9v15.2c3-2.2 7-2.5 11-.9V12.5z" style="fill:var(--primary)"/>
<circle cx="28.5" cy="12" r="5.2" fill="#c9a227" stroke="#fff" stroke-width="1.6"/>
@else
<rect width="40" height="40" rx="11" fill="url(#{{ $gradient }})"/>
<path d="M8 12.5c4-1.6 8-1.3 11 .9v15.2c-3-2.2-7-2.5-11-.9V12.5zm24 0c-4-1.6-8-1.3-11 .9v15.2c3-2.2 7-2.5 11-.9V12.5z" fill="#fff"/>
<circle cx="28.5" cy="12" r="5.2" fill="#e3c04f" stroke="#10305c" stroke-width="1.6"/>
<defs><linearGradient id="{{ $gradient }}" x1="0" y1="0" x2="40" y2="40"><stop style="stop-color:var(--brand-from,#1b4e96)"/><stop offset="1" style="stop-color:var(--brand-to,#0a1f3d)"/></linearGradient></defs>
@endif
</svg>
