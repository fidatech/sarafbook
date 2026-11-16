@php
    $pageTitle = trim($__env->yieldContent('title'));
    $appName = $setting?->company_name ?: config('app.name');
    $fullTitle = $pageTitle === '' ? $appName : (str_contains($pageTitle, '|') ? $pageTitle : $pageTitle.' | '.$appName);
    $description = trim($__env->yieldContent('description')) ?: __('Accounts, exchange, remittances and reports of the Sarafi in one fast, secure system.');
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#2160d8">
<meta name="color-scheme" content="light dark">
<link rel="canonical" href="{{ url()->current() }}">
<link rel="icon" href="{{ asset_v('img/favicon.svg') }}" type="image/svg+xml">
<link rel="manifest" href="{{ asset_v('manifest.webmanifest') }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<script>(function(){var s={};try{s=JSON.parse(localStorage.getItem('erp.ui')||'{}')}catch(e){}var h=document.documentElement;h.setAttribute('data-bs-theme',s.theme==='dark'?'dark':'light');if(h.hasAttribute('data-layout'))h.setAttribute('data-layout',s.layout==='collapsed'?'collapsed':'vertical')})();</script>
<link rel="stylesheet" href="{{ asset_v(text_dir() === 'rtl' ? 'css/app.rtl.css' : 'css/app.css') }}">
{{ \App\Support\Fonts::style() }}
