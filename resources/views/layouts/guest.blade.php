{{-- <x-guest-layout> of the password pages, inside the sign-in look. --}}
@extends('layouts.auth')
@section('content')
<div class="auth-card">{{ $slot }}</div>
@endsection
