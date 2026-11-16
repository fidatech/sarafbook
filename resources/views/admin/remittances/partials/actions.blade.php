@php
    $canPay = $r->status === 'pending' && $r->type === 'incoming' && auth()->user()->can('update remittances');
@endphp

<div class="btn-group">
    <button type="button"
            class="btn btn-sm btn-outline-success btn-remittance-whatsapp"
            data-url="{{ route('admin.remittances.send-whatsapp', $r->id) }}"
            title="{{ __('remittances.send_whatsapp') }}"
            data-bs-toggle="tooltip">
        <i class="bi bi-whatsapp"></i>
    </button>

    <a href="{{ route($r->paid_at && auth()->user()->can('update remittances') ? 'admin.remittances.payment-receipt' : 'admin.remittances.print', $r->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark" title="{{ __('remittances.print') }}" aria-label="{{ __('remittances.print') }}">
        <i class="bi bi-printer"></i>
    </a>

    @if($canPay)
        <a href="{{ route('admin.remittances.payment', $r) }}" class="btn btn-sm btn-success" title="{{ __('remittance_payment.title') }}" aria-label="{{ __('remittance_payment.title') }}">
            <i class="bi bi-check2-circle"></i>
        </a>
    @endif

    @if($r->paid_at && auth()->user()->can('update remittances'))
        <a href="{{ route('admin.remittances.payment', $r) }}" class="btn btn-sm btn-outline-success" title="{{ __('remittance_payment.record') }}" aria-label="{{ __('remittance_payment.record') }}"><i class="bi bi-person-check"></i></a>
    @endif

    @if($r->status !== 'rejected' && !$r->paid_at)
        <button type="button" class="btn btn-sm btn-warning btn-reject" title="{{ __('remittances.reject') }}" aria-label="{{ __('remittances.reject') }}" data-id="{{ $r->id }}">
            <i class="bi bi-x-circle"></i>
        </button>
    @endif

    @if(!$r->paid_at)
    <button type="button" class="btn btn-sm btn-danger btn-delete" title="{{ __('remittances.delete') }}" aria-label="{{ __('remittances.delete') }}" data-id="{{ $r->id }}">
        <i class="bi bi-trash"></i>
    </button>
    @endif
</div>
