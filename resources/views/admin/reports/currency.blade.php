@extends('layouts.admin.base')

@section('title', __('reports.currency_title'))

@section('css')
    <style>
        /* Minimalist Essentials */
        body { background-color: #fcfcfd; color: #2d3748; }
        .card { border: 1px solid #e2e8f0; border-radius: 0.75rem; box-shadow: none; }

        /* Typography */
        .report-title { font-weight: 800; letter-spacing: -0.025em; color: #1a202c; }
        .amount-font { font-family: 'JetBrains Mono', 'Menlo', monospace; font-weight: 600; }

        /* Refined Colors */
        .text-credit { color: #4f46e5 !important; }
        .text-debit { color: #e11d48 !important; }
        .card-balance { background-color: #f8fafc; border: 1px solid #e2e8f0; }

        /* Table Styling */
        .table thead th {
            background-color: #f8fafc;
            text-transform: uppercase;
            font-size: 0.7rem;
            color: #64748b;
            padding: 12px 16px;
        }

        /* Placeholder / Empty State */
        #emptyState {
            padding: 100px 20px;
            text-align: center;
            background: #fff;
            border-radius: 1rem;
            border: 2px dashed #e2e8f0;
            margin-top: 2rem;
        }
        .placeholder-icon {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
            display: block;
        }

        .btn-pdf {
            background: #fff;
            color: #e11d48;
            border: 1px solid #e11d48;
            font-weight: 600;
        }
        .btn-pdf:hover { background: #e11d48; color: #fff; }
    </style>
@endsection

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 border">
        <h4 class="report-title mb-0">{{ __('ui.currency_report') }}</h4>

        <div class="d-flex gap-2">
            <select id="currencySelect" class="form-select border-light shadow-sm" style="width: 220px;">
                <option value="">{{ __('ui.select_currency') }}</option>
                @foreach ($currencies as $cur)
                    <option value="{{ $cur->id }}">{{ $cur->code }}</option>
                @endforeach
            </select>
            <button id="exportPdfBtn" class="btn btn-pdf px-4">
                <i class="bi bi-file-pdf me-1"></i> PDF
            </button>
        </div>
    </div>

    <div id="emptyState">
        <i class="bi bi-wallet2 placeholder-icon"></i>
        <h5 class="text-secondary">{{ __('ui.blade_no_currency_selected') }}</h5>
        <p class="text-muted">{{ __('ui.blade_please_choose_a_currency_from_the_dropdown_to_generate_the_report_data') }}</p>
    </div>

    <div id="reportContent" class="d-none">
        <div id="summaryDashboard" class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card h-100 p-3 shadow-sm border-0 bg-white">
                    <small class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem;">{{ __('ui.total_credits') }}</small>
                    <h3 id="creditsTotal" class="text-credit amount-font mb-0 mt-1">0.00</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-3 shadow-sm border-0 bg-white">
                    <small class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem;">{{ __('ui.total_debits') }}</small>
                    <h3 id="debitsTotal" class="text-debit amount-font mb-0 mt-1">0.00</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-3 shadow-sm border-0 card-balance">
                    <small class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem;">{{ __('ui.blade_net_balance') }}</small>
                    <h3 id="currencyBalance" class="amount-font mb-0 mt-1">0.00</h3>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm overflow-hidden">
                    <div class="px-3 py-3 border-bottom bg-white"><h6 class="mb-0 fw-bold text-credit">{{ __('ui.credits') }}</h6></div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th width="50">#</th><th>{{ __('ui.customer') }}</th><th class="text-end">{{ __('ui.amount') }}</th></tr></thead>
                            <tbody id="creditsTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm overflow-hidden">
                    <div class="px-3 py-3 border-bottom bg-white"><h6 class="mb-0 fw-bold text-debit">{{ __('ui.debits') }}</h6></div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th width="50">#</th><th>{{ __('ui.customer') }}</th><th class="text-end">{{ __('ui.amount') }}</th></tr></thead>
                            <tbody id="debitsTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {

        $('#currencySelect').on('change', function() {
            let currency = $(this).val();

            if (!currency) {
                $('#reportContent').addClass('d-none');
                $('#emptyState').removeClass('d-none');
                return;
            }

            // Show loading or toggle views
            $.get("{{ route('admin.reports.currency.data') }}", { currency_id: currency }, function(res) {
                $('#emptyState').addClass('d-none');
                $('#reportContent').removeClass('d-none');

                let creditsHtml = '';
                let debitsHtml = '';
                let creditSum = 0;
                let debitSum = 0;

                const format = (v) => v.toLocaleString('en-US', {minimumFractionDigits: 2});

                // Credits
                if (res.credits.length === 0) {
                    creditsHtml = '<tr><td colspan="3" class="text-center py-4 text-muted">{{ __('ui.blade_no_credits') }}</td></tr>';
                } else {
                    res.credits.forEach((row, i) => {
                        let val = parseFloat(row.amount.replace(/,/g, ''));
                        creditSum += val;
                        creditsHtml += `<tr><td>${i+1}</td><td><div class="fw-bold">${row.name}</div></td><td class="text-end text-credit fw-bold amount-font">${format(val)}</td></tr>`;
                    });
                }

                // Debits
                if (res.debits.length === 0) {
                    debitsHtml = '<tr><td colspan="3" class="text-center py-4 text-muted">{{ __('ui.blade_no_debits') }}</td></tr>';
                } else {
                    res.debits.forEach((row, i) => {
                        let val = parseFloat(row.amount.replace(/,/g, ''));
                        debitSum += val;
                        debitsHtml += `<tr><td>${i+1}</td><td><div class="fw-bold">${row.name}</div></td><td class="text-end text-debit fw-bold amount-font">${format(val)}</td></tr>`;
                    });
                }

                $('#creditsTable').html(creditsHtml);
                $('#debitsTable').html(debitsHtml);
                $('#creditsTotal').text(format(creditSum));
                $('#debitsTotal').text(format(debitSum));

                let balance = creditSum - debitSum;
                $('#currencyBalance').text(format(balance))
                    .removeClass('text-credit text-debit')
                    .addClass(balance >= 0 ? 'text-credit' : 'text-debit');
            });
        });

        // Fixed PDF Button Listener
        $(document).on('click', '#exportPdfBtn', function(e) {
            e.preventDefault();
            let id = $('#currencySelect').val();
            if(!id) {
                alert(@json(__('reports.select_currency')));
                return;
            }

            let url = "{{ route('admin.reports.currency.export.pdf') }}?currency_id=" + id;
            window.open(url, "_blank");
        });
    });
</script>
@endsection
