@extends('layouts.auth', ['localeRoute' => 'platform.locale'])
@section('title', __('Platform sign in'))

@section('content')
<div class="rh-auth-card">
    <span class="rh-auth-icon" aria-hidden="true"><x-icon name="server" /></span>
    <h1 class="rh-auth-heading">{{ __('Platform sign in') }}</h1>
    <p class="rh-auth-sub">{{ __('For the staff who manage the Sarafis on this platform.') }}</p>

    @if ($errors->any())
        <div class="rh-auth-error" role="alert"><x-icon name="triangle-exclamation" /> <span>{{ $errors->first() }}</span></div>
    @endif

    <form method="POST" action="{{ route('platform.login.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">{{ __('Email address') }}</label>
            <div class="auth-field"><x-icon name="envelope" set="regular" /><input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="username" inputmode="email" dir="ltr" required autofocus></div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">{{ __('ui.password') }}</label>
            <div class="auth-field"><x-icon name="lock" /><input type="password" id="password" name="password" class="form-control" autocomplete="current-password" dir="ltr" required><button type="button" class="field-eye" data-toggle-password aria-label="{{ __('Show password') }}"><x-icon name="eye" set="regular" /></button></div>
        </div>
        <label class="rh-auth-remember" for="remember">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
            <span>{{ __('Keep me signed in') }}</span>
        </label>
        <button type="submit" class="btn btn-primary btn-lg w-100 rh-auth-submit">{{ __('Sign in') }} <x-icon name="arrow-right" class="ms-1" /></button>
    </form>
</div>
@endsection
