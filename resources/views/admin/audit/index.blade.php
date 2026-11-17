@extends('layouts.admin.base')
@section('title', __('ui.audit_logs'))
@section('modern', '1')

{{-- Who changed what, newest first. Filters by user, part of the system, kind of change and day. --}}
@php
    $filtered = $filters['user_id'] || $filters['model'] !== '' || $filters['event'] !== '' || $filters['date'] !== '' || $filters['q'] !== '';
    $events = __('administration.events');
    $modelNames = __('administration.audit_models');
    $tone = ['created' => 'success', 'updated' => 'warning', 'deleted' => 'danger'];
@endphp

@section('content')
<x-page-header :title="__('ui.audit_logs')" icon="clock-rotate-left" :subtitle="__('Who changed what, and when.')" />

<div class="card">
    <div class="card-body">
        <form method="GET" class="list-tools" role="search" data-live="list">
            <input type="search" class="form-control grow" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Search the descriptions…') }}" aria-label="{{ __('ui.search') }}">
            <select class="form-select" name="user_id" aria-label="{{ __('User') }}">
                <option value="">{{ __('Everyone') }}</option>
                @foreach ($users as $u)<option value="{{ $u->id }}" @selected($filters['user_id'] === $u->id)>{{ $u->name }}</option>@endforeach
            </select>
            <select class="form-select" name="model" aria-label="{{ __('Part of the system') }}">
                <option value="">{{ __('Everything') }}</option>
                @foreach ($models as $m)<option value="{{ $m }}" @selected($filters['model'] === $m)>{{ is_array($modelNames) ? ($modelNames[$m] ?? $m) : $m }}</option>@endforeach
            </select>
            <select class="form-select" name="event" aria-label="{{ __('Change') }}">
                <option value="">{{ __('Any change') }}</option>
                @foreach (['created', 'updated', 'deleted'] as $e)<option value="{{ $e }}" @selected($filters['event'] === $e)>{{ is_array($events) ? ($events[$e] ?? $e) : $e }}</option>@endforeach
            </select>
            <input type="date" class="form-control" name="date" value="{{ $filters['date'] }}" aria-label="{{ __('ui.date') }}" max="{{ now()->toDateString() }}">
            <a class="btn btn-light-secondary" href="{{ route('admin.audit.index') }}" data-live-clear title="{{ __('Clear the filters') }}" aria-label="{{ __('Clear the filters') }}" @if (! $filtered) hidden @endif><x-icon name="filter-circle-xmark" /></a>
        </form>

@fragment('list')
<div data-live-part="list" data-filtered="{{ $filtered ? 1 : 0 }}">
        @if ($logs->isEmpty())
            <div class="empty-state">
                <div class="es-icon"><x-icon name="clock-rotate-left" /></div>
                <h2 class="h3">{{ $filtered ? __('Nothing found') : __('Nothing recorded yet') }}</h2>
            </div>
        @else
            <div class="table-responsive data-wrap">
                <table class="table w-100 mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('When') }}</th>
                            <th scope="col">{{ __('Who') }}</th>
                            <th scope="col">{{ __('Change') }}</th>
                            <th scope="col">{{ __('ui.description') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php $props = $log->properties ? $log->properties->toArray() : []; @endphp
                            <tr>
                                <td class="text-nowrap"><span title="{{ $log->created_at }}">{{ $log->created_at->format('Y-m-d H:i') }}</span><div class="row-meta">{{ $log->created_at->locale(app()->getLocale())->diffForHumans() }}</div></td>
                                <td class="text-nowrap">{{ $log->causer->name ?? '—' }}</td>
                                <td class="text-nowrap">
                                    <span class="badge-soft {{ $tone[$log->event] ?? 'secondary' }}">{{ is_array($events) ? ($events[$log->event] ?? $log->event) : $log->event }}</span>
                                    <div class="row-meta">{{ is_array($modelNames) ? ($modelNames[$log->log_name] ?? $log->log_name) : $log->log_name }}</div>
                                </td>
                                <td>
                                    {{ $log->description }}
                                    @if ($props)
                                        <details class="small mt-1">
                                            <summary class="text-muted">{{ __('Details') }}</summary>
                                            <dl class="row mb-0 mt-1">
                                                @foreach ($props as $key => $value)
                                                    <dt class="col-sm-4 text-muted fw-normal">{{ $key }}</dt>
                                                    <dd class="col-sm-8 mb-1" dir="auto">{{ is_scalar($value) || $value === null ? ($value ?? '—') : json_encode($value, JSON_UNESCAPED_UNICODE) }}</dd>
                                                @endforeach
                                            </dl>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $logs->links('partials.pagination') }}
        @endif
</div>
@endfragment
    </div>
</div>
@endsection
