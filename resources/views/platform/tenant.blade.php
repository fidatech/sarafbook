@extends('layouts.platform')
@section('title', $tenant->name)

@php
    $tone = ['active' => 'success', 'trial' => 'warning', 'suspended' => 'danger'][$tenant->status] ?? 'secondary';
    $days = $tenant->daysLeft();
    $daysNote = $days === null ? null : ($days < 0 ? __('Ended') : ($days === 0 ? __('Ends today') : __(':count days left', ['count' => $days])));
    $featureNames = \App\Http\Controllers\Platform\PlanController::FEATURES;
    $own = (array) $tenant->features;
    $hasKey = ! empty(($tenant->settings ?? [])['services.wasender.key']);
@endphp

@section('content')
<x-page-header :title="$tenant->name" icon="building-columns" :crumbs="[[__('Platform'), route('platform.dashboard')], [__('Sarafis'), route('platform.tenants.index')], [$tenant->name]]">
    @unless ($tenant->isSuspended())
        <form method="post" action="{{ route('platform.tenants.enter', $tenant) }}" target="_blank" data-confirm="{{ __('Sign in as this Sarafi?') }}" data-confirm-text="{{ __('This is written to the audit log.') }}" data-confirm-ok="{{ __('Sign in') }}" data-confirm-style="primary">@csrf
            <button class="btn btn-soft-primary" type="submit"><x-icon name="user-shield" /> {{ __('Sign in as this Sarafi') }}</button>
        </form>
    @endunless
    <a class="btn btn-light-secondary" href="{{ $tenant->url() }}" target="_blank" rel="noopener"><x-icon name="arrow-up-right-from-square" /> {{ __('Open address') }}</a>
</x-page-header>

@if (session('first_login'))
    <div class="alert alert-success" role="status">
        <strong>{{ __('First sign-in of the administrator. This password is shown only now; give it to the owner and ask him to change it.') }}</strong>
        <div class="mt-2" dir="ltr">{{ __('ui.username') }}: <code>{{ session('first_login.username') }}</code> &nbsp; {{ __('ui.password') }}: <code>{{ session('first_login.password') }}</code></div>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><x-stat :label="__('Status')" :value="enum_label($tenant->status)" icon="circle-info" :tone="['success' => 'green', 'warning' => 'amber', 'danger' => 'red'][$tone] ?? 'blue'" :note="$tenant->isReadOnly() ? __('Read only') : null" /></div>
    <div class="col-6 col-xl-3"><x-stat :label="__('Valid until')" :value="$tenant->validUntil()?->toDateString() ?? '—'" icon="calendar-check" tone="blue" :note="$daysNote" /></div>
    <div class="col-6 col-xl-3"><x-stat :label="__('Users')" :value="$stats ? $stats['users'].($tenant->maxUsers() ? ' / '.$tenant->maxUsers() : '') : '—'" icon="users" tone="violet" /></div>
    <div class="col-6 col-xl-3"><x-stat :label="__('Database')" :value="$stats && $stats['size_mb'] !== null ? $stats['size_mb'].' MB' : '—'" icon="database" tone="green" :note="$tenant->database" /></div>
</div>

@unless ($stats)
    <div class="alert alert-danger" role="alert">{{ __('The database of this Sarafi could not be reached. Check that it exists and that the server can open it.') }}</div>
@endunless

