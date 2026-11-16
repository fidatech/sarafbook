@extends('layouts.admin.base')

@section('title', 'Account Summary')

@section('css')
    <link rel="stylesheet"
        href="{{ asset('assets/offline/3972c57a70889695.css') }}">
<style>
    html { font-size: 15px; background: #f0f2f5; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

    .export-box {
        background: #fff;
        max-width: 750px;
        margin: 2rem auto;
        padding: 2rem 2.5rem;
        border-radius: 12px;
        box-shadow: 0 0 25px rgba(0, 0, 0, 0.05);
    }

    .export-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #ddd;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
    }

    .export-header img {
        height: 50px;
    }

    .export-header h4 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #1c2c52;
    }

    .section-card {
        margin-bottom: 1.5rem;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #0d6efd;
        margin-bottom: 1rem;
        border-bottom: 1px dashed #ccc;
        padding-bottom: 0.5rem;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f2f2f2;
    }

    .info-label {
        font-weight: 600;
        color: #333;
    }

    .info-value {
        color: #555;
        text-align: right;
    }

    .currency-summary-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .currency-summary-box {
        flex: 1 1 45%;
        border: 1px solid #bbb;
        border-radius: 6px;
        padding: 0.75rem;
        background-color: #f5f5f5;
    }

    .balance-positive { color: #28a745 !important; font-weight: bold; }
    .balance-negative { color: #dc3545 !important; font-weight: bold; }

    .export-footer {
        border-top: 1px solid #ddd;
        margin-top: 2rem;
        padding-top: 1rem;
        text-align: center;
        font-size: 0.85rem;
        color: #666;
    }

    .export-actions {
        max-width: 750px;
        margin: 1rem auto;
        text-align: right;
    }
</style>
@endsection

@section('content')
<div class="export-actions">
    <button class="btn btn-outline-primary btn-sm" id="exportImage"><i class="fas fa-image"></i> {{ __('ui.blade_export_image') }}</button>
    <button class="btn btn-outline-danger btn-sm" id="exportPdf"><i class="fas fa-file-pdf"></i> {{ __('ui.export_pdf') }}</button>
</div>

<div id="export-area" class="export-box">
    <div class="export-header">
        <img src="{{ asset('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}">
        <div>
            <h4>{{ __('ui.blade_account_summary_report') }}</h4>
            <div style="font-size: 0.9rem; color: #888;">{{ __('accounts.generated') }} {{ now()->format('Y-m-d H:i:s') }}</div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title"><i class="fas fa-user-circle me-2"></i> {{ __('ui.client_information') }}</div>
        <div class="info-item"><span class="info-label">{{ __('ui.code') }}</span><span class="info-value">{{ $account->code }}</span></div>
        {{-- <div class="info-item"><span class="info-label">{{ __('ui.category') }}</span><span class="info-value">{{ $account->subCategory->name ?? 'N/A' }}</span></div> --}}
        <div class="info-item"><span class="info-label">{{ __('ui.contact') }}</span><span class="info-value">{{ $account->contact ?? 'N/A' }}</span></div>
        {{-- <div class="info-item"><span class="info-label">{{ __('accounts.company') }}</span><span class="info-value">{{ $account->company ?? 'N/A' }}</span></div>
        <div class="info-item"><span class="info-label">{{ __('ui.address') }}</span><span class="info-value">{{ $account->address ?? 'N/A' }}</span></div> --}}
    </div>

    <div class="section-card">
        <div class="section-title"><i class="fas fa-wallet me-2"></i> {{ __('ui.blade_currency_summary') }}</div>
        <div class="currency-summary-row">
            @foreach ($summariesByCurrency as $currency => $summary)
                @php
                    $balance = $summary['credit'] - $summary['debit'];
                    $balanceClass = $balance >= 0 ? 'balance-positive' : 'balance-negative';
                    $currencyFlag = strtolower(substr($currency, 0, 2));
                @endphp
                <div class="currency-summary-box">
                    <div><strong><img src="/assets/flags/{{ $currencyFlag }}.svg" width="20" class="me-1"> {{ $currency }}</strong></div>
                    {{-- <div><strong style="color: #28a745">{{ __('ui.cr') }}</strong> {{ format_amount($summary['credit'], 2) }}</div>
                    <div><strong style="color: #dc3545">{{ __('ui.dr') }}</strong> {{ format_amount($summary['debit'], 2) }}</div> --}}
                    <div><strong class="{{ $balanceClass }}">{{ __('ui.balance') }}</strong> <span class="{{ $balanceClass }}">{{ format_amount($balance, 2) }}</span></div>
                </div> @endforeach
        </div>
    </div>

    <div class="export-footer">
    {{ $setting->company_name }} · 📧 {{ $setting->email }} · ☎ {{ $setting->contact }}
    </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('assets/offline/ed46bfb3ee1c1b39.js') }}"></script>
    <script src="{{ asset('assets/offline/19e7da8fedcfe35f.js') }}"></script>
    <script>
        document.getElementById('exportImage').addEventListener('click', () => {
            html2canvas(document.getElementById('export-area'), {
                backgroundColor: '#ffffff',
                useCORS: true,
                scale: 2
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'account-{{ $account->code }}.png';
                link.href = canvas.toDataURL();
                link.click();
            });
        });

        document.getElementById('exportPdf').addEventListener('click', async () => {
            const {
                jsPDF
            } = window.jspdf;
            const canvas = await html2canvas(document.getElementById('export-area'), {
                backgroundColor: '#ffffff',
                useCORS: true,
                scale: 2
            });
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const width = pdf.internal.pageSize.getWidth();
            const height = (canvas.height * width) / canvas.width;
            pdf.addImage(imgData, 'PNG', 0, 10, width, height);
            pdf.save('account-{{ $account->code }}.pdf');
        });
    </script>
@endsection
