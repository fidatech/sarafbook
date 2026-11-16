@extends('layouts.admin.base')

@section('title', __('administration.whatsapp_title'))

@section('content')
<div class="card mt-4 shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">{{ __('ui.blade_whatsapp_connection') }}</h5>
    </div>
    <div class="card-body text-center">
        <p>{{ __('ui.status') }}:
            <span class="badge bg-{{ $status === 'connected' ? 'success' : 'danger' }}">
                {{ __('administration.connection_statuses')[$status] ?? strtoupper($status) }}
            </span>
        </p>

        @if ($status === 'connected')
            <p class="text-success fw-bold">{{ __('administration.whatsapp_connected') }}</p>
        @elseif($qr)
            <p>{{ __('ui.blade_scan_this_qr_code_with_your_whatsapp') }}</p>
            <img src="{{ $qr }}" class="img-fluid mt-2" style="max-width: 300px;">
        @else
            <p class="text-muted">{{ __('ui.blade_unable_to_load_qr_try_again_later') }}</p>
        @endif
    </div>
</div>
@endsection
