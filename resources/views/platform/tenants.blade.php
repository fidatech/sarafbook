@extends('layouts.platform')
@section('title', __('Sarafis'))

@section('content')
<x-page-header :title="__('Sarafis')" icon="building-columns" :crumbs="[[__('Platform'), route('platform.dashboard')], [__('Sarafis')]]">
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#newTenant"><x-icon name="plus" /> {{ __('New Sarafi') }}</button>
</x-page-header>

<div class="card">
    <div class="card-body border-bottom">
        <form method="get" class="row g-2 align-items-end" role="search">
            <div class="col-sm-6 col-lg-5"><label class="form-label" for="q">{{ __('Search') }}</label><input class="form-control" type="search" id="q" name="q" value="{{ $search }}" placeholder="{{ __('Name, short name or address') }}"></div>
            <div class="col-sm-4 col-lg-3"><label class="form-label" for="status">{{ __('Status') }}</label>
                <select class="form-select" id="status" name="status">
                    <option value="">{{ __('All') }}</option>
                    @foreach (['active', 'trial', 'suspended'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ enum_label($option) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-light-secondary" type="submit"><x-icon name="filter" /> {{ __('Filter') }}</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th scope="col">{{ __('Sarafi') }}</th><th scope="col">{{ __('Address') }}</th><th scope="col">{{ __('Plan') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Valid until') }}</th><th scope="col">{{ __('Version') }}</th><th scope="col"><span class="visually-hidden">{{ __('Open') }}</span></th></tr></thead>
            <tbody>
            @forelse ($tenants as $tenant)
                <tr>
                    <td><a href="{{ route('platform.tenants.show', $tenant) }}"><strong>{{ $tenant->name }}</strong></a><br><small class="text-muted" dir="ltr">{{ $tenant->slug }}</small></td>
                    <td dir="ltr">{{ $tenant->primaryDomain() }}</td>
                    <td>{{ $tenant->plan?->name ?? '—' }}</td>
                    <td><span class="badge-soft {{ ['active' => 'success', 'trial' => 'warning', 'suspended' => 'danger'][$tenant->status] ?? 'secondary' }}">{{ enum_label($tenant->status) }}</span>@if ($tenant->isReadOnly()) <span class="badge-soft danger">{{ __('Read only') }}</span>@endif</td>
                    <td dir="ltr">{{ $tenant->validUntil()?->toDateString() ?? '—' }}</td>
                    <td dir="ltr">{{ $tenant->release }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-soft-primary" href="{{ route('platform.tenants.show', $tenant) }}">{{ __('Open') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-5">{{ __('No Sarafi found.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($tenants->hasPages())
        <div class="card-footer">{{ $tenants->links('partials.pagination') }}</div>
    @endif
</div>

<div class="modal fade" id="newTenant" tabindex="-1" aria-labelledby="newTenantTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" method="post" action="{{ route('platform.tenants.store') }}">
            @csrf
            <div class="modal-header"><h2 class="modal-title h5" id="newTenantTitle">{{ __('New Sarafi') }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-7"><label class="form-label" for="t-name">{{ __('Name of the Sarafi') }}</label><input class="form-control" id="t-name" name="name" value="{{ old('name') }}" maxlength="120" required></div>
                    <div class="col-md-5"><label class="form-label" for="t-slug">{{ __('Short name') }}</label><input class="form-control" id="t-slug" name="slug" value="{{ old('slug') }}" dir="ltr" pattern="[a-z0-9]([a-z0-9-]*[a-z0-9])?" maxlength="40" autocapitalize="none" spellcheck="false" required aria-describedby="t-slug-help"><div class="form-text" id="t-slug-help">{{ __('a-z, 0-9 and "-". The address becomes') }} <span dir="ltr"><span id="t-slug-preview">name</span>.{{ config('tenancy.base_domain') }}</span></div></div>
                    <div class="col-md-7"><label class="form-label" for="t-domain">{{ __('Own domain (optional)') }}</label><input class="form-control" id="t-domain" name="domain" value="{{ old('domain') }}" dir="ltr" placeholder="sarafi-example.af" autocapitalize="none" spellcheck="false"></div>
                    <div class="col-md-5"><label class="form-label" for="t-plan">{{ __('Plan') }}</label>
                        <select class="form-select" id="t-plan" name="plan_id"><option value="">—</option>
                            @foreach ($plans as $plan)<option value="{{ $plan->id }}" @selected((string) old('plan_id') === (string) $plan->id)>{{ $plan->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label" for="t-status">{{ __('Starts as') }}</label>
                        <select class="form-select" id="t-status" name="status"><option value="trial">{{ __('Trial') }} ({{ config('tenancy.trial_days') }} {{ __('days') }})</option><option value="active" @selected(old('status') === 'active')>{{ __('Active') }}</option></select>
                    </div>
                    <div class="col-md-4"><label class="form-label" for="t-expires">{{ __('Paid until') }}</label><input class="form-control" type="date" id="t-expires" name="expires_at" value="{{ old('expires_at') }}"></div>
                    <div class="col-md-4"><label class="form-label" for="t-locale">{{ __('Language') }}</label>
                        <select class="form-select" id="t-locale" name="locale">@foreach (config('app.locales') as $code => $language)<option value="{{ $code }}" @selected(old('locale', config('app.locale')) === $code)>{{ $language }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-4"><label class="form-label" for="t-contact">{{ __('Owner') }}</label><input class="form-control" id="t-contact" name="contact_name" value="{{ old('contact_name') }}" maxlength="120"></div>
                    <div class="col-md-4"><label class="form-label" for="t-phone">{{ __('Phone') }}</label><input class="form-control" id="t-phone" name="contact_phone" value="{{ old('contact_phone') }}" type="tel" dir="ltr" maxlength="32"></div>
                    <div class="col-md-4"><label class="form-label" for="t-admin">{{ __('Administrator username') }}</label><input class="form-control" id="t-admin" name="admin_username" value="{{ old('admin_username', 'admin') }}" dir="ltr" minlength="4" maxlength="40" autocapitalize="none" required></div>
                    <div class="col-12">
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="t-existing" name="existing_database" value="1" @checked(old('existing_database'))><label class="form-check-label" for="t-existing">{{ __('The database already exists (a client brought from an earlier installation). It is updated, not filled with starting data, and keeps its own users.') }}</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><span class="me-auto text-muted small">{{ __('The administrator password is shown once after the Sarafi is created.') }}</span><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('Create') }}</button></div>
        </form>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var slug = document.getElementById('t-slug'), preview = document.getElementById('t-slug-preview');
    slug.addEventListener('input', function () { slug.value = slug.value.toLowerCase().replace(/[^a-z0-9-]/g, ''); preview.textContent = slug.value || 'name'; });
    if (location.hash === '#new' || {{ $errors->any() || session('error') ? 'true' : 'false' }}) bootstrap.Modal.getOrCreateInstance(document.getElementById('newTenant')).show();
});
</script>
@endpush
@endsection
