@extends('layouts.site')
@section('title', __('SarafBook: accounts, exchange and hawala for Sarafis'))
@section('description', __('SarafBook keeps the books of a Sarafi: customer accounts in every currency, the daily journal, currency exchange, hawala and reports, in Dari, Pashto and English.'))

@php
    $base = config('tenancy.base_domain');
    $whatsapp = config('site.whatsapp') ? 'https://wa.me/'.config('site.whatsapp') : null;
    $features = [
        ['users', __('Customer accounts'), __('Every customer, Saraf and bank has an account with a balance in each currency and a statement you can print or send.')],
        ['book', __('Daily journal'), __('Record what comes in and what goes out during the day and see the cash of each currency at any moment.')],
        ['coins', __('Currency exchange'), __('Buy and sell currency at the rate of the day. The profit or loss of every deal is recorded by itself.')],
        ['paper-plane', __('Hawala'), __('Outgoing and incoming remittances with commission, a receipt for the customer and a record of who was paid.')],
        ['chart-column', __('Reports'), __('Balances, debtors and creditors, profit and loss and currency reports, as PDF or Excel.')],
        ['user-shield', __('Users and permissions'), __('Each employee signs in with his own name and sees only what you allow. Every change is kept in the audit log.')],
    ];
    $reasons = [
        ['database', __('Your own database'), __('The books of each Sarafi are kept in a separate database. No other Sarafi can see them.')],
        ['language', __('Dari, Pashto and English'), __('Every page and every receipt in the language you choose, written right to left where it should be.')],
        ['mobile-screen', __('Works on the phone'), __('Open the same system on a computer, a tablet or a phone. Nothing to install.')],
        ['shield-halved', __('Backed up every night'), __('A copy of your books is made every night, and you can always read and export them.')],
    ];
    $jsonLd = json_encode(['@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => config('app.name'), 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web', 'url' => url('/'), 'description' => __('SarafBook keeps the books of a Sarafi: customer accounts in every currency, the daily journal, currency exchange, hawala and reports, in Dari, Pashto and English.'), 'publisher' => ['@type' => 'Organization', 'name' => config('site.company'), 'url' => config('site.company_url')]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
@endphp

@push('head')
<script type="application/ld+json">{!! $jsonLd !!}</script>
@endpush

@section('content')
<section class="rh-hero" aria-labelledby="hero-title">
    <div class="rh-hero-main sb-hero-main">
        <div class="rh-hero-text">
            <span class="rh-hero-kicker"><x-icon name="building-columns" /> {{ __('For Sarafis and hawala offices') }}</span>
            <h1 id="hero-title">{{ __('The books of your Sarafi,') }} <span>{{ __('fast and in order.') }}</span></h1>
            <p>{{ __('Customer accounts, the daily journal, currency exchange, hawala and reports in one system, in Dari, Pashto and English.') }}</p>
            <div class="rh-hero-chips">
                @if ($whatsapp)<a href="{{ $whatsapp }}" target="_blank" rel="noopener"><x-icon name="comment-dots" /> {{ __('Ask for a demonstration') }}</a>@endif
                <a href="#features">{{ __('See what it does') }}</a>
            </div>
        </div>
        <div class="sb-signin" id="signin">
            <form class="rh-search" method="post" action="{{ route('site.find') }}" id="findForm">
                @csrf
                <h2 class="sb-signin-title"><x-icon name="right-to-bracket" /> {{ __('Sign in to your Sarafi') }}</h2>
                <p class="sb-signin-text">{{ __('Write the short name of your Sarafi. You will go to its own sign-in page.') }}</p>
                @if (session('find_error'))
                    <div class="auth-error" role="alert"><x-icon name="triangle-exclamation" /> <span>{{ session('find_error') }}</span></div>
                @endif
                <label class="form-label" for="sarafi">{{ __('Name of your Sarafi') }}</label>
                <div class="input-group sb-signin-group" dir="ltr">
                    <input class="form-control @error('sarafi') is-invalid @enderror" id="sarafi" name="sarafi" value="{{ old('sarafi') }}" placeholder="ahmadi" autocomplete="organization" autocapitalize="none" spellcheck="false" inputmode="url" maxlength="191" required aria-describedby="sarafi-help">
                    <span class="input-group-text">.{{ $base }}</span>
                </div>
                <div class="form-text" id="sarafi-help">{{ __('You may also write the full address of your Sarafi.') }}</div>
                <button class="btn btn-primary w-100 mt-3" type="submit">{{ __('Continue') }} <x-icon name="arrow-right" class="ms-1" /></button>
            </form>
        </div>
    </div>
</section>

<section class="rh-section" id="features" aria-labelledby="features-title">
    <div class="rh-section-head"><div><span class="rh-kicker">{{ __('Features') }}</span><h2 id="features-title">{{ __('Everything a Sarafi records in a day') }}</h2><p>{{ __('Built with Sarafis in Kabul, for the way the work is really done.') }}</p></div></div>
    <div class="rh-services sb-grid-3">
        @foreach ($features as [$icon, $title, $text])
            <div class="rh-service"><span class="rh-service-icon"><x-icon :name="$icon" /></span><strong>{{ $title }}</strong><span>{{ $text }}</span></div>
        @endforeach
    </div>
</section>

<section class="rh-section" id="why" aria-labelledby="why-title">
    <div class="rh-section-head"><div><span class="rh-kicker">{{ __('Why SarafBook') }}</span><h2 id="why-title">{{ __('Safe, simple and always with you') }}</h2></div></div>
    <div class="rh-services">
        @foreach ($reasons as [$icon, $title, $text])
            <div class="rh-service"><span class="rh-service-icon"><x-icon :name="$icon" /></span><strong>{{ $title }}</strong><span>{{ $text }}</span></div>
        @endforeach
    </div>
</section>

@if ($plans->isNotEmpty())
<section class="rh-section" id="plans" aria-labelledby="plans-title">
    <div class="rh-section-head"><div><span class="rh-kicker">{{ __('Plans') }}</span><h2 id="plans-title">{{ __('One yearly price, no surprises') }}</h2></div></div>
    <div class="rh-services sb-grid-3">
        @foreach ($plans as $plan)
            <div class="rh-service">
                <strong>{{ $plan->name }}</strong>
                <span class="sb-price" dir="ltr">{{ number_format((float) $plan->price_yearly) }} <small>{{ $plan->currency }} / {{ __('year') }}</small></span>
                <span>{{ __('Users') }}: {{ $plan->max_users ?? __('unlimited') }}</span>
                <ul class="sb-plan-list">
                    @foreach (\App\Http\Controllers\Platform\PlanController::FEATURES as $key => $label)
                        @if (! empty($plan->features[$key]))<li><x-icon name="circle-check" /> {{ __($label) }}</li>@endif
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</section>
@endif

<section class="rh-cta" aria-labelledby="cta-title">
    <div>
        <span class="rh-cta-kicker">{{ __('Start with a free trial') }}</span>
        <h2 class="sb-cta-title" id="cta-title">{{ __('See SarafBook with your own Sarafi\'s name on it.') }}</h2>
        <p>{{ __('Tell us the name of your Sarafi. We set up your own address and show you the system in your language.') }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if ($whatsapp)<a class="btn btn-gold btn-lg" href="{{ $whatsapp }}" target="_blank" rel="noopener"><x-icon name="comment-dots" class="me-1" /> WhatsApp</a>@endif
        @if (config('site.phone'))<a class="btn btn-outline-light btn-lg" href="tel:{{ preg_replace('/[^0-9+]/', '', config('site.phone')) }}" dir="ltr"><x-icon name="phone" class="me-1" /> {{ config('site.phone') }}</a>@endif
    </div>
</section>

@push('scripts')
<script>
// the name typed last time is offered again on this device
(function () {
    var input = document.getElementById('sarafi'), form = document.getElementById('findForm');
    try { if (input && !input.value) input.value = localStorage.getItem('sarafbook.sarafi') || ''; } catch (e) {}
    if (form) form.addEventListener('submit', function () { try { localStorage.setItem('sarafbook.sarafi', input.value.trim()); } catch (e) {} });
})();
</script>
@endpush
@endsection
