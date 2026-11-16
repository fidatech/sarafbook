{{--
    The public website on the platform's own address, in the formal look: a navy bar with contact
    details and languages, the header with the brand and menu, the page, and a navy footer.
--}}
@php
    $phone = config('site.phone');
    $email = config('site.email');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ text_dir() }}" data-bs-theme="light" data-primary="blue" data-icons="{{ asset_v('icons.svg') }}">
<head>
    @include('partials.head')
    @stack('head')
</head>
<body class="public-body">
<a class="skip-link" href="#app-content">{{ __('Skip to content') }}</a>
<div class="site-topbar">
    <div class="site-topbar-inner">
        <div class="site-contact">
            @if ($phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" dir="ltr"><x-icon name="phone" /> {{ $phone }}</a>@endif
            @if ($email)<a href="mailto:{{ $email }}" dir="ltr"><x-icon name="envelope" /> {{ $email }}</a>@endif
        </div>
        <div class="site-tools">
            <nav class="site-langs" aria-label="{{ __('Language') }}">
                @foreach (config('app.locales') as $code => $language)
                    <a class="{{ app()->getLocale() === $code ? 'active' : '' }}" href="{{ route('platform.locale', $code) }}" lang="{{ $code }}" hreflang="{{ $code }}" data-no-prefetch>{{ $language }}</a>
                @endforeach
            </nav>
            <button class="site-theme" type="button" data-theme-toggle title="{{ __('Light / dark mode') }}" aria-label="{{ __('Light / dark mode') }}"><x-icon name="moon" /></button>
        </div>
    </div>
</div>
<header class="site-header">
    <div class="site-header-inner">
        <a class="brand-link" href="{{ route('site.home') }}" aria-label="{{ config('app.name') }}">
            <x-brand-logo :size="44" />
            <span class="brand-text">{{ config('app.name') }}<small>{{ __('Sarafi accounts, exchange and remittances') }}</small></span>
        </a>
        <nav class="site-nav" aria-label="{{ __('Menu') }}">
            <a href="#features">{{ __('Features') }}</a>
            <a href="#why">{{ __('Why SarafBook') }}</a>
            @if (! empty($plans) && $plans->isNotEmpty())<a href="#plans">{{ __('Plans') }}</a>@endif
            <a href="#contact">{{ __('Contact') }}</a>
        </nav>
        <div class="topbar-right">
            <a class="btn btn-primary" href="#signin"><x-icon name="right-to-bracket" class="me-1" /> {{ __('Sign in') }}</a>
        </div>
    </div>
</header>

<main class="site-main" id="app-content">
    @yield('content')
</main>

<footer class="site-footer" id="contact">
    <div class="site-footer-inner">
        <div class="site-footer-brand">
            <span class="brand-link"><x-brand-logo :size="40" inverse /><span class="brand-text">{{ config('app.name') }}</span></span>
            <p>{{ __('The books of your Sarafi, in one system.') }}</p>
        </div>
        <div>
            <h2 class="site-footer-title">{{ __('Contact') }}</h2>
            @if ($phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" dir="ltr"><span><x-icon name="phone" /> {{ $phone }}</span></a>@endif
            @if (config('site.whatsapp'))<a href="https://wa.me/{{ config('site.whatsapp') }}" target="_blank" rel="noopener"><span><x-icon name="comment-dots" /> WhatsApp</span></a>@endif
            @if ($email)<a href="mailto:{{ $email }}" dir="ltr"><span><x-icon name="envelope" /> {{ $email }}</span></a>@endif
            @if (config('site.address'))<span><x-icon name="location-dot" /> {{ config('site.address') }}</span>@endif
        </div>
        <div>
            <h2 class="site-footer-title">{{ config('app.name') }}</h2>
            <a href="#features">{{ __('Features') }}</a>
            <a href="#why">{{ __('Why SarafBook') }}</a>
            <a href="#signin">{{ __('Sign in to your Sarafi') }}</a>
        </div>
    </div>
    <div class="site-footer-bottom">
        <div><span>© {{ date('Y') }} {{ config('app.name') }}</span><span>{{ __('by') }} <a href="{{ config('site.company_url') }}" target="_blank" rel="noopener">{{ config('site.company') }}</a></span></div>
    </div>
</footer>
@include('partials.flash')
@include('partials.scripts', ['legacy' => false])
@stack('scripts')
</body>
</html>
