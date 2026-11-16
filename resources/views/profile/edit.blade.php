@extends('layouts.admin.base')
@section('title', __('ui.blade_my_profile'))

@section('content')
<x-page-header :title="__('ui.blade_my_profile')" icon="user" :subtitle="auth()->user()->username" />

@if (session('status') === 'profile-updated' || session('status') === 'password-updated')
    <div class="alert alert-success" role="status">{{ __('Saved.') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-6">
        <form class="card h-100" method="post" action="{{ route('profile.update') }}">
            @csrf @method('patch')
            <div class="card-header"><h2 class="card-title h6 mb-0">{{ __('Name and email') }}</h2></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="name">{{ __('Name') }}</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="email">{{ __('Email address') }}</label>
                    <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" dir="ltr" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary" type="submit">{{ __('Save') }}</button></div>
        </form>
    </div>
    <div class="col-lg-6">
        <form class="card h-100" method="post" action="{{ route('password.update') }}">
            @csrf @method('put')
            <div class="card-header"><h2 class="card-title h6 mb-0">{{ __('Change password') }}</h2></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="current_password">{{ __('Current password') }}</label>
                    <input class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" type="password" id="current_password" name="current_password" autocomplete="current-password" dir="ltr" required>
                    @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">{{ __('New password') }}</label>
                    <input class="form-control @error('password', 'updatePassword') is-invalid @enderror" type="password" id="password" name="password" autocomplete="new-password" dir="ltr" required>
                    @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="password_confirmation">{{ __('Repeat the new password') }}</label>
                    <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" dir="ltr" required>
                </div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary" type="submit">{{ __('Save') }}</button></div>
        </form>
    </div>
</div>
@endsection
