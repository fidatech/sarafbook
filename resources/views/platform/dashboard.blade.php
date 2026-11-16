@extends('layouts.platform')
@section('title', __('Dashboard'))

@section('content')
<x-page-header :title="__('Platform')" icon="gauge-high" :subtitle="__('Every Sarafi on this server, its plan and its subscription.')">
    <a class="btn btn-primary" href="{{ route('platform.tenants.index') }}#new"><x-icon name="plus" /> {{ __('New Sarafi') }}</a>
</x-page-header>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><x-stat :label="__('Sarafis')" :value="$total" icon="building-columns" tone="blue" :href="route('platform.tenants.index')" /></div>
    <div class="col-6 col-xl-3"><x-stat :label="__('Active')" :value="$active" icon="circle-check" tone="green" :href="route('platform.tenants.index', ['status' => 'active'])" /></div>
    <div class="col-6 col-xl-3"><x-stat :label="__('On trial')" :value="$trial" icon="hourglass-half" tone="amber" :href="route('platform.tenants.index', ['status' => 'trial'])" /></div>
    <div class="col-6 col-xl-3"><x-stat :label="__('Suspended')" :value="$suspended" icon="ban" tone="red" :href="route('platform.tenants.index', ['status' => 'suspended'])" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h2 class="card-title h6 mb-0">{{ __('Ending within 30 days, or already ended') }}</h2></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th scope="col">{{ __('Sarafi') }}</th><th scope="col">{{ __('Plan') }}</th><th scope="col">{{ __('Valid until') }}</th><th scope="col">{{ __('Days') }}</th></tr></thead>
                    <tbody>
                    @forelse ($expiring as $tenant)
                        @php $days = $tenant->daysLeft(); @endphp
                        <tr>
                            <td><a href="{{ route('platform.tenants.show', $tenant) }}"><strong>{{ $tenant->name }}</strong></a><br><small class="text-muted" dir="ltr">{{ $tenant->primaryDomain() }}</small></td>
                            <td>{{ $tenant->plan?->name ?? '—' }}</td>
                            <td dir="ltr">{{ $tenant->validUntil()?->toDateString() }}</td>
                            <td><span class="badge-soft {{ $days < 0 ? 'danger' : 'warning' }}">{{ $days }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">{{ __('Nothing is ending soon.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h2 class="card-title h6 mb-0">{{ __('Received this year') }}</h2></div>
            <div class="card-body">
                @forelse ($incomeYear as $currency => $sum)
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">{{ $currency }}</span><strong dir="ltr">{{ number_format((float) $sum, 2) }}</strong></div>
                @empty
                    <p class="text-muted mb-0">{{ __('No payment recorded yet.') }}</p>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title h6 mb-0">{{ __('Newest') }}</h2></div>
            <ul class="list-group list-group-flush">
                @forelse ($recent as $tenant)
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <a href="{{ route('platform.tenants.show', $tenant) }}">{{ $tenant->name }}</a>
                        <span class="badge-soft {{ ['active' => 'success', 'trial' => 'warning', 'suspended' => 'danger'][$tenant->status] ?? 'secondary' }}">{{ enum_label($tenant->status) }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">{{ __('No Sarafi yet.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
