@php
    $user = $shell === 'platform' ? auth('platform')->user() : auth()->user();
    $pageTitle = trim(explode('|', $__env->yieldContent('title'))[0]);
    $brand = $shell === 'platform' ? config('app.name') : ($setting?->company_name ?: config('app.name'));
@endphp
<header class="app-topbar" id="appTopbar">
    <button class="btn-icon btn-ghost topbar-toggle" id="sidebarToggle" type="button" aria-label="{{ __('Show or hide the menu') }}"><x-icon name="bars-staggered" /></button>
    <button class="appbar-back" type="button" id="appBack" aria-label="{{ __('Back') }}"><x-icon name="chevron-left" /></button>
    <div class="appbar-title" id="appbarTitle">{{ $pageTitle !== '' ? $pageTitle : $brand }}</div>

    <button class="topbar-search" type="button" data-bs-toggle="modal" data-bs-target="#searchModal" aria-label="{{ __('Search pages…') }}">
        <x-icon name="magnifying-glass" /><span>{{ __('Search pages…') }}</span><span class="kbd-group"><kbd>Ctrl</kbd><kbd>K</kbd></span>
    </button>

    <div class="topbar-brand" aria-hidden="true">{{ $brand }}</div>

    <div class="topbar-right">
        @include('partials.language-menu', ['localeRoute' => $shell === 'platform' ? 'platform.locale' : 'lang.switch'])

        <button class="topbar-icon" type="button" data-theme-toggle title="{{ __('Light / dark mode') }}" aria-label="{{ __('Light / dark mode') }}"><x-icon name="moon" /></button>

        <div class="dropdown">
            <button class="topbar-user" data-bs-toggle="dropdown" type="button" aria-expanded="false" aria-label="{{ __('Account') }}">
                <span class="avatar avatar-sm avatar-soft-primary">{{ initial($user?->name) }}</span>
                <span class="topbar-user-meta"><strong>{{ $user?->name }}</strong><small>{{ $shell === 'platform' ? __('Platform administrator') : ($user?->username) }}</small></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end user-menu">
                <li class="user-menu-head"><span class="avatar avatar-md avatar-soft-primary">{{ initial($user?->name) }}</span><div><strong>{{ $user?->name }}</strong><small>{{ $user?->email ?: $user?->username }}</small></div></li>
                <li><hr class="dropdown-divider"></li>
                @if ($shell !== 'platform')
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><x-icon name="user" /> {{ __('ui.blade_my_profile') }}</a></li>
                    @can('view settings')
                        <li><a class="dropdown-item" href="{{ url('admin/settings') }}"><x-icon name="gear" /> {{ __('ui.system_settings') }}</a></li>
                    @endcan
                    <li><hr class="dropdown-divider"></li>
                @endif
                <li><button class="dropdown-item text-danger" type="submit" form="logoutForm"><x-icon name="right-from-bracket" /> {{ __('Sign out') }}</button></li>
            </ul>
        </div>
    </div>
</header>
