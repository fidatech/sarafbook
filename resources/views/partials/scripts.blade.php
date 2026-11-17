{{--
    Scripts of every page. $legacy = true loads jQuery, SweetAlert and the number boxes before the
    page's own scripts, as the pages of the first SarafBook expect; without it nothing blocks.
--}}
@php
    $erpText = [
        'cancel' => __('Cancel'),
        'confirmOk' => __('Yes'),
        'confirmTitle' => __('Are you sure?'),
        'noResults' => __('No results'),
    ];
    $defer = empty($legacy) ? 'defer' : '';
@endphp
<div class="nav-progress" id="navProgress" aria-hidden="true"><span></span></div>
<script>window.ERP_TEXT = {{ Illuminate\Support\Js::from($erpText) }};</script>
@if (! empty($legacy))
    <script src="{{ asset_v('js/vendor/jquery.min.js') }}"></script>
@endif
<script {{ $defer }} src="{{ asset_v('js/vendor/bootstrap.bundle.min.js') }}"></script>
@if (! empty($legacy))
    <script src="{{ asset_v('js/vendor/sweetalert2.min.js') }}"></script>
    <script src="{{ asset_v('assets/js/numeric-inputs.js') }}"></script>
@endif
{{--
    Loading pages early (docs/SPEED.md). The browser fetches a page as soon as the pointer rests on
    its link (Chrome, Edge, Opera, Samsung Internet), and js/app.js adds the pages this user is most
    likely to open next once the current page has settled. Only the HTML is fetched; no script of
    the next page runs until it is opened. Addresses on config('prefetch.never') are never fetched.
--}}
@if (config('prefetch.enabled'))
@php
    $never = array_values((array) config('prefetch.never'));
    $hoverRules = ['prefetch' => [[
        'where' => ['and' => [
            ['href_matches' => '/*'],
            ['not' => ['href_matches' => $never]],
            ['not' => ['selector_matches' => '[data-no-prefetch],[target=_blank],[download],[rel~=external],form a']],
        ]],
        'eagerness' => 'moderate',
    ]]];
@endphp
<script type="speculationrules">{!! json_encode($hoverRules, JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/json" id="prefetchConfig">{!! json_encode([
    'eager' => (int) config('prefetch.eager', 4),
    'never' => $never,
    'warm' => \App\Support\WarmAssets::urls(),
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endif
<script {{ $defer }} src="{{ asset_v('js/app.js') }}"></script>

