@extends('layouts.admin.base')
@section('title', __('remittance_payment.title'))
@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/remittance-payment.css') }}?v=2">
@endsection
@section('content')
@php
    $canPay = $remittance->type === 'incoming' && $remittance->status === 'pending' && !$remittance->paid_at && $remittance->remittance_mode === 'no_track';
    $money = fn ($amount, $currency) => $amount === null ? '—' : number_format((float) $amount, 2).' '.($currency?->code ?? '');
    $transfer = [
        __('ui.date') => $remittance->date?->format('Y-m-d'),
        __('ui.type') => __('ui.'.$remittance->type),
        __('remittance_payment.mode') => __('ui.'.$remittance->remittance_mode),
        __('remittance_payment.track_account') => $remittance->trackAccount?->name,
        __('remittance_payment.track_number') => $remittance->track_hawala_number,
        __('ui.exchange_rate') => $remittance->exchange_rate,
        __('remittance_payment.exchange_mode') => $remittance->exchange_mode ? __('remittance_payment.'.$remittance->exchange_mode) : '—',
    ];
@endphp
<div class="remittance-payment">
    <header class="rp-header">
        <div><p class="rp-eyebrow">{{ __('remittance_payment.payment_desk') }}</p><h4>{{ $remittance->paid_at ? __('remittance_payment.record') : __('remittance_payment.title') }}</h4><p class="text-muted mb-0">{{ __('remittance_payment.review_hint') }}</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.remittances.pendingIncoming') }}">{{ __('remittance_payment.back') }}</a>
    </header>
    @if($errors->any())
        <div class="alert alert-danger" role="alert"><p>{{ __('remittance_payment.reselect') }}</p><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div class="rp-layout">
        <section class="rp-details" aria-label="{{ __('remittance_payment.details') }}">
            <div class="rp-summary">
                <div class="rp-summary-top"><span>{{ __('remittance_payment.hawala_number') }} <b dir="auto">#{{ $remittance->hawala_number ?: $remittance->id }}</b></span><span class="rp-status">{{ __('remittance_payment.'.$remittance->status) }}</span></div>
                <p class="rp-summary-label">{{ __('remittance_payment.payout') }}</p>
                <div class="rp-total" dir="ltr">{{ $money($remittance->partner_amount, $remittance->partnerCurrency) }}</div>
                <div class="rp-summary-bottom"><span>{{ __('ui.receiver') }} <strong>{{ $remittance->receiver ?: '—' }}</strong></span><span>{{ $remittance->date?->format('Y-m-d') }}</span></div>
            </div>
            <div class="rp-panel rp-people">
                @foreach(['sender' => ['from', $remittance->fromAccount], 'receiver' => ['to', $remittance->toAccount]] as $person => [$direction, $account])
                    <div class="rp-person"><div class="rp-avatar" aria-hidden="true">{{ mb_substr($remittance->{$person} ?: '—', 0, 1) }}</div><div><span class="rp-label">{{ __('ui.'.$person) }}</span><h5>{{ $remittance->{$person} ?: '—' }}</h5><p>{{ __('remittance_payment.'.$direction) }}: <strong>{{ $account?->name ?: '—' }}</strong></p></div></div>
                @endforeach
            </div>
            <div class="rp-panel">
                <h5 class="rp-section-title">{{ __('remittance_payment.transfer_info') }}</h5>
                <dl class="rp-info-grid">@foreach($transfer as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value ?? '—' }}</dd></div>@endforeach</dl>
                <h5 class="rp-section-title rp-divider">{{ __('remittance_payment.amounts') }}</h5>
                <div class="rp-amounts">
                    @foreach([__('ui.amount') => [$remittance->amount, $remittance->currency], __('remittance_payment.partner_amount') => [$remittance->partner_amount, $remittance->partnerCurrency], __('remittance_payment.actual_amount') => [$remittance->actual_amount, $remittance->actualCurrency]] as $label => [$amount, $currency])
                        <div><span class="rp-label">{{ $label }}</span><strong dir="ltr">{{ $money($amount, $currency) }}</strong></div>
                    @endforeach
                </div>
                <div class="rp-commission"><h6>{{ __('remittance_payment.commission_details') }}</h6><dl>
                    <div><dt>{{ __('ui.commission_mode') }}</dt><dd>{{ $remittance->commission_mode === 'company' ? __('remittances.company') : __('remittance_payment.'.$remittance->commission_mode) }}</dd></div>
                    <div><dt>{{ __('remittance_payment.company_commission') }}</dt><dd dir="ltr">{{ $money($remittance->commission_kabul_amount, $remittance->commissionCurrency) }}</dd></div>
                    <div><dt>{{ __('remittance_payment.partner_commission') }}</dt><dd dir="ltr">{{ $money($remittance->commission_partner_amount, $remittance->commissionCurrency) }}</dd></div>
                </dl></div>
                <div class="rp-note"><span class="rp-label">{{ __('ui.description') }}</span><p>{{ $remittance->description ?: '—' }}</p></div>
                <div class="rp-audit"><span>{{ __('remittance_payment.creator') }}: <b>{{ $remittance->createdBy?->name ?: '—' }}</b></span><span>{{ __('remittance_payment.created_at') }}: {{ $remittance->created_at?->format('Y-m-d H:i') }}</span></div>
            </div>
        </section>
        <section class="rp-panel rp-evidence" aria-label="{{ __('remittance_payment.evidence') }}">
            <h5 class="rp-section-title">{{ __('remittance_payment.evidence') }}</h5>
            @if($canPay)
                <form id="payment-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.remittances.markPaid', $remittance) }}">
                    @csrf
                    <div class="rp-document">
                        <h6><span class="rp-step">1</span>{{ __('remittance_payment.photo') }}</h6>
                        <div class="rp-photo-preview" id="photo-preview-area"><img id="preview-photo" alt="{{ __('remittance_payment.photo') }}" hidden><div id="photo-placeholder"><svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 7h3l2-3h6l2 3h3v13H4z"/><circle cx="12" cy="13" r="4"/></svg><p>{{ __('remittance_payment.photo_empty') }}</p></div></div>
                        <div class="rp-photo-actions"><button type="button" class="btn btn-primary" id="open-camera">{{ __('remittance_payment.camera') }}</button><button type="button" class="btn btn-outline-secondary" id="edit-photo" disabled>{{ __('remittance_payment.edit_photo') }}</button></div>
                        <label class="visually-hidden" for="receiver_photo">{{ __('remittance_payment.photo') }}</label>
                        <input type="file" class="form-control" id="receiver_photo" name="receiver_photo" accept="image/jpeg,image/png,image/webp" aria-describedby="photo-help photo-status">
                        <p class="form-text" id="photo-help">{{ __('remittance_payment.photo_help') }}</p><p id="photo-status" class="small mb-0" role="status" aria-live="polite"></p>
                    </div>
                    <div class="rp-document">
                        <h6><span class="rp-step">2</span><label for="receiver_id_copy" class="mb-0">{{ __('remittance_payment.id_copy') }}</label></h6>
                        <input type="file" class="form-control" id="receiver_id_copy" name="receiver_id_copy" accept="image/jpeg,image/png,image/webp,application/pdf" aria-describedby="id-help">
                        <p class="form-text" id="id-help">{{ __('remittance_payment.id_help') }}</p><img id="preview-id-copy" class="rp-id-preview" alt="{{ __('remittance_payment.id_copy') }}" hidden>
                        <label class="form-label mt-2" for="receiver_id_number">{{ __('remittance_payment.id_number') }}</label><input class="form-control" id="receiver_id_number" name="receiver_id_number" maxlength="100" value="{{ old('receiver_id_number') }}">
                    </div>
                    <fieldset class="rp-fingerprint" disabled aria-describedby="fingerprint-help"><legend>{{ __('remittance_payment.fingerprint') }} <span>{{ __('remittance_payment.disabled') }}</span></legend><input type="file" class="form-control" id="receiver_fingerprint" disabled accept="image/*" aria-label="{{ __('remittance_payment.fingerprint') }}"><p class="form-text mb-0" id="fingerprint-help">{{ __('remittance_payment.fingerprint_disabled') }}</p></fieldset>
                    <div class="form-check my-4"><input class="form-check-input" type="checkbox" name="identity_confirmed" id="identity_confirmed" value="1" required><label class="form-check-label" for="identity_confirmed">{{ __('remittance_payment.confirm') }}</label></div>
                    <button id="pay-button" class="btn btn-success w-100" type="submit">{{ __('remittance_payment.submit') }}</button>
                </form>
            @elseif($remittance->paid_at)
                <a class="btn btn-primary w-100 mb-3" href="{{ route('admin.remittances.payment-receipt', $remittance) }}">{{ __('remittance_payment.print_receipt') }}</a>
                <div class="rp-paid-photo"><img src="{{ route('admin.remittances.payment-evidence', [$remittance, 'photo']) }}" alt="{{ __('remittance_payment.photo') }}"></div>
                <dl class="rp-info-grid"><div><dt>{{ __('remittance_payment.paid_by') }}</dt><dd>{{ $remittance->paidBy?->name ?: '—' }}</dd></div><div><dt>{{ __('remittance_payment.paid_at') }}</dt><dd>{{ $remittance->paid_at->format('Y-m-d H:i') }}</dd></div><div><dt>{{ __('remittance_payment.id_number') }}</dt><dd>{{ $remittance->receiver_id_number ?: '—' }}</dd></div></dl>
                @foreach(['id_copy' => 'id-copy', 'fingerprint' => 'fingerprint', 'photo' => 'photo'] as $field => $kind)
                    @if($remittance->{'receiver_'.$field.'_path'})<a class="btn btn-outline-primary w-100 mb-2" target="_blank" rel="noopener" href="{{ route('admin.remittances.payment-evidence', [$remittance, $kind]) }}">{{ __('remittance_payment.open') }}: {{ __('remittance_payment.'.$field) }}</a>@endif
                @endforeach
            @else
                <div class="alert alert-warning">{{ __('remittance_payment.only_pending') }}</div>
            @endif
        </section>
    </div>
