{{--
    Sign-in page in the formal look: a navy panel with the brand and what the system does, and the
    form on a clean side with the languages and the light / dark switch.
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
<div class="rh-auth">
    <aside class="rh-auth-side" aria-label="{{ __('About') }}">
        <div class="rh-auth-brand">
            @if ($logo)
                <img src="{{ $logo }}" alt="" width="46" height="46" style="width:46px;height:46px;object-fit:cover;border-radius:12px;background:#fff">
            @else
                <x-brand-logo :size="46" inverse />
            @endif
            <span>{{ $brand }}<small>{{ __('Sarafi accounts, exchange and remittances') }}</small></span>
        </div>
        <div class="rh-auth-pitch">
            <span class="rh-auth-arch" aria-hidden="true"><x-icon name="building-columns" /></span>
            <p class="rh-auth-title">{{ __('The books of your Sarafi, in one system.') }}</p>
            <p>{{ __('Customer accounts, the daily journal, currency exchange, hawala and reports, in English, Dari and Pashto.') }}</p>
        </div>
        <ul class="rh-auth-roles">
            <li><span><x-icon name="book" /></span><div><strong>{{ __('Accounts and journal') }}</strong><small>{{ __('Every customer balance in every currency, always up to date.') }}</small></div></li>
            <li><span><x-icon name="coins" /></span><div><strong>{{ __('Exchange and rates') }}</strong><small>{{ __('Buy and sell currency with the profit of each deal recorded.') }}</small></div></li>
            <li><span><x-icon name="paper-plane" /></span><div><strong>{{ __('Remittances') }}</strong><small>{{ __('Outgoing and incoming hawala with receipts and payment records.') }}</small></div></li>
        </ul>
    </aside>
    <main class="rh-auth-main" id="app-content">
        <div class="rh-auth-top">
            <div class="rh-auth-brand is-mobile"><x-brand-logo :size="38" /><span>{{ $brand }}</span></div>
            <nav class="rh-auth-langs" aria-label="{{ __('Language') }}">
                @foreach (config('app.locales') as $code => $language)
                    <a href="{{ route($localeRoute, $code) }}" lang="{{ $code }}" hreflang="{{ $code }}" rel="nofollow" class="{{ app()->getLocale() === $code ? 'active' : '' }}" data-no-prefetch>{{ $language }}</a>
                @endforeach
            </nav>
            <button class="topbar-icon" type="button" data-theme-toggle title="{{ __('Light / dark mode') }}" aria-label="{{ __('Light / dark mode') }}"><x-icon name="moon" /></button>
        </div>
        <div class="rh-auth-center">@yield('content')</div>
        <footer class="rh-auth-foot">© {{ date('Y') }} {{ config('app.name') }} · {{ __('by') }} <a href="https://fida.af" target="_blank" rel="noopener">Fida Technologies</a></footer>
    </main>
</div>
@include('partials.flash')
@include('partials.scripts', ['legacy' => false])
@stack('scripts')
</body>
</html>
