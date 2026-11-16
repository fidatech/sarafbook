@extends('layouts.admin.base')

@section('title', __('reports.transactions_title'))

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
    <link href="{{ asset('assets/offline/febd1387d851569a.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/offline/680d13a070165f98.css') }}" />

    <style>
        /* Base Styling */
        body {
            background-color: #f4f5f8;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: #393a3d;
        }

        .report-container {
            background: #fff;
            padding: 20px; /* Reduced from 30px */
            border: 1px solid #d4d7dc;
            width: 100%;
            margin-top: 10px;
        }

        /* QB Header Section */
        .qb-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #393a3d; /* Thicker like a ledger */
            padding-bottom: 15px;
        }

        .qb-header h2 {
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0;
            color: #000;
        }

        .filter-bar {
            background: #f4f5f8;
            padding: 10px;
            border: 1px solid #d4d7dc;
            margin-bottom: 15px;
            border-radius: 2px;
        }

        /* Compact Table Reset - The "QuickBooks" Density */
        .table {
            margin-bottom: 0;
            font-size: 12px; /* Standard QB Report size */
        }

        /* Force small row height and remove padding */
        .table > :not(caption) > * > * {
            padding: 4px 8px !important;
            line-height: 1.2;
            vertical-align: middle;
            border-bottom: 1px solid #edeef0;
        }

        .table thead th {
            background-color: #fff !important;
            border-bottom: 2px solid #393a3d !important;
            font-size: 11px;
            text-transform: uppercase;
            color: #393a3d;
            font-weight: 700;
            white-space: nowrap;
        }

        /* Summary Area Styling */
        .currency-summary-box {
            border: 1px solid #babec5;
            margin-bottom: 15px;
            background: #fff;
        }

        .currency-header {
            background: #393a3d;
            color: #fff;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            padding: 2px 0;
            letter-spacing: 1px;
        }

        .summary-content {
            padding: 8px 12px;
        }

        .summary-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #6a6c71;
            font-weight: 700;
            display: block;
        }

        .summary-value {
            font-size: 15px;
            font-weight: 700;
        }

        .text-credit { color: #198754 !important; }
        .text-debit { color: #dc3545 !important; }

        /* Print Logic */
        @media print {
            body { background: #fff !important; }
            body * { visibility: hidden; }
            .report-container, .report-container * { visibility: visible; }
            .report-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100% !important;
                border: none !important;
                padding: 0 !important;
            }
            .no-print, .filter-bar, .btn, .dataTables_paginate { display: none !important; }
            .table thead th { border-bottom: 2px solid #000 !important; }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-end no-print my-2">
            <button class="btn btn-sm btn-outline-dark px-3 shadow-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> {{ __('reports.print_report') }}
            </button>

            <a href="#" class="btn btn-success btn-sm px-3 shadow-sm btn-excel">
    <i class="fas fa-file-excel me-1"></i> {{ __('reports.export_excel') }}
</a>
        </div>

        <div class="report-container shadow-sm">
            <div class="qb-header">
                @if ($company->logo)
                    <img src="{{ asset('images/' . $company->logo) }}" style="max-height:60px;" class="mb-2">
                @endif
                <h2>{{ $company->name }}</h2>
                <div class="small">{{ $company->address }} | {{ $company->email }}</div>
                <h3 class="mt-2 mb-1" style="font-size: 17px; font-weight: 400; color: #393a3d;">{{ __('ui.blade_transaction_detail') }}</h3>
                <p class="small text-muted mb-0" id="print-period">{{ __('ui.blade_basis_accrual') }}</p>
            </div>

            <div class="filter-bar no-print">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="x-small fw-bold">{{ __('ui.account') }}</label>
                        <select id="account_id" class="select2 form-select form-select-sm">
                            <option value="">{{ __('ui.all_accounts') }}</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->name }} ({{ $a->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="x-small fw-bold">{{ __('ui.currency') }}</label>
                        <select id="currency_id" class="select2 form-select form-select-sm">
                            <option value="">{{ __('ui.all_currencies') }}</option>
                            @foreach ($currencies as $c)
                                <option value="{{ $c->id }}">{{ $c->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="x-small fw-bold">{{ __('ui.blade_from') }}</label><input type="date" id="from_date" class="form-control form-control-sm"></div>
                    <div class="col-md-2"><label class="x-small fw-bold">{{ __('ui.blade_to') }}</label><input type="date" id="to_date" class="form-control form-control-sm"></div>
                    <div class="col-md-1">
                        <label class="x-small fw-bold">{{ __('ui.type') }}</label>
                        <select id="transaction_type" class="form-select form-select-sm">
                            <option value="">{{ __('ui.all') }}</option>
                            <option value="credit">{{ __('ui.credit') }}</option>
                            <option value="debit">{{ __('ui.debit') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-dark btn-sm w-100" onclick="table.ajax.reload()">{{ __('ui.blade_run_report') }}</button></div>
                </div>
            </div>

            <div id="summary-area"></div>

            <div class="table-responsive">
                <table id="table" class="table-hover w-100 table border-0">
                    <thead>
                        <tr>
                            <th width="12%">{{ __('ui.date') }}</th>
                            <th>{{ __('ui.account') }}</th>
                            <th width="8%" class="text-center">{{ __('ui.blade_ccy') }}</th>
                            <th width="12%" class="text-end">{{ __('ui.credit') }}</th>
                            <th width="12%" class="text-end">{{ __('ui.debit') }}</th>
                            <th>{{ __('ui.blade_memo') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>
    <script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>
    <script src="{{ asset('assets/offline/33859893654b9de2.js') }}"></script>

    <script>
        let table;
        $(document).ready(function() {
            $('.select2').select2({ language: @include('admin.reports.partials.select-language'), theme: 'bootstrap-5' });

            table = $('#table').DataTable({
                language: @include('admin.reports.partials.table-language'),
                processing: true,
                serverSide: true,
                pageLength: 50,
                dom: 'rtp',
                ajax: {
                    url: "{{ route('admin.reports.transactions.data') }}",
                    data: function(d) {
                        d.account_id = $('#account_id').val();
                        d.currency_id = $('#currency_id').val();
                        d.transaction_type = $('#transaction_type').val();
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                    }
                },
                columns: [
                    { data: 'date', className: 'text-nowrap' },
                    { data: 'account', className: 'fw-bold' },
                    { data: 'currency', className: 'text-center' },
                    { data: 'credit', className: 'text-end text-credit' },
                    { data: 'debit', className: 'text-end text-debit' },
                    { data: 'note' },
                ],
                drawCallback: function(settings) {
                    const json = settings.json;
                    const area = $('#summary-area');
                    area.empty();

                    if (json.summaries && json.summaries.length > 0) {
                        json.summaries.forEach(function(s) {
                            const balClass = s.raw_balance >= 0 ? 'text-success' : 'text-danger';
                            area.append(`
                                <div class="currency-summary-box mb-3">
                                    <div class="currency-header">${s.code} {{ __('reports.summary') }}</div>
                                    <div class="summary-content">
                                        <div class="row">
                                            <div class="col-4 border-end">
                                                <span class="summary-label">{{ __('ui.total_credit') }}</span>
                                                <span class="summary-value text-success">${s.credit}</span>
                                            </div>
                                            <div class="col-4 border-end">
                                                <span class="summary-label">{{ __('ui.total_debit') }}</span>
                                                <span class="summary-value text-danger">${s.debit}</span>
                                            </div>
                                            <div class="col-4 text-end">
                                                <span class="summary-label">{{ __('reports.net_balance') }} (${s.code})</span>
                                                <span class="summary-value ${balClass}">${s.balance}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `);
                        });
                    }

                    let from = $('#from_date').val() || @json(__('reports.start'));
                    let to = $('#to_date').val() || @json(__('reports.end'));
                    $('#print-period').text(@json(__('reports.period')).replace(':from', from).replace(':to', to));
                }
            });
        });

        function updateExcelLink() {
    let url = "{{ route('admin.reports.transactions.export.excel') }}?" +
        "account_id=" + encodeURIComponent($('#account_id').val() || '') +
        "&currency_id=" + encodeURIComponent($('#currency_id').val() || '') +
        "&transaction_type=" + encodeURIComponent($('#transaction_type').val() || '') +
        "&from_date=" + encodeURIComponent($('#from_date').val() || '') +
        "&to_date=" + encodeURIComponent($('#to_date').val() || '');

    $('.btn-excel').attr('href', url);
}

$(document).on('change', '#account_id, #currency_id, #transaction_type, #from_date, #to_date', function () {
    updateExcelLink();
});

$(document).ready(function () {
    updateExcelLink();
});
    </script>
@endsection
