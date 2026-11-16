@extends('layouts.admin.base')
@section('title', __('access.online_title'))

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('ui.blade_active_now') }}</h5>
  </div>
  <div class="card-body">
    <ul class="list-group list-group-flush">
      @forelse($users as $user)
      <li class="list-group-item d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <div class="rounded-circle bg-success me-3" style="width:12px;height:12px;"></div>
          <div>
            <strong>{{ $user->name }}</strong>
            <div class="text-muted small">{{ '@' . $user->username }}</div>
          </div>
        </div>
        <span class="text-muted small">
          {{ $user->lastSeen() ? \Carbon\Carbon::parse($user->lastSeen())->locale(app()->getLocale())->diffForHumans() : __('ui.blade_active_now') }}
        </span>
      </li>
      @empty
      <li class="list-group-item text-muted text-center">{{ __('ui.blade_no_one_is_online_right_now') }}</li>
      @endforelse
    </ul>
  </div>
</div>
@endsection
