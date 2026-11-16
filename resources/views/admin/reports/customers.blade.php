@php
    // Define the currencies for the JS columns and the table header/footer
    $reportCurrencies = $currencies
        ->map(function ($currency) {
            return [
                'id' => $currency->id,
                'code' => $currency->code,
                'column' => strtolower($currency->code) . '_balance',
            ];
        })
        ->values();
@endphp

@extends('layouts.admin.base')

@section('title', __('reports.customers_title'))

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
    <style>
        /* QBO Core Aesthetic */
        :root {
            --qb-green: #2ca01c;
            --qb-text: #393a3d;
            --qb-border: #d4d7dc;
            --qb-bg-light: #f4f5f8;
        }

        body {
            background-color: var(--qb-bg-light);
            color: var(--qb-text);
        }

        .report-paper {
            background: #fff;
            padding: 40px;
            margin-top: 20px;
            border: 1px solid var(--qb-border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* QBO Table Style */
        .table-qbo {
            font-size: 13px !important;
            border-collapse: collapse !important;
            width: 100% !important;
        }

        .table-qbo thead th {
            border-top: 1px solid var(--qb-text) !important;
            border-bottom: 2px solid var(--qb-text) !important;
            padding: 8px !important;
            font-weight: 700;
            background: #fff !important;
        }

        .table-qbo tbody td {
            padding: 4px 8px !important;
            border-bottom: 1px solid #eceef1 !important;
            height: 32px;
        }

        /* Accounting Footer */
        .table-qbo tfoot th {
            border-top: 2px solid var(--qb-text) !important;
            padding: 10px 8px !important;
            background: #fff !important;
            font-weight: 800;
        }

        .total-double-underline {
            border-bottom: 3px double var(--qb-text);
            padding-bottom: 2px;
            display: inline-block;
        }

        /* Formatting */
        .val-neg {
            color: #d52d1e;
        }

        .val-pos {
            color: #0077c5;
        }

        .btn-qbo {
            border: 1px solid var(--qb-border);
            background: #fff;
            font-weight: 600;
            font-size: 13px;
        }

        .btn-qbo:hover {
            background: #f4f5f8;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4">

        {{-- Action Bar --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">{{ __('ui.blade_accounts_balance_summary') }}</h4>
            <div class="d-flex gap-2">
                <button class="btn btn-qbo btn-sm" onclick="confirmRecalculateBalances()"><i class="bi bi-arrow-repeat"></i> {{ __('ui.blade_recalculate') }}</button>
                <button class="btn btn-qbo btn-sm" id="exportPdfBtn"><i class="bi bi-printer"></i> {{ __('ui.blade_print_pdf') }}</button>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body rounded border bg-white p-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="small fw-bold mb-1">{{ __('reports.account_type') }}</label>
                        <select class="form-select-sm form-select" id="filter_sub">
                            <option value="">{{ __('ui.blade_all_types') }}</option>
                            @foreach ($subCategories as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold mb-1">{{ __('ui.accounts') }}</label>
                        <select class="form-select-sm form-select" id="filter_customer">
                            <option value="">{{ __('ui.all_accounts') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-qbo btn-sm w-100" id="resetFiltersBtn">{{ __('ui.blade_reset_filters') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Report Content --}}
        <div class="report-paper">
            <div class="mb-5 text-center">
                <h3 class="fw-bold mb-1">{{ $company->name ?? __('reports.company') }}</h3>
                <h4 class="fw-normal mb-1">{{ __('ui.blade_customers_balance_summary') }}</h4>
                <div class="text-muted">{{ __('reports.as_of') }} {{ now()->locale(app()->getLocale())->translatedFormat('F d, Y') }}</div>
            </div>

            <div class="table-responsive">
                <table class="table-qbo table" id="reportTable">
                    <thead>
                        <tr>
                            <th class="text-start">{{ __('ui.accounts') }}</th>
                            @foreach ($currencies as $currency)
                                <th class="text-end">{{ $currency->code }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DataTables populated --}}
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-start">{{ __('ui.total') }}</th>
                            @foreach ($currencies as $currency)
                                <th class="text-end" id="total-{{ strtolower($currency->code) }}"></th>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="border-top small text-muted mt-5 pt-3">
                <em>{{ __('reports.accrual') }} {{ now()->locale(app()->getLocale())->translatedFormat('l, F d, Y h:i A') }}</em>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>
    <script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>

    <script>
        const reportsSwal = Swal.mixin({confirmButtonText: @json(__('ui.blade_ok')), cancelButtonText: @json(__('ui.cancel'))});
        const reportCurrencies = @json($reportCurrencies);

        function formatAccounting(num) {
            const val = parseFloat(num || 0);
            const formatted = Math.abs(val).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            if (val < 0) return `<span class="val-neg">(${formatted})</span>`;
            if (val === 0) return `<span class="text-muted">0.00</span>`;
            return `<span class="val-pos">${formatted}</span>`;
        }

        // 1. Column Definitions
        let columns = [{
            data: 'account_name',
            className: 'text-start fw-bold',
            render: (data, type, row) => `<a href="/admin/accounts/${row.id}" class="text-decoration-none text-dark">${data}</a>`
        }];

        reportCurrencies.forEach(curr => {
            columns.push({
                data: curr.column,
                className: 'text-end',
                render: formatAccounting
            });
        });

        // 2. Initialize DataTable
        let table = $('#reportTable').DataTable({
                language: @include('admin.reports.partials.table-language'),
            serverSide: true,
            processing: true,
            searching: false,
            paging: false,
            info: false,
            ajax: {
                url: '{{ route('admin.reports.customers.data') }}',
                data: function(d) {
                    d.sub_category_id = $('#filter_sub').val();
                    d.customer_id = $('#filter_customer').val();
                }
            },
            columns: columns,
            drawCallback: function() {
                const json = this.api().ajax.json();
                if (json && json.footerTotals) {
                    reportCurrencies.forEach(curr => {
                        const total = json.footerTotals[curr.column];
                        $(`#total-${curr.code.toLowerCase()}`).html(
                            `<span class="total-double-underline">${formatAccounting(total)}</span>`
                        );
                    });
                }
            }
        });

        // 3. Dynamic Filter Logic
        $('#filter_sub').on('change', function() {
            const subId = $(this).val();

            // Clear customer dropdown and show loading
            $('#filter_customer').html('<option value="">{{ __('ui.blade_loading') }}</option>');

            $.ajax({
                url: '{{ route('admin.reports.getCustomersBySubCategory') }}',
                data: {
                    sub_category_id: subId
                },
                success: function(data) {
                    let options = '<option value="">{{ __('ui.all_customers') }}</option>';
                    data.forEach(customer => {
                        const codeStr = customer.code ? ` (${customer.code})` : '';
                        options += `<option value="${customer.id}">${customer.name}${codeStr}</option>`;
                    });
                    $('#filter_customer').html(options);
                }
            });

            table.ajax.reload();
        });

        $('#filter_customer').on('change', function() {
            table.ajax.reload();
        });

        $('#resetFiltersBtn').on('click', function() {
            $('#filter_sub').val('');
            $('#filter_customer').html('<option value="">{{ __('ui.all_customers') }}</option>');
            table.ajax.reload();
        });

        // 4. Utility Functions
        function confirmRecalculateBalances() {
    reportsSwal.fire({
        title: '{{ __('ui.recalculate_balances') }}',
        text: '{{ __('ui.blade_this_will_recalculate_all_account_balances_please_wait_until_it_finish') }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2ca01c',
        cancelButtonColor: '#d33',
        confirmButtonText: '{{ __('ui.yes_recalculate') }}',
        cancelButtonText: '{{ __('ui.cancel') }}'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        reportsSwal.fire({
            title: '{{ __('ui.blade_recalculating') }}',
            text: '{{ __('ui.blade_please_wait') }}',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "{{ route('admin.reports.recalculate_balances') }}",
            type: "GET",
            success: function(response) {
                reportsSwal.fire({
                    icon: 'success',
                    title: '{{ __('ui.blade_done') }}',
                    text: response.message || @json(__('reports.recalculated')),
                    timer: 1800,
                    showConfirmButton: false
                });

                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                let message = @json(__('reports.recalculate_error'));

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                reportsSwal.fire({
                    icon: 'error',
                    title: '{{ __('ui.blade_failed') }}',
                    text: message
                });
            }
        });
    });
}

        $('#exportPdfBtn').on('click', function(e) {
    e.preventDefault();

    const subId = $('#filter_sub').val() || '';
    const customerId = $('#filter_customer').val() || '';

    const baseUrl = "{{ route('admin.reports.customers.print') }}";
    const finalUrl = `${baseUrl}?sub_category_id=${subId}&customer_id=${customerId}`;

    window.open(finalUrl, '_blank');
});
    </script>
@endsection