<div class="row g-3">
    <div class="col-xl-7">
        <form class="card mb-3" method="post" action="{{ route('platform.tenants.update', $tenant) }}">
            @csrf @method('put')
            <div class="card-header"><h2 class="card-title h6 mb-0">{{ __('Details and plan') }}</h2></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-7"><label class="form-label" for="name">{{ __('Name of the Sarafi') }}</label><input class="form-control" id="name" name="name" value="{{ old('name', $tenant->name) }}" maxlength="120" required></div>
                    <div class="col-md-5"><label class="form-label" for="plan_id">{{ __('Plan') }}</label>
                        <select class="form-select" id="plan_id" name="plan_id"><option value="">—</option>
                            @foreach ($plans as $plan)<option value="{{ $plan->id }}" @selected((string) old('plan_id', $tenant->plan_id) === (string) $plan->id)>{{ $plan->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label" for="expires_at">{{ __('Paid until') }}</label><input class="form-control" type="date" id="expires_at" name="expires_at" value="{{ old('expires_at', $tenant->expires_at?->toDateString()) }}"></div>
                    <div class="col-md-4"><label class="form-label" for="trial_ends_at">{{ __('Trial until') }}</label><input class="form-control" type="date" id="trial_ends_at" name="trial_ends_at" value="{{ old('trial_ends_at', $tenant->trial_ends_at?->toDateString()) }}"></div>
                    <div class="col-md-4"><label class="form-label" for="release">{{ __('Version') }}</label><input class="form-control" id="release" name="release" value="{{ old('release', $tenant->release) }}" dir="ltr" maxlength="32" required aria-describedby="release-help"><div class="form-text" id="release-help">{{ __('"stable", or the name of this Sarafi\'s own pinned release.') }}</div></div>
                    <div class="col-md-6"><label class="form-label" for="contact_name">{{ __('Owner') }}</label><input class="form-control" id="contact_name" name="contact_name" value="{{ old('contact_name', $tenant->contact_name) }}" maxlength="120"></div>
                    <div class="col-md-6"><label class="form-label" for="contact_phone">{{ __('Phone') }}</label><input class="form-control" id="contact_phone" name="contact_phone" type="tel" dir="ltr" value="{{ old('contact_phone', $tenant->contact_phone) }}" maxlength="32"></div>
                    <div class="col-12">
                        <fieldset>
                            <legend class="form-label">{{ __('Feature switches for this Sarafi only') }}</legend>
                            <div class="row g-2">
                                @foreach ($featureNames as $key => $label)
                                    <div class="col-md-6"><label class="form-label small mb-1" for="f-{{ $key }}">{{ __($label) }}</label>
                                        <select class="form-select form-select-sm" id="f-{{ $key }}" name="features[{{ $key }}]" @if (! array_key_exists($key, $own)) data-plan @endif>
                                            <option value="" @selected(! array_key_exists($key, $own))>{{ __('As in the plan') }}</option>
                                            <option value="1" @selected(($own[$key] ?? null) === true)>{{ __('On') }}</option>
                                            <option value="0" @selected(($own[$key] ?? null) === false)>{{ __('Off') }}</option>
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-12"><label class="form-label" for="wasender_key">{{ __('Own WhatsApp sender key') }}</label><input class="form-control" id="wasender_key" name="wasender_key" type="password" dir="ltr" autocomplete="off" placeholder="{{ $hasKey ? __('A key is saved. Type a new one to replace it.') : __('Empty: the key of the platform is used.') }}">
                        @if ($hasKey)<div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="wasender_clear" name="wasender_clear" value="1"><label class="form-check-label" for="wasender_clear">{{ __('Remove the saved key') }}</label></div>@endif
                    </div>
                    <div class="col-12"><label class="form-label" for="notes">{{ __('Notes') }}</label><textarea class="form-control" id="notes" name="notes" rows="2" maxlength="2000">{{ old('notes', $tenant->notes) }}</textarea></div>
                </div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary" type="submit">{{ __('Save') }}</button></div>
        </form>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center"><h2 class="card-title h6 mb-0">{{ __('Payments') }}</h2><button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#newPayment"><x-icon name="plus" /> {{ __('Record a renewal') }}</button></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th scope="col">{{ __('Paid on') }}</th><th scope="col">{{ __('Amount') }}</th><th scope="col">{{ __('Method') }}</th><th scope="col">{{ __('Until') }}</th><th scope="col">{{ __('Recorded by') }}</th></tr></thead>
                    <tbody>
                    @forelse ($tenant->payments->sortByDesc('paid_at') as $payment)
                        <tr><td dir="ltr">{{ $payment->paid_at->toDateString() }}</td><td dir="ltr">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td><td>{{ enum_label($payment->method) }}@if ($payment->reference)<br><small class="text-muted" dir="ltr">{{ $payment->reference }}</small>@endif</td><td dir="ltr">{{ $payment->period_end?->toDateString() }}</td><td>{{ $payment->recorder?->name ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No payment recorded yet.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card mb-3">
            <div class="card-header"><h2 class="card-title h6 mb-0">{{ __('Status') }}</h2></div>
            <div class="card-body d-flex flex-wrap gap-2">
                @foreach (['active' => ['Activate', 'success'], 'trial' => ['Set to trial', 'warning'], 'suspended' => ['Suspend', 'danger']] as $status => [$label, $style])
                    @continue($tenant->status === $status)
                    <form method="post" action="{{ route('platform.tenants.status', $tenant) }}" @if ($status === 'suspended') data-confirm="{{ __('Suspend this Sarafi?') }}" data-confirm-text="{{ __('Nobody can sign in until it is activated again. No data is removed.') }}" data-confirm-ok="{{ __('Suspend') }}" @endif>@csrf
                        <input type="hidden" name="status" value="{{ $status }}"><button class="btn btn-soft-{{ $style }}" type="submit">{{ __($label) }}</button>
                    </form>
                @endforeach
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h2 class="card-title h6 mb-0">{{ __('Addresses') }}</h2></div>
            <ul class="list-group list-group-flush">
                @foreach ($tenant->domains as $domain)
                    <li class="list-group-item d-flex justify-content-between align-items-center"><span dir="ltr">{{ $domain->domain }}</span>@if ($domain->is_primary)<span class="badge-soft primary">{{ __('Main') }}</span>@endif</li>
                @endforeach
            </ul>
            <form class="card-body border-top" method="post" action="{{ route('platform.tenants.domain', $tenant) }}">@csrf
                <label class="form-label" for="domain">{{ __('Add an address') }}</label>
                <div class="input-group"><input class="form-control" id="domain" name="domain" dir="ltr" placeholder="sarafi-example.af" autocapitalize="none" spellcheck="false" required><button class="btn btn-soft-primary" type="submit">{{ __('Add') }}</button></div>
                <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="is_primary" name="is_primary" value="1"><label class="form-check-label" for="is_primary">{{ __('Make it the main address') }}</label></div>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title h6 mb-0">{{ __('Database and own changes') }}</h2></div>
            <div class="card-body">
                <dl class="row mb-3 small">
                    <dt class="col-5 text-muted fw-normal">{{ __('Database') }}</dt><dd class="col-7" dir="ltr">{{ $tenant->database }}</dd>
                    <dt class="col-5 text-muted fw-normal">{{ __('Accounts') }}</dt><dd class="col-7" dir="ltr">{{ $stats ? number_format($stats['accounts']) : '—' }}</dd>
                    <dt class="col-5 text-muted fw-normal">{{ __('Transactions') }}</dt><dd class="col-7" dir="ltr">{{ $stats ? number_format($stats['transactions']) : '—' }}</dd>
                    <dt class="col-5 text-muted fw-normal">{{ __('Last entry') }}</dt><dd class="col-7" dir="ltr">{{ $stats['last_entry'] ?? '—' }}</dd>
                    <dt class="col-5 text-muted fw-normal">{{ __('Own layer') }}</dt><dd class="col-7">@if ($hasOverrides)<span class="badge-soft info" dir="ltr">tenants/{{ $tenant->slug }}</span>@else{{ __('None: this Sarafi runs the main system as it is.') }}@endif</dd>
                </dl>
                <form method="post" action="{{ route('platform.tenants.migrate', $tenant) }}">@csrf<button class="btn btn-light-secondary" type="submit"><x-icon name="rotate" /> {{ __('Update the database of this Sarafi') }}</button></form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newPayment" tabindex="-1" aria-labelledby="newPaymentTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="{{ route('platform.tenants.payment', $tenant) }}">@csrf
            <div class="modal-header"><h2 class="modal-title h5" id="newPaymentTitle">{{ __('Record a renewal') }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-7"><label class="form-label" for="amount">{{ __('Amount') }}</label><input class="form-control" id="amount" name="amount" inputmode="decimal" dir="ltr" value="{{ old('amount', $tenant->plan?->price_yearly) }}" required></div>
                    <div class="col-5"><label class="form-label" for="currency">{{ __('Currency') }}</label><select class="form-select" id="currency" name="currency">@foreach (['AFN', 'USD'] as $c)<option @selected(old('currency', $tenant->plan?->currency ?? 'AFN') === $c)>{{ $c }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label" for="method">{{ __('Method') }}</label><select class="form-select" id="method" name="method">@foreach (['cash', 'bank', 'hesabpay', 'hawala', 'other'] as $m)<option value="{{ $m }}">{{ enum_label($m) }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label" for="reference">{{ __('Reference') }}</label><input class="form-control" id="reference" name="reference" dir="ltr" maxlength="120"></div>
                    <div class="col-6"><label class="form-label" for="paid_at">{{ __('Paid on') }}</label><input class="form-control" type="date" id="paid_at" name="paid_at" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-6"><label class="form-label" for="period_end">{{ __('Subscription until') }}</label><input class="form-control" type="date" id="period_end" name="period_end" value="{{ ($tenant->expires_at && $tenant->expires_at->isFuture() ? $tenant->expires_at : now())->copy()->addYear()->toDateString() }}" required></div>
                    <div class="col-12"><label class="form-label" for="note">{{ __('Note') }}</label><input class="form-control" id="note" name="note" maxlength="500"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('Save') }}</button></div>
        </form>
    </div>
</div>
@endsection
