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
<script {{ $defer }} src="{{ asset_v('js/app.js') }}"></script>
{{-- The next page is fetched as soon as the pointer rests on its link, so opening it feels instant. --}}
<script type="speculationrules">
{"prefetch":[{"where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/logout","/lang/*","/platform/lang/*","/*recalculate*","/*export*","/*pdf*","/*print*","/*download*","/*delete*","/*template*","/attachments/*","/storage/*","/_platform/*"]}},{"not":{"selector_matches":"[data-no-prefetch],[target=_blank],[download],[rel~=external]"}}]},"eagerness":"moderate"}]}
</script>
