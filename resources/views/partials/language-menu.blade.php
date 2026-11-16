{{-- Language list of the top bar. $localeRoute is the name of the route that changes the language. --}}
<div class="dropdown">
    <button class="topbar-icon topbar-lang" data-bs-toggle="dropdown" type="button" aria-expanded="false" title="{{ __('Language') }}" aria-label="{{ __('Language') }}"><x-icon name="globe" /><span>{{ strtoupper(app()->getLocale()) }}</span></button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><h6 class="dropdown-header">{{ __('Language') }}</h6></li>
        @foreach (config('app.locales') as $code => $language)
            <li><a class="dropdown-item {{ app()->getLocale() === $code ? 'active' : '' }}" href="{{ route($localeRoute ?? 'lang.switch', $code) }}" lang="{{ $code }}" hreflang="{{ $code }}" rel="nofollow" data-no-prefetch>{{ $language }}</a></li>
        @endforeach
    </ul>
</div>
