{{--
    Sign-in page of the fida-admin template, as in Fida Sarafi: the brand panel in the blue and
    violet of the template, and the form on a clean side.
    $localeRoute: name of the route that changes the language.
--}}
@php
    $brand = $setting?->company_name ?: config('app.name');
    $logo = $setting?->logo ? asset('images/'.$setting->logo) : null;
    $localeRoute = $localeRoute ?? 'lang.switch';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ text_dir() }}" data-bs-theme="light" data-primary="blue" data-icons="{{ asset_v('icons.svg') }}">
<head>
    @include('partials.head')
</head>
<body class="auth-body">
<div class="auth-shell">
    <aside class="auth-hero" aria-label="{{ __('About') }}">
        <div class="brand-link">
            @if ($logo)
                <img class="auth-logo" src="{{ $logo }}" alt="" width="46" height="46">
            @else
                <x-brand-logo :size="40" inverse />
            @endif
            <span class="brand-text">{{ $brand }}</span>
        </div>
        <div>
            <span class="ah-kicker"><x-icon name="building-columns" /> {{ __('Sarafi accounts, exchange and remittances') }}</span>
            <p class="auth-hero-title">{{ __('The books of your Sarafi, in one system.') }}</p>
            <p>{{ __('Customer accounts, the daily journal, currency exchange, hawala and reports, in English, Dari and Pashto.') }}</p>
        </div>
        <ul class="auth-points">
            <li><x-icon name="book" /> {{ __('Accounts and journal') }}</li>
            <li><x-icon name="coins" /> {{ __('Exchange and rates') }}</li>
            <li><x-icon name="paper-plane" /> {{ __('Remittances') }}</li>
        </ul>
    </aside>
    <main class="auth-main" id="app-content">
        <div class="auth-tools">
            <button class="topbar-icon" type="button" data-theme-toggle title="{{ __('Light / dark mode') }}" aria-label="{{ __('Light / dark mode') }}"><x-icon name="moon" /></button>
        </div>
        <div class="auth-mobile-hero"><span class="amh-logo"><x-brand-logo :size="44" /></span><strong>{{ $brand }}</strong><small>{{ __('Sarafi accounts, exchange and remittances') }}</small></div>
        @yield('content')
        <nav class="auth-languages" aria-label="{{ __('Language') }}">
            @foreach (config('app.locales') as $code => $language)
                <a href="{{ route($localeRoute, $code) }}" lang="{{ $code }}" hreflang="{{ $code }}" rel="nofollow" class="{{ app()->getLocale() === $code ? 'active' : '' }}" data-no-prefetch>{{ $language }}</a>
            @endforeach
        </nav>
        <footer class="mt-4 small text-muted">© {{ date('Y') }} {{ config('app.name') }} · <a href="https://fida.af" target="_blank" rel="noopener">Fida Technologies</a></footer>
    </main>
</div>
@include('partials.flash')
@include('partials.scripts', ['legacy' => false])
@stack('scripts')
</body>
</html>
