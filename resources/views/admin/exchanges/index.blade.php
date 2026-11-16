@extends('layouts.admin.base')

@section('title', __('exchanges.index_title'))

@section('css')

    <link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/9fb6ddf0a1dcf229.css') }}">

    <style>
        .exchange-pill {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
        }

        .exchange-buy {
            background: #dcfce7;
            color: #166534;
        }

        .exchange-sell {
            background: #fee2e2;
            color: #991b1b;
        }

        .amount-mono {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
        }

        .action-btn {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }

        /* Exchange ledger visual system */
        .exchanges-page{--ex-ink:#1c304e;--ex-muted:#65768e;--ex-line:#e5ebf3;max-width:1820px;margin-inline:auto;padding-bottom:24px;color:var(--ex-ink)}
        .exchanges-hero{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:19px 23px;margin-bottom:15px;border-radius:17px;background:linear-gradient(115deg,#182b4a 0%,#29416f 63%,#365aa3 100%);color:#fff;position:relative;overflow:hidden;box-shadow:0 11px 28px rgba(27,48,89,.13)}
        .exchanges-hero:after{content:"";position:absolute;width:190px;height:190px;border:25px solid rgba(255,255,255,.045);border-radius:50%;inset-inline-end:-64px;top:-110px;pointer-events:none}
        .exchanges-hero-copy,.exchanges-create-btn{position:relative;z-index:1}
        .exchanges-eyebrow{display:inline-flex;align-items:center;gap:7px;font-size:.69rem;font-weight:800;color:#bfd0f8;letter-spacing:.04em}
        .exchanges-hero h2{font-size:1.42rem;font-weight:800;letter-spacing:-.025em;color:#fff;margin:4px 0}
        .exchanges-hero p{font-size:.76rem;color:#d4def3;margin:0}
        .exchanges-create-btn{display:inline-flex;align-items:center;gap:8px;justify-content:center;white-space:nowrap;min-height:41px;background:#fff;color:#284a8a;border:1px solid rgba(255,255,255,.85);border-radius:10px;padding:9px 15px;font-weight:800;font-size:.76rem;box-shadow:0 4px 12px rgba(14,30,61,.13)}
        .exchanges-create-btn:hover{background:#eaf1ff;color:#1d3c77}
        .exchanges-section-heading{display:flex;align-items:center;justify-content:space-between;margin:6px 2px 11px}
        .exchanges-section-heading span,.exchanges-table-heading span{font-size:.66rem;letter-spacing:.065em;font-weight:800;color:#5a76aa}
        .exchanges-section-heading h3,.exchanges-table-heading h3{font-size:1rem;font-weight:800;color:#233d5e;margin:3px 0 0}
        .exchanges-page>.card{border:1px solid var(--ex-line)!important;border-radius:15px!important;box-shadow:0 4px 15px rgba(29,48,83,.04)!important;background:#fff;overflow:hidden}
        .exchanges-filter-card .card-body{padding:16px 18px}
        .exchanges-filter-title{display:flex;align-items:center;gap:8px;margin-bottom:12px;color:#39537d;font-size:.78rem;font-weight:800}
        .exchanges-filter-title i{color:#4c70b2}
        .exchanges-field-label{display:block;font-size:.7rem;font-weight:750;color:#62758e;margin-bottom:6px}
        .exchanges-page .exchanges-filter-card .form-select,.exchanges-page .exchanges-filter-card .form-control{height:40px;font-size:.8rem;border-radius:9px;border:1px solid #dce5ef;background:#fff;color:#283c5b;box-shadow:none}
        .exchanges-page .exchanges-filter-card .form-control:focus,.exchanges-page .exchanges-filter-card .form-select:focus{border-color:#8aa5db;box-shadow:0 0 0 3px rgba(69,110,180,.12)}
        .exchanges-page #exchange_summary>[class*="col-"]{min-width:0}
        .exchange-summary-card{height:100%;position:relative;overflow:hidden;background:linear-gradient(150deg,#fff,#fafcff);border:1px solid var(--ex-line);border-radius:14px;min-height:124px;padding:17px;box-shadow:0 4px 14px rgba(27,47,81,.04)}
        .exchange-summary-card:before{content:"";position:absolute;inset-block:0;inset-inline-start:0;width:3px;background:var(--summary-accent,#456db8)}
        .exchange-summary-card:after{content:"";position:absolute;inset-inline-end:-40px;bottom:-55px;width:88px;height:88px;border-radius:50%;background:var(--summary-soft,#eaf0ff);opacity:.6;pointer-events:none}
        .exchange-summary-card.profit{--summary-accent:#159377;--summary-soft:#e7f7f1}
        .exchange-summary-card.count{--summary-accent:#7b5ccc;--summary-soft:#f0eaff}
        .exchange-summary-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:15px}
        .exchange-summary-title{color:#62738a;font-weight:750;font-size:.73rem}
        .exchange-summary-icon{width:37px;height:37px;flex:0 0 37px;display:grid;place-items:center;background:var(--summary-soft,#eaf0ff);color:var(--summary-accent,#456db8);border-radius:10px;font-size:1.08rem}
        .exchange-summary-value{font-size:1.5rem;font-weight:850;letter-spacing:-.027em;line-height:1.25;overflow-wrap:anywhere;color:#243a5a;font-variant-numeric:tabular-nums}
        .exchange-summary-card.profit .exchange-summary-value{color:#107c62}
        .exchanges-table-heading{display:flex;justify-content:space-between;align-items:center;gap:15px;padding:16px 18px;border-bottom:1px solid #e9eef5}
        .exchanges-table-heading>i{display:grid;place-items:center;background:#edf2ff;color:#4268b0;border-radius:10px;width:39px;height:39px;font-size:1.1rem}
        .exchanges-table-scroll{padding:12px 14px!important;overflow-x:auto}
        .exchanges-page #exchange-table{min-width:870px;margin:0!important;border-collapse:separate;border-spacing:0}
        .exchanges-page #exchange-table thead th{background:#f3f6fb;color:#5f718a;border-bottom:1px solid #e5ebf4;padding:12px 11px;white-space:nowrap;font-size:.69rem;font-weight:800;letter-spacing:.035em}
        .exchanges-page #exchange-table tbody td{font-size:.77rem;padding:12px 11px;color:#3c506f;border-bottom:1px solid #eef1f6;vertical-align:middle}
        .exchanges-page #exchange-table tbody tr:hover td{background:#fafcff}
        .exchanges-page .amount-mono{font-family:inherit;font-size:.82rem;font-weight:800;letter-spacing:-.015em;font-variant-numeric:tabular-nums;white-space:nowrap}
        .exchanges-page .exchange-pill{display:inline-flex;align-items:center;border-radius:7px;padding:6px 10px;font-size:.7rem;font-weight:800;white-space:nowrap}
        .exchanges-page .exchange-buy{background:#e7f8f0;color:#117a5b}
        .exchanges-page .exchange-sell{background:#fff0f1;color:#bf4c5f}
        .exchanges-page .action-btn{width:32px;height:32px;border-radius:9px;background:#f1f5fc;border:1px solid #e2eaf5;box-shadow:none}
        .exchanges-page .action-btn:hover{background:#e3ecff;border-color:#cad9f5}
        .exchanges-page .action-btn.delete-btn{background:#fff1f3;border-color:#f7dee3}
        .exchanges-page .action-btn.delete-btn:hover{background:#ffe4e9}
        .exchanges-page .dataTables_wrapper .dataTables_filter input,.exchanges-page .dataTables_wrapper .dataTables_length select{font-size:.76rem;min-height:35px;border:1px solid #dce5f0;border-radius:8px;padding:6px 9px;background:#fff}
        .exchanges-page .dataTables_wrapper .dataTables_filter,.exchanges-page .dataTables_wrapper .dataTables_length,.exchanges-page .dataTables_wrapper .dataTables_info,.exchanges-page .dataTables_wrapper .dataTables_paginate{font-size:.74rem;color:#647690}
        .exchanges-page .dataTables_wrapper>.row{row-gap:10px}
        .exchanges-detail-modal{border:1px solid #e6edf6;border-radius:15px;overflow:hidden;box-shadow:0 16px 45px rgba(20,36,74,.18)}
        .exchanges-detail-modal .modal-header{background:#f7f9fd;border-color:#e8edf5;padding:16px 19px}
        .exchanges-detail-modal .modal-title{font-size:.96rem;font-weight:800;color:#253c60}
        .exchanges-detail-modal .modal-body{padding:19px}
        .exchange-detail-field{border:1px solid #e8edf5;background:#f9fbfe;padding:12px;border-radius:10px;height:100%}
        .exchange-detail-field small{display:block;color:#64748b;font-size:.7rem;font-weight:750;margin-bottom:6px}
        .exchange-detail-field strong{font-size:.9rem;font-weight:800;color:#243b5d;overflow-wrap:anywhere;font-variant-numeric:tabular-nums}
        .exchanges-page :is(button,a,input,select):focus-visible{outline:3px solid #96afe3;outline-offset:2px}
        @media(max-width:767px){.exchanges-hero{padding:16px}.exchanges-hero h2{font-size:1.2rem}.exchanges-hero p{font-size:.68rem}.exchanges-create-btn{padding:9px;min-height:40px}.exchanges-create-btn span{display:none}.exchanges-filter-card .card-body{padding:13px}.exchange-summary-card{padding:13px;min-height:105px}.exchange-summary-value{font-size:1.2rem}.exchanges-table-scroll{padding:9px!important}}
    </style>

@endsection

@section('content')
    <div class="exchanges-page">
        <header class="exchanges-hero">
            <div class="exchanges-hero-copy">
                <span class="exchanges-eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i> {{ __('exchanges.index_title') }}</span>
                <h2>{{ __('ui.exchange_ledger') }}</h2>
                <p>{{ __('ui.blade_all_currency_exchange_operations') }}</p>
            </div>
            <a href="{{ route('admin.exchanges.create') }}" class="btn exchanges-create-btn">
                <i class="bi bi-plus-lg" aria-hidden="true"></i><span>{{ __('ui.blade_new_transaction') }}</span>
            </a>
        </header>

        <div class="exchanges-section-heading">
            <div><span>{{ __('exchanges.index_title') }}</span><h3>{{ __('ui.blade_total_exchanges') }}</h3></div>
        </div>
        <div class="row g-3 mb-3" id="exchange_summary" aria-live="polite"></div>
        <div class="card exchanges-filter-card mb-3">
            <div class="card-body">
                <div class="exchanges-filter-title"><i class="bi bi-funnel" aria-hidden="true"></i>{{ __('ui.blade_all_currency_exchange_operations') }}</div>
                <div class="row g-2 align-items-end">

                <div class="col-md-6 col-xl-3">
                    <label for="filter_type" class="exchanges-field-label">{{ __('ui.type') }}</label>
                    <select id="filter_type" class="form-select-sm form-select">
                        <option value="">{{ __('ui.blade_all_types') }}</option>
                        <option value="buy">{{ __('ui.blade_buy_2') }}</option>
                        <option value="sell">{{ __('ui.blade_sell') }}</option>
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label for="filter_mode" class="exchanges-field-label">{{ __('ui.mode') }}</label>
                    <select id="filter_mode" class="form-select-sm form-select">
                        <option value="">{{ __('ui.blade_all_modes') }}</option>
                        <option value="cash">{{ __('ui.blade_cash') }}</option>
                        <option value="customer">{{ __('ui.customer') }}</option>
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label for="date_from" class="exchanges-field-label">{{ __('ui.blade_from') }}</label>
                    <input type="date" id="date_from" class="form-control form-control-sm">
                </div>

                <div class="col-md-6 col-xl-3">
                    <label for="date_to" class="exchanges-field-label">{{ __('ui.blade_to') }}</label>
                    <input type="date" id="date_to" class="form-control form-control-sm">
                </div>

            </div>

        </div>

    </div>

        <div class="card exchanges-table-card">
            <div class="exchanges-table-heading">
                <div><span>{{ __('exchanges.index_title') }}</span><h3>{{ __('ui.exchange_ledger') }}</h3></div>
                <i class="bi bi-currency-exchange" aria-hidden="true"></i>
            </div>
            <div class="table-responsive exchanges-table-scroll">

            <table id="exchange-table" class="table-hover mb-0 table align-middle">

                <thead>

                    <tr>

                        <th class="ps-4">#</th>

                        <th>{{ __('ui.date') }}</th>

                        <th>{{ __('ui.type') }}</th>

                        <th>{{ __('ui.pair') }}</th>

                        <th class="text-end">{{ __('ui.blade_from') }}</th>

                        <th class="text-end">{{ __('ui.blade_to') }}</th>

                        <th class="text-end">{{ __('ui.rate') }}</th>

                        <th class="text-end">{{ __('ui.blade_profit') }}</th>

                        <th class="text-center">{{ __('ui.actions') }}</th>

                    </tr>

                </thead>

            </table>

        </div>

    </div>

    <div class="modal fade" id="exchangeViewModal" tabindex="-1" aria-labelledby="exchangeViewModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content exchanges-detail-modal">

                <div class="modal-header">
                    <h5 id="exchangeViewModalTitle" class="modal-title"><i class="bi bi-receipt me-2" aria-hidden="true"></i>{{ __('ui.blade_exchange_details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.cancel') }}"></button>
                </div>

                <div class="modal-body" id="exchange_view_content">
                    {{ __('exchanges.loading') }}
                </div>

            </div>
        </div>
    </div>
    </div>
@endsection
@section('js')

    <script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>
    <script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>

    <script>
        let table = $('#exchange-table').DataTable({
            language: @include('admin.exchanges.partials.table-language'),

            processing: true,
            serverSide: true,
            order: [
                [1, 'desc']
            ],

            ajax: {
                url: '{{ route('admin.exchanges.data') }}',
                data: function(d) {

                    d.type = $('#filter_type').val();
                    d.mode = $('#filter_mode').val();
                    d.date_from = $('#date_from').val();
                    d.date_to = $('#date_to').val();

                }
            },

            columns: [

                {
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'date'
                },

                {
                    data: 'type',
                    render: function(t) {

                        if (t == 'buy') {
                            return `<span class="exchange-pill exchange-buy">{{ __('ui.blade_buy') }}</span>`;
                        }

                        return `<span class="exchange-pill exchange-sell">{{ __('ui.blade_sell_2') }}</span>`;

                    }
                },

                {
                    data: 'pair'
                },

                {
                    data: 'from_amount_display',
                    className: 'text-end amount-mono'
                },

                {
                    data: 'to_amount_display',
                    className: 'text-end amount-mono'
                },

                {
                    data: 'rate_display',
                    className: 'text-end amount-mono'
                },

                {
                    data: 'profit_display',
                    className: 'text-end amount-mono'
                },

                {
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }

            ]

        });

        $('#filter_type,#filter_mode,#date_from,#date_to').on('change', function() {
            table.draw();
        });

        const escapeExchangeText = value => String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[char]);

        table.on('xhr', function() {
            const json = table.ajax.json();
            if (!json) return;

            const summaryCards = [
                { label: @json(__('ui.blade_total_volume')), value: json.totalVolume, icon: 'bi-graph-up-arrow', tone: 'volume' },
                { label: @json(__('ui.blade_total_profit')), value: json.totalProfit, icon: 'bi-cash-coin', tone: 'profit' },
                { label: @json(__('ui.blade_total_exchanges')), value: json.totalCount, icon: 'bi-arrow-left-right', tone: 'count' }
            ];

            $('#exchange_summary').html(summaryCards.map(card => `
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="exchange-summary-card ${card.tone}">
                        <div class="exchange-summary-head">
                            <span class="exchange-summary-title">${escapeExchangeText(card.label)}</span>
                            <span class="exchange-summary-icon"><i class="bi ${card.icon}" aria-hidden="true"></i></span>
                        </div>
                        <div class="exchange-summary-value" dir="ltr">${escapeExchangeText(card.value ?? '—')}</div>
                    </div>
                </div>
            `).join(''));
        });

        $(document).on('click', '.view-btn', function() {

            let id = $(this).data('id');

            $.get('/admin/exchanges/' + id, function(res) {

                const details = [
                    { label: @json(__('ui.date')), value: res.date },
                    { label: @json(__('ui.type')), value: ({buy: @json(__('ui.blade_buy')), sell: @json(__('ui.blade_sell'))})[res.type] ?? res.type },
                    { label: @json(__('ui.blade_from')), value: res.from_amount },
                    { label: @json(__('ui.blade_to')), value: res.to_amount },
                    { label: @json(__('ui.blade_profit')), value: res.profit }
                ];
                $('#exchange_view_content').html('<div class="row g-2">' + details.map((item, index) => `
                    <div class="${index === 4 ? 'col-12' : 'col-6'}">
                        <div class="exchange-detail-field">
                            <small>${escapeExchangeText(item.label)}</small>
                            <strong>${escapeExchangeText(item.value ?? '—')}</strong>
                        </div>
                    </div>
                `).join('') + '</div>');

                $('#exchangeViewModal').modal('show');

            });

        });

        $(document).on('click', '.delete-btn', function() {

            let id = $(this).data('id');

            Swal.fire({
                title: @json(__('exchanges.delete_confirm')),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: @json(__('exchanges.delete')),
                cancelButtonText: @json(__('ui.cancel')),
                confirmButtonColor: '#c84c60',
                reverseButtons: true
            }).then(result => {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: '/admin/exchanges/' + id,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function() { table.ajax.reload(null, false); },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: @json(__('ui.blade_error')),
                            text: xhr.responseJSON?.message || @json(__('exchanges.failed'))
                        });
                    }
                });
            });

        });
    </script>

@endsection
