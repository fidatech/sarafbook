@extends('layouts.admin.base')

@section('title', __('administration.settings'))

@section('content')
<div class="row g-4">

    <!-- Company Info -->
    <div class="col-12">
        <div class="card rounded-4 border-0 shadow-sm">
            <div class="card-header border-bottom bg-white">
                <h5 class="fw-bold mb-0">🧾 {{ __('ui.company_information') }}</h5>
            </div>

            <div class="card-body my-4">
                <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-4">

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('ui.company_name') }}</label>
                            <input type="text" name="company_name" class="form-control" value="{{ $setting->company_name }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('ui.contact') }}</label>
                            <input type="text" name="contact" class="form-control" value="{{ $setting->contact }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('ui.email') }}</label>
                            <input type="email" name="email" class="form-control" value="{{ $setting->email }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('ui.address') }}</label>
                            <textarea name="address" class="form-control" rows="3">{{ $setting->address }}</textarea>
                        </div>

                    </div>
            </div>
        </div>
    </div>

    <!-- Preferences -->
    <div class="col-md-6">
        <div class="card rounded-4 h-100 border-0 shadow-sm">
            <div class="card-header border-bottom bg-white">
                <h5 class="fw-bold mb-0">⚙️ {{ __('ui.preferences') }}</h5>
            </div>

            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('ui.default_language') }}</label>
                    <select name="default_language" class="form-select">
                        <option value="en" @selected($setting->default_language === 'en')>{{ __('ui.lang_en') }}</option>
                        <option value="fa" @selected($setting->default_language === 'fa')>{{ __('ui.lang_fa') }}</option>
                        <option value="ps" @selected($setting->default_language === 'ps')>{{ __('ui.lang_ps') }}</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('ui.default_currency') }}</label>
                    <select name="currency" class="form-select">
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->id }}" @selected($setting->currency === $currency->id)>
                                {{ $currency->name }} ({{ $currency->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- ROUND AMOUNT -->
                <div class="mb-3">
                    <label class="form-label fw-semibold d-block">{{ __('ui.blade_round_amount') }}</label>

                    <div class="form-check form-switch">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="round_amount"
                            id="round_amount"
                            value="1"
                            {{ $setting->round_amount ? 'checked' : '' }}
                        >

                        <label class="form-check-label" for="round_amount">
                            {{ __('administration.rounding_enabled') }}
                        </label>
                    </div>

                    <small class="text-muted">
                        {{ __('administration.rounding_help') }}
                    </small>
                </div>

            </div>
        </div>
    </div>

    <!-- Logo -->
    <div class="col-md-6">
        <div class="card rounded-4 h-100 border-0 shadow-sm">
            <div class="card-header border-bottom bg-white">
                <h5 class="fw-bold mb-0">🖼️ {{ __('ui.company_logo') }}</h5>
            </div>

            <div class="card-body">

                <label class="form-label fw-semibold">{{ __('ui.current_logo') }}</label>
                <div class="mb-3">
                    @if ($setting->logo)
                        <img src="{{ asset('images/' . $setting->logo) }}" alt="{{ __('administration.logo') }}" class="img-fluid rounded border shadow-sm" style="max-height: 80px;">
                    @else
                        <p class="text-muted">{{ __('ui.no_logo_uploaded') }}</p>
                    @endif
                </div>

                <label class="form-label fw-semibold">{{ __('ui.upload_new_logo') }}</label>
                <input type="file" name="logo" class="form-control" accept="image/*">

            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="col-12">
        <div class="text-end">
            <button type="submit" class="btn btn-success btn-lg mt-2 px-5 shadow-sm">
                💾 {{ __('ui.save_settings') }}
            </button>
        </div>
    </div>

    </form>

</div>
@endsection
