@extends('layouts.auth')
@section('title', __('Sign in'))
@section('description', __('Sign in to the Sarafi system: accounts, exchange, remittances and reports.'))

@section('content')
<div class="auth-card">
    <div class="ac-icon" aria-hidden="true"><x-icon name="right-to-bracket" /></div>
    <h1 class="auth-heading">{{ __('Sign in') }}</h1>
    <p class="ac-sub">{{ __('ui.blade_please_sign_in_to_your_account') }}</p>

    @if ($errors->any())
        <div class="auth-error" role="alert"><x-icon name="triangle-exclamation" /> <span>{{ $errors->first() }}</span></div>
    @endif

    <form method="POST" action="{{ route('login') }}" id="loginForm">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="username">{{ __('ui.username') }}</label>
            <div class="auth-field"><x-icon name="user" set="regular" /><input type="text" id="username" name="username" value="{{ old('username') }}" class="form-control @error('username') is-invalid @enderror" autocomplete="username" autocapitalize="none" spellcheck="false" dir="ltr" minlength="4" required autofocus></div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">{{ __('ui.password') }}</label>
            <div class="auth-field"><x-icon name="lock" /><input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" dir="ltr" required><button type="button" class="field-eye" data-toggle-password aria-label="{{ __('Show password') }}"><x-icon name="eye" set="regular" /></button></div>
            <div class="auth-caps" id="capsNote" hidden><x-icon name="circle-info" /> {{ __('Caps Lock is on.') }}</div>
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
            <label class="form-check-label small" for="remember">{{ __('Keep me signed in') }}</label>
        </div>
        <button type="submit" class="btn btn-gradient btn-lg w-100">{{ __('Sign in') }} <x-icon name="arrow-right" class="ms-1" /></button>
    </form>
</div>
@push('scripts')
<script>
(() => {
 const pass=document.getElementById('password'), note=document.getElementById('capsNote');
 const caps=e=>{ if(e.getModifierState) note.hidden=!e.getModifierState('CapsLock'); };
 pass?.addEventListener('keyup',caps); pass?.addEventListener('keydown',caps); pass?.addEventListener('blur',()=>note.hidden=true);
})();
</script>
@endpush
@endsection
