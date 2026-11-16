@extends('layouts.platform')
@section('title', __('Audit log'))

@section('content')
<x-page-header :title="__('Audit log')" icon="clipboard-check" :crumbs="[[__('Platform'), route('platform.dashboard')], [__('Audit log')]]" :subtitle="__('What platform staff did, including every sign-in to a Sarafi.')" />

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th scope="col">{{ __('When') }}</th><th scope="col">{{ __('Who') }}</th><th scope="col">{{ __('What') }}</th><th scope="col">{{ __('Sarafi') }}</th><th scope="col">{{ __('From') }}</th></tr></thead>
            <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td dir="ltr">{{ $entry->created_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $entry->user?->name ?? '—' }}</td>
                    <td>{{ enum_label($entry->action) }}@if ($entry->details)<br><small class="text-muted" dir="ltr">{{ \Illuminate\Support\Str::limit(json_encode($entry->details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 90) }}</small>@endif</td>
                    <td>@if ($entry->tenant)<a href="{{ route('platform.tenants.show', $entry->tenant) }}">{{ $entry->tenant->name }}</a>@else — @endif</td>
                    <td dir="ltr">{{ $entry->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-5">{{ __('Nothing yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($entries->hasPages())
        <div class="card-footer">{{ $entries->links('partials.pagination') }}</div>
    @endif
</div>
@endsection
