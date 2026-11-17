@extends('layouts.admin.base')
@section('title', __('ui.settings'))
@section('modern', '1')

{{-- The Sarafi's own details, shown on receipts and prints, and how amounts are written. --}}
@php
    $canUpdate = auth()->user()->can('update settings');
    $v = fn ($key) => old($key, $setting->{$key});
@endphp

@section('content')
<x-page-header :title="__('ui.settings')" icon="gear" :subtitle="__('Your Sarafi\'s details, printed on receipts and statements.')" />

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" novalidate>
    @csrf
    <fieldset @disabled(! $canUpdate)>
    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <section class="card mb-3" aria-labelledby="sCompany">
                <div class="card-header"><h2 class="h5 card-title mb-0" id="sCompany">{{ __('Company') }}</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <x-field col="col-12 col-md-6" name="company_name" :label="__('ui.company_name')" :value="$setting->company_name" />
                        <x-field col="col-12 col-md-6" name="contact" type="tel" :label="__('ui.contact')" :value="$setting->contact" dir="ltr" />
                        <x-field col="col-12 col-md-6" name="email" type="email" :label="__('ui.email')" :value="$setting->email" dir="ltr" />
                        <x-field col="col-12" name="address" type="textarea" :label="__('ui.address')" :value="$setting->address" />
                    </div>
                </div>
            </section>

            <section class="card" aria-labelledby="sNotes">
                <div class="card-header">
                    <h2 class="h5 card-title mb-0" id="sNotes">{{ __('Note on receipts') }}</h2>
                    <span class="text-muted small">{{ __('A line printed at the bottom of receipts, in the language of the receipt.') }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <x-field col="col-12 col-md-4" name="note_fa" type="textarea" :label="__('ui.lang_fa')" :value="$setting->note_fa" dir="rtl" />
                        <x-field col="col-12 col-md-4" name="note_ps" type="textarea" :label="__('ui.lang_ps')" :value="$setting->note_ps" dir="rtl" />
                        <x-field col="col-12 col-md-4" name="note_en" type="textarea" :label="__('ui.lang_en')" :value="$setting->note_en" dir="ltr" />
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="card mb-3" aria-labelledby="sWork">
                <div class="card-header"><h2 class="h5 card-title mb-0" id="sWork">{{ __('How the system works') }}</h2></div>
                <div class="card-body">
                    <x-field name="default_language" type="select" :label="__('ui.default_language')" :value="$setting->default_language"
                             :options="['fa' => __('ui.lang_fa'), 'ps' => __('ui.lang_ps'), 'en' => __('ui.lang_en')]" required />
                    <x-field name="currency" type="select" :label="__('ui.default_currency')" :value="$setting->currency"
                             :options="$currencies->mapWithKeys(fn ($c) => [$c->id => $c->code.' — '.$c->name])->all()" required />
                    <x-field name="round_amount" type="switch" :label="__('administration.rounding_enabled')" :value="(bool) $setting->round_amount" />
                    <p class="form-text mt-n2">{{ __('administration.rounding_help') }}</p>
                </div>
            </section>

            <section class="card" aria-labelledby="sLogo">
                <div class="card-header"><h2 class="h5 card-title mb-0" id="sLogo">{{ __('ui.company_logo') }}</h2></div>
                <div class="card-body">
                    <div class="logo-box mb-3">
                        @if ($setting->logo)
                            <img src="{{ asset('images/'.$setting->logo) }}" alt="{{ __('administration.logo') }}" id="logoPreview" style="max-height:80px;max-width:100%">
                        @else
                            <img alt="" id="logoPreview" hidden style="max-height:80px;max-width:100%">
                            <p class="text-muted mb-0" id="logoNone">{{ __('ui.no_logo_uploaded') }}</p>
                        @endif
                    </div>
                    <x-field name="logo" type="file" :label="__('ui.upload_new_logo')" accept="image/png,image/jpeg,image/webp,image/svg+xml" :hint="__('PNG, JPG, WebP or SVG, up to 2 MB.')" />
                </div>
            </section>
        </div>
    </div>
    </fieldset>

    @if ($canUpdate)
        <div class="form-actions card card-soft mt-3">
            <div class="card-body d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4"><x-icon name="check" class="me-1" /> {{ __('ui.save_settings') }}</button>
            </div>
        </div>
    @endif
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('change', function (e) {
    if (e.target.name !== 'logo' || !e.target.files || !e.target.files[0]) return;
    var img = document.getElementById('logoPreview'), none = document.getElementById('logoNone');
    img.src = URL.createObjectURL(e.target.files[0]); img.hidden = false; if (none) none.hidden = true;
});
</script>
@endpush