</div>
@if($canPay)
<div class="modal rp-photo-modal" id="photo-studio" tabindex="-1" aria-labelledby="photo-studio-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="photo-studio-title">{{ __('remittance_payment.editor') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('remittance_payment.cancel') }}"></button></div>
        <div class="modal-body">
            <p id="studio-status" role="status" aria-live="polite"></p>
            <div id="camera-panel"><video id="receiver-camera" autoplay muted playsinline aria-label="{{ __('remittance_payment.camera') }}"></video><div class="rp-photo-actions"><button type="button" class="btn btn-primary" id="capture-photo" disabled>{{ __('remittance_payment.capture') }}</button><label class="btn btn-outline-secondary mb-0" for="native-camera">{{ __('remittance_payment.native_camera') }}</label><input type="file" id="native-camera" class="visually-hidden" accept="image/jpeg,image/png,image/webp" capture="user"></div></div>
            <div id="editor-panel" hidden>
                <p class="small text-muted" id="crop-help">{{ __('remittance_payment.crop_help') }}</p>
                <div class="rp-editor-layout"><div class="rp-crop-stage"><canvas id="photo-canvas" width="720" height="960" tabindex="0" role="img" aria-label="{{ __('remittance_payment.photo') }}" aria-describedby="crop-help"></canvas></div><div class="rp-editor-controls">
                    @foreach(['zoom' => [100, 300, 100], 'brightness' => [60, 140, 100], 'contrast' => [60, 140, 100]] as $control => [$min, $max, $value])
                        <label for="photo-{{ $control }}">{{ __('remittance_payment.'.$control) }} <output id="value-{{ $control }}">100%</output></label><input class="form-range" type="range" id="photo-{{ $control }}" min="{{ $min }}" max="{{ $max }}" value="{{ $value }}" dir="ltr">
                    @endforeach
                    <button type="button" class="btn btn-outline-secondary" id="rotate-photo">{{ __('remittance_payment.rotate') }}</button><button type="button" class="btn btn-outline-secondary" id="reset-photo">{{ __('remittance_payment.reset') }}</button><button type="button" class="btn btn-outline-primary" id="retake-photo">{{ __('remittance_payment.retake') }}</button>
                </div></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('remittance_payment.cancel') }}</button><button type="button" class="btn btn-primary" id="apply-photo" disabled>{{ __('remittance_payment.apply') }}</button></div>
    </div></div>
</div>
@endif
@endsection
@section('js')
<script>window.remittancePhotoText = @json(__('remittance_payment'));</script>
<script src="{{ asset('assets/js/remittance-payment.js') }}?v=2"></script>
@endsection
