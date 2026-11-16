{{--
    The panel of the fida-admin template in the formal look of Rahnuma: sidebar, top bar, content,
    footer, and the tab bar of phones. Used through layouts.admin.base (a Sarafi) and
    layouts.platform (the platform panel); both set $shell and $legacy.
--}}
@php
    $shell = $shell ?? 'tenant';
    $home = $shell === 'platform' ? route('platform.dashboard') : url('admin/dashboard');
    $tenantNow = function_exists('tenant') ? tenant() : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ text_dir() }}" data-bs-theme="light" data-sidebar="light" data-layout="vertical" data-primary="blue" data-width="fluid" data-icons="{{ asset_v('icons.svg') }}" data-home="{{ $home }}">
<head>
    @include('partials.head')
    @if (! empty($legacy))
        {{-- icons and message boxes used by the pages carried over from the first SarafBook --}}
        <link rel="stylesheet" href="{{ asset_v('css/vendor/bootstrap-icons.css') }}">
        <link rel="stylesheet" href="{{ asset_v('css/vendor/sweetalert2.min.css') }}">
    @endif
    @yield('css')
</head>
<body>
<a class="skip-link" href="#app-content">{{ __('Skip to content') }}</a>
<div class="app-wrapper" id="app-shell" data-shell="{{ $shell }}">
    @include('partials.sidebar')
    <div class="app-main">
        @include('partials.topbar')
        <main class="app-content" id="app-content">
            <div class="content-container">
                @if ($shell === 'tenant' && session('platform_support'))
                    <div class="rh-impersonate"><span><x-icon name="user-shield" /> {{ __('Platform support is signed in to this Sarafi. What is done here is done in the name of its administrator.') }}</span></div>
                @endif
                @if ($shell === 'tenant' && $tenantNow && $tenantNow->isExpired())
                    <div class="alert alert-warning d-flex gap-2 align-items-start" role="status"><x-icon name="triangle-exclamation" class="mt-1" /><div>
                        @if ($tenantNow->isReadOnly())
                            <strong>{{ __('Your subscription has ended.') }}</strong> {{ __('The books are open for reading and export; renew to record new entries.') }}
                        @else
                            <strong>{{ __('Your subscription has ended.') }}</strong> {{ __('Please renew it soon to keep recording entries.') }}
                        @endif
                    </div></div>
                @endif
                @yield('content')
            </div>
        </main>
        <footer class="app-footer">
            <span>© {{ date('Y') }} {{ config('app.name') }}</span>
            <nav aria-label="{{ __('Footer') }}"><span class="text-muted">{{ __('by') }} <a href="https://fida.af" target="_blank" rel="noopener">Fida Technologies</a></span></nav>
        </footer>
    </div>
</div>

<nav class="app-tabbar" aria-label="{{ __('Quick menu') }}">
    <a href="{{ $home }}" class="tab-item {{ request()->url() === $home ? 'active' : '' }}"><x-icon name="house" /><em>{{ __('Home') }}</em></a>
    @if ($shell === 'tenant')
        @can('view customers')
            <a href="{{ url('admin/accounts') }}" class="tab-item {{ request()->is('admin/accounts*') ? 'active' : '' }}"><x-icon name="users" /><em>{{ __('ui.accounts') }}</em></a>
        @endcan
        @can('view journal')
            <a href="{{ url('admin/journal') }}" class="tab-item {{ request()->is('admin/journal*') ? 'active' : '' }}"><x-icon name="book" /><em>{{ __('ui.journal') }}</em></a>
        @endcan
    @else
        <a href="{{ route('platform.tenants.index') }}" class="tab-item {{ request()->routeIs('platform.tenants.*') ? 'active' : '' }}"><x-icon name="building-columns" /><em>{{ __('Sarafis') }}</em></a>
    @endif
    <button type="button" class="tab-item" data-bs-toggle="modal" data-bs-target="#searchModal"><x-icon name="magnifying-glass" /><em>{{ __('Search') }}</em></button>
    <button type="button" class="tab-item" id="tabMenu"><x-icon name="grip" /><em>{{ __('Menu') }}</em></button>
</nav>

<div class="modal fade" id="searchModal" tabindex="-1" aria-label="{{ __('Search') }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable search-dialog">
        <div class="modal-content">
            <div class="search-input"><x-icon name="magnifying-glass" /><input type="search" id="searchInput" placeholder="{{ __('Search pages…') }}" aria-label="{{ __('Search pages…') }}" autocomplete="off"><kbd>Esc</kbd></div>
            <div class="modal-body p-2" id="searchResults"></div>
        </div>
    </div>
</div>

<form id="logoutForm" method="POST" action="{{ $shell === 'platform' ? route('platform.logout') : route('logout') }}" hidden
      data-confirm="{{ __('Sign out?') }}" data-confirm-text="{{ __('You will need to sign in again to continue.') }}"
      data-confirm-ok="{{ __('Sign out') }}" data-confirm-style="primary">@csrf</form>

@include('partials.flash')
@include('partials.scripts')
@yield('js')
@stack('scripts')
</body>
</html>
