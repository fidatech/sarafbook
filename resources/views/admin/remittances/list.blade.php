@extends('layouts.admin.base')

@section('title', $pageTitle ?? __('ui.remittances'))

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/ad2e98fea92964b2.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/febd1387d851569a.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/d55d61f8152b62c7.css') }}">

    {{-- Date Range Picker --}}
    <link rel="stylesheet" href="{{ asset('assets/offline/3218bf0b4c8ec67e.css') }}">

    <style>
        :root {
            --fin-bg: #f3f6fc;
            --card-radius: 20px;
            --shadow-sm: 0 8px 20px rgba(0, 0, 0, 0.02), 0 2px 6px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 12px 28px rgba(0, 0, 0, 0.05);
            --primary: #2c6e9e;
            --primary-dark: #1f4e72;
            --success: #2c7a4d;
            --warning: #b86b2c;
            --danger: #b02a37;
            --info: #2c7a7a;
        }

        body {
            background: var(--fin-bg);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }

        /* Modern Card */
        .card-modern {
            border: none;
            border-radius: var(--card-radius);
            background: #ffffff;
            box-shadow: var(--shadow-md);
            transition: all 0.2s ease;
            overflow: hidden;
        }

        .card-header-modern {
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.2rem 1.5rem;
        }

        /* Filter Section */
        .filter-section {
            background: #ffffff;
            border-bottom: 1px solid #edf2f7;
            padding: 1rem 1.5rem 1.2rem;
        }

        .filter-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #5a6e8a;
            margin-bottom: 0.4rem;
        }

        .btn-pill {
            border-radius: 40px !important;
            padding: 0.45rem 1.2rem;
            font-weight: 600;
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .btn-pill i {
            font-size: 0.9rem;
        }

        /* Table Styling */
        .data-table-wrapper {
            padding: 0 1.5rem 1.5rem 1.5rem;
        }

        #remittances-table {
            width: 100% !important;
            font-size: 0.72rem;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
        }

        #remittances-table thead th {
            background: #f9fbfe;
            color: #334155;
            font-weight: 700;
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 0;
            padding: 0.7rem 0.35rem;
            border-bottom: 1px solid #e4e9f2;
            white-space: nowrap;
        }

        #remittances-table tbody td {
            padding: 0.65rem 0.35rem;
            vertical-align: middle;
            border-bottom: 1px solid #f0f2f6;
            color: #1e2a3e;
            font-weight: 500;
        }

        #remittances-table tbody tr:hover {
            background-color: #fafcff;
            transition: 0.1s;
        }


        #remittances-table th,
        #remittances-table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #remittances-table .badge-modern {
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
            padding: 0.2rem 0.4rem;
            font-size: 0.64rem;
        }

        #remittances-table .mono-number {
            font-family: inherit;
            font-variant-numeric: tabular-nums;
            letter-spacing: 0;
        }

        #remittances-table .btn-group {
            display: inline-flex;
            width: 100%;
            max-width: 150px;
            vertical-align: middle;
        }

        #remittances-table .btn-group .btn {
            flex: 1 1 0;
            min-width: 0;
            padding: 0.3rem 0;
            font-size: 0.72rem;
            line-height: 1.3;
        }

        .data-table-wrapper,
        #remittances-table_wrapper {
            min-width: 0;
            max-width: 100%;
        }

        #remittances-table .remittance-date,
        #remittances-table .remittance-time {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #remittances-table .remittance-time {
            margin-top: 0.15rem;
            font-size: 0.65rem;
            font-variant-numeric: tabular-nums;
        }

        /* Badges Modern */
        .badge-modern {
            padding: 0.3rem 0.7rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.7rem;
            letter-spacing: 0.01em;
            display: inline-block;
            text-align: center;
            min-width: 70px;
        }

        .badge-incoming {
            background: #e1f0fa;
            color: #1a6d9f;
        }

        .badge-outgoing {
            background: #e6f2ef;
            color: #2c7a4d;
        }

        .badge-transfer {
            background: #fff0e0;
            color: #b16e2e;
        }

        .badge-pending {
            background: #fff1e0;
            color: #c97e2c;
        }

        .badge-paid {
            background: #e0f2e9;
            color: #1f7840;
        }

        .badge-rejected {
            background: #fee9e9;
            color: #c73a2b;
        }

        /* Action Buttons */
        .action-btn {
            padding: 0.3rem 0.6rem;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 600;
            margin: 0 2px;
            transition: all 0.15s;
        }

        .action-btn i {
            font-size: 0.75rem;
            margin-right: 3px;
        }

        .mono-number {
            font-family: 'SF Mono', 'JetBrains Mono', monospace;
            font-weight: 700;
            letter-spacing: -0.2px;
        }

        /* DataTables Overrides */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 30px;
            border: 1px solid #dee2e8;
            padding: 0.4rem 1rem;
            font-size: 0.8rem;
            background-color: #ffffff;
            width: 260px;
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 30px;
            padding: 0.2rem 1.8rem 0.2rem 0.8rem;
            border-color: #dee2e8;
        }

        /* Small adjustments */
        @media (max-width: 768px) {
            .filter-section .row {
                gap: 0.8rem;
            }

            #remittances-table {
                font-size: 0.7rem;
            }

            .action-btn span {
                display: none;
            }

            .action-btn i {
                margin-right: 0;
            }
        }

        /* Remittance list: consistent banking UI. Shared by all list presets. */
        .remittances-list-page{--rl-ink:#1c304e;--rl-muted:#65768d;--rl-line:#e5ebf3;max-width:1840px;margin-inline:auto;padding:12px 0 25px;color:var(--rl-ink)}
        .remittances-list-hero{display:flex;align-items:center;justify-content:space-between;gap:18px;background:linear-gradient(113deg,#182a49,#29416e 62%,#3659a4);color:#fff;border-radius:17px;padding:19px 23px;margin-bottom:15px;box-shadow:0 12px 28px rgba(28,46,88,.13);position:relative;overflow:hidden}
        .remittances-list-hero:after{content:"";position:absolute;inset-inline-end:-72px;top:-110px;border:27px solid rgba(255,255,255,.045);border-radius:50%;height:195px;width:195px;pointer-events:none}
        .remittances-list-hero .remittances-hero-copy,.remittances-create-action{position:relative;z-index:1}
        .remittances-eyebrow{display:inline-flex;gap:7px;align-items:center;color:#bfd2fa;font-size:.69rem;font-weight:800;letter-spacing:.045em}
        .remittances-list-hero h1{color:#fff;font-size:1.43rem;font-weight:800;letter-spacing:-.025em;margin:4px 0}
        .remittances-list-hero p{color:#d4def3;font-size:.76rem;margin:0}
        .remittances-create-action{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;padding:9px 14px;border-radius:10px;background:#fff;color:#284a89;border:1px solid rgba(255,255,255,.85);font-weight:800;font-size:.77rem;white-space:nowrap;box-shadow:0 4px 12px rgba(16,28,58,.13)}
        .remittances-create-action:hover{background:#eaf1ff;color:#1c3a75}
        .remittances-list-page .remittances-list-card{border:1px solid var(--rl-line);border-radius:15px;box-shadow:0 4px 17px rgba(29,46,83,.045);background:#fff;overflow:hidden}
        .remittances-table-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid #edf1f6;background:#fff}
        .remittances-heading-copy{display:flex;gap:11px;align-items:center;min-width:0}
        .remittances-heading-icon{width:39px;height:39px;border-radius:11px;display:grid;place-items:center;flex:0 0 39px;background:#eaf0ff;color:#3c61a9;font-size:1.1rem}
        .remittances-heading-copy strong{font-size:.94rem;font-weight:800;color:#263d5f;display:block}
        .remittances-heading-copy small{font-size:.69rem;color:#7889a0;display:block;margin-top:2px}
        .remittances-list-page .filter-section{padding:14px 18px 16px;background:#fafcff;border-bottom:1px solid #e9eef5}
        .remittances-filter-heading{display:flex;align-items:center;gap:8px;color:#405c86;font-size:.77rem;font-weight:800;margin-bottom:11px}
        .remittances-filter-heading i{color:#4a6fb4}
        .remittances-list-page .filter-label{display:block;font-size:.7rem;font-weight:750;text-transform:none;letter-spacing:0;color:#61738c;margin-bottom:6px}
        .remittances-list-page .filter-section .form-control,.remittances-list-page .filter-section .form-select{background:#fff!important;border:1px solid #dce5ef!important;border-radius:9px!important;box-shadow:none!important;min-height:41px;height:41px;padding:8px 11px;font-size:.78rem;color:#293e5d}
        .remittances-list-page .filter-section :is(.form-control,.form-select):focus{border-color:#8da7dc!important;box-shadow:0 0 0 3px rgba(79,115,189,.12)!important}
        .remittances-list-page .filter-section .select2-container{max-width:100%}
        .remittances-list-page .filter-section .select2-container--bootstrap-5 .select2-selection{min-height:41px;height:41px;border:1px solid #dce5ef;border-radius:9px;background:#fff;display:flex;align-items:center;padding:5px 10px}
        .remittances-list-page .filter-section .select2-selection__rendered{font-size:.79rem;color:#293e5d}
        .remittances-filter-actions{display:flex;align-items:end;justify-content:flex-end;gap:9px}
        .remittances-filter-actions .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:41px;padding:9px 13px;border-radius:9px!important;font-size:.77rem;font-weight:800;white-space:nowrap}
        .remittances-filter-actions .btn-primary{background:#3159a7;border-color:#3159a7}
        .remittances-filter-actions .btn-primary:hover{background:#24488c;border-color:#24488c}
        .remittances-filter-actions .btn-outline-secondary{background:#fff;border-color:#d7e1ee;color:#465d7e}
        .remittances-filter-actions .btn-outline-secondary:hover{background:#edf3fb}
        .remittances-list-page .data-table-wrapper{padding:14px 16px 16px;min-width:0;max-width:100%}
        .remittances-list-page .remittances-table-scroll{overflow-x:auto;max-width:100%;border:1px solid #e7edf5;border-radius:11px;scrollbar-width:thin}
        .remittances-list-page #remittances-table{width:100%!important;min-width:1290px!important;table-layout:auto!important;border-collapse:separate;border-spacing:0;font-size:.77rem;margin:0!important}
        .remittances-list-page #remittances-table th,.remittances-list-page #remittances-table td{white-space:nowrap;overflow:visible;text-overflow:clip}
        .remittances-list-page #remittances-table thead th{background:#f3f6fb;font-size:.69rem;color:#60718a;letter-spacing:.02em;padding:12px 11px;font-weight:800;border-bottom:1px solid #e7edf5}
        .remittances-list-page #remittances-table tbody td{padding:12px 11px;font-size:.76rem;color:#3a4e6b;font-weight:550;border-bottom:1px solid #edf1f6;vertical-align:middle}
        .remittances-list-page #remittances-table tbody tr:hover td{background:#fafcff}
        .remittances-list-page #remittances-table .mono-number{font-family:inherit;font-variant-numeric:tabular-nums;font-weight:800;letter-spacing:-.012em}
        .remittances-list-page #remittances-table .badge-modern{padding:6px 9px;border-radius:7px;max-width:none;overflow:visible;font-size:.7rem;font-weight:800;min-width:0}
        .remittances-list-page .badge-incoming{background:#e8f1fd;color:#2b5d9b}
        .remittances-list-page .badge-outgoing{background:#e7f7f0;color:#177d61}
        .remittances-list-page .badge-pending{background:#fff3e5;color:#a96b22}
        .remittances-list-page .badge-paid{background:#e5f7ee;color:#178056}
        .remittances-list-page .badge-rejected{background:#fcebee;color:#bb465c}
        .remittances-list-page #remittances-table .btn-group{display:inline-flex;width:auto;max-width:none;gap:5px;vertical-align:middle}
        .remittances-list-page #remittances-table .btn-group .btn{flex:0 0 auto;min-width:31px;min-height:31px;padding:6px 8px;border-radius:8px!important;line-height:1.25;font-weight:750}
        .remittances-list-page .action-btn{border-radius:8px;padding:6px 9px;font-weight:750;font-size:.7rem}
        .remittances-list-page .action-btn i{font-size:.83rem}
        .remittances-list-page #remittances-table .remittance-date,.remittances-list-page #remittances-table .remittance-time{overflow:visible;text-overflow:clip}
        .remittances-list-page .dataTables_wrapper .dataTables_filter input,.remittances-list-page .dataTables_wrapper .dataTables_length select{border:1px solid #dce5f0;border-radius:8px;min-height:36px;padding:6px 9px;background:#fff;font-size:.76rem}
        .remittances-list-page .dataTables_wrapper .dataTables_filter,.remittances-list-page .dataTables_wrapper .dataTables_length,.remittances-list-page .dataTables_wrapper .dataTables_info,.remittances-list-page .dataTables_wrapper .dataTables_paginate{font-size:.75rem;color:#697a90}
        .remittances-list-page .dataTables_wrapper>.row{row-gap:11px}
        .remittances-list-page :is(button,a,input,select):focus-visible{outline:3px solid #96b0e4;outline-offset:2px}
        @media(max-width:767px){.remittances-list-hero{padding:16px}.remittances-list-hero h1{font-size:1.2rem}.remittances-list-hero p{font-size:.7rem}.remittances-create-action{padding:9px}.remittances-create-action span{display:none}.remittances-table-heading{padding:13px}.remittances-list-page .filter-section{padding:13px}.remittances-filter-actions .btn{flex:1}.remittances-list-page .data-table-wrapper{padding:10px}.remittances-list-page #remittances-table{min-width:1250px!important}}
        @media(prefers-reduced-motion:reduce){.remittances-list-page .action-btn{transition:none}}
    </style>
@endsection

@section('content')
    <div class="container-fluid remittances-list-page">
        <header class="remittances-list-hero">
            <div class="remittances-hero-copy">
                <span class="remittances-eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i> {{ __('ui.remittances') }}</span>
                <h1>{{ $pageTitle ?? __('ui.remittances') }}</h1>
                <p>{{ __('remittances.list_description') }}</p>
            </div>
            <a href="{{ route('admin.remittances.createNew') }}" class="btn remittances-create-action">
                <i class="bi bi-plus-lg" aria-hidden="true"></i><span>{{ __('ui.new_remittance') }}</span>
            </a>
        </header>

        {{-- Main Card --}}
        <div class="card-modern remittances-list-card">
            <div class="remittances-table-heading">
                <div class="remittances-heading-copy">
                    <span class="remittances-heading-icon"><i class="bi bi-send-check" aria-hidden="true"></i></span>
                    <div><strong>{{ $pageTitle ?? __('ui.remittances') }}</strong><small>{{ __('remittances.list_description') }}</small></div>
                </div>
            </div>
            {{-- Filter Bar --}}
            <div class="filter-section">
                <div class="remittances-filter-heading"><i class="bi bi-funnel" aria-hidden="true"></i><span>{{ __('ui.apply') }}</span></div>
                <div class="row g-2 align-items-end">
                    <div class="col-md-6 col-xl-3">
                        <label for="f_account_id" class="filter-label">{{ __('remittances.saraf_account') }}</label>
                        <select id="f_account_id" class="select2 form-select-sm rounded-3 bg-light form-select border-0">
                            <option value="">{{ __('ui.blade_all_sarafs') }}</option>
                            @foreach ($sarafs as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-xl-2">
                        <label for="f_currency_id" class="filter-label">{{ __('ui.currency') }}</label>
                        <select id="f_currency_id" class="select2 form-select-sm rounded-3 bg-light form-select border-0">
                            <option value="">{{ __('ui.all_currencies') }}</option>
                            @foreach ($currencies as $c)
                                <option value="{{ $c->id }}">{{ $c->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="f_date_range" class="filter-label">{{ __('ui.blade_transaction_date') }}</label>
                        <input type="text" id="f_date_range" class="form-control form-control-sm rounded-3 bg-light border-0" placeholder="{{ __('ui.select_date_range') }}">
                    </div>
                    <div class="col-md-6 col-xl-4 remittances-filter-actions">
                        <button type="button" id="btnFilter" class="btn btn-primary btn-pill">
                            <i class="bi bi-search me-1"></i> {{ __('ui.apply') }}
                        </button>
                        <button type="button" id="btnReset" class="btn btn-outline-secondary btn-pill">
                            <i class="bi bi-arrow-repeat me-1"></i> {{ __('remittances.reset_filters') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- DataTable Area --}}
            <div class="data-table-wrapper">
                <div class="remittances-table-scroll table-responsive">
                <table id="remittances-table" class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('ui.date') }}</th>
                            <th>{{ __('ui.type') }}</th>
                            <th>{{ __('remittances.list_status') }}</th>
                            <th>{{ __('ui.blade_hawala_2') }}</th>
                            <th title="{{ __('remittances.track_hawala_no') }}">{{ __('remittances.track_hawala_no') }}</th>
                            <th>{{ __('ui.sender') }}</th>
                            <th>{{ __('ui.receiver') }}</th>
                            <th>{{ __('remittances.from_account') }}</th>
                            <th>{{ __('remittances.to_account') }}</th>
                            <th class="text-end">{{ __('ui.amount') }}</th>
                            <th>{{ __('ui.commission') }}</th>
                            <th class="text-center">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden Presets (optional) --}}
    <input type="hidden" id="presetType" value="{{ $presetType ?? '' }}">
    <input type="hidden" id="presetStatus" value="{{ $presetStatus ?? '' }}">
@endsection

@section('js')

    <script src="{{ asset('assets/offline/33859893654b9de2.js') }}"></script>
    <script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>
    <script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>
    <script src="{{ asset('assets/offline/322e717b93d426e4.js') }}"></script>
    <script src="{{ asset('assets/offline/00b712c3c875b8cd.js') }}"></script>

    <script>
        const remittancesSwal = Swal.mixin({confirmButtonText: @json(__('remittances.ok')), cancelButtonText: @json(__('ui.cancel'))});
        // Helper formatting
        const formatMoney = (val) => {
            if (!val && val !== 0) return '-';
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(val);
        };

        // Initialize Select2
        $('.select2').select2({
            language: @include('admin.remittances.partials.select-language'),
            width: '100%',
            dropdownAutoWidth: true,
            theme: 'bootstrap-5'
        });

        // Date Range Picker config
        $('#f_date_range').daterangepicker({
            autoUpdateInput: false,
            opens: 'left',
            locale: {
                format: 'YYYY-MM-DD',
                cancelLabel: @json(__('remittances.clear')),
                applyLabel: @json(__('remittances.apply')),
                fromLabel: @json(__('remittances.from')),
                toLabel: @json(__('remittances.to')),
                customRangeLabel: @json(__('remittances.custom_range')),
                weekLabel: @json(__('remittances.week')),
                daysOfWeek: @json(__('remittances.days')),
                monthNames: @json(__('remittances.months')),
            },
            ranges: {
                [@json(__('ui.today'))]: [moment(), moment()],
                [@json(__('ui.yesterday'))]: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                [@json(__('remittances.last_7_days'))]: [moment().subtract(6, 'days'), moment()],
                [@json(__('ui.this_month'))]: [moment().startOf('month'), moment().endOf('month')],
                [@json(__('ui.blade_last_month'))]: [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });

        $('#f_date_range').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $('#f_date_range').on('cancel.daterangepicker', function() {
            $(this).val('');
        });

        // Initialize DataTable
        let table = $('#remittances-table').DataTable({
            language: @include('admin.remittances.partials.table-language'),
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            scrollX: false,
            pageLength: 25,
            order: [
                [1, 'desc']
            ],
            ajax: {
                url: "{{ route('admin.remittances.data') }}",
                data: function(d) {
                    d.type = $('#presetType').val() || null;
                    d.status = $('#presetStatus').val() || null;
                    d.account_id = $('#f_account_id').val();
                    d.currency_id = $('#f_currency_id').val();
                    d.date_range = $('#f_date_range').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                    className: 'fw-semibold'
                },
                {
                    data: 'date',
                    name: 'date',
                    className: 'fw-medium'
                },
                {
                    data: 'type',
                    name: 'type',
                    render: function(v) {
                        let typeClass = v === 'incoming' ? 'badge-incoming' : (v === 'outgoing' ? 'badge-outgoing' : 'badge-transfer');
                        let display = ({incoming: @json(__('remittances.incoming')), outgoing: @json(__('remittances.outgoing')), transfer: @json(__('remittances.transfer'))})[v] ?? (v ? v.charAt(0).toUpperCase() + v.slice(1) : '-');
                        return `<span class="badge-modern ${typeClass}">${display}</span>`;
                    }
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function(v) {
                        let statusClass = v === 'pending' ? 'badge-pending' : (v === 'paid' ? 'badge-paid' : 'badge-rejected');
                        let display = ({pending: @json(__('remittances.pending')), paid: @json(__('remittances.paid')), rejected: @json(__('remittances.rejected')), confirmed: @json(__('remittances.confirmed'))})[v] ?? (v ? v.charAt(0).toUpperCase() + v.slice(1) : '-');
                        return `<span class="badge-modern ${statusClass}">${display}</span>`;
                    }
                },
                {
                    data: 'hawala_number',
                    name: 'hawala_number',
                    defaultContent: '—',
                    className: 'font-monospace'
                },
                {
                    data: 'track_hawala_number',
                    name: 'track_hawala_number',
                    defaultContent: '—',
                    className: 'font-monospace'
                },
                {
                    data: 'sender',
                    name: 'sender',
                    defaultContent: '—'
                },
                {
                    data: 'receiver',
                    name: 'receiver',
                    defaultContent: '—'
                },
                {
                    data: 'from_name',
                    name: 'fromAccount.name',
                    defaultContent: '—'
                },
                {
                    data: 'to_name',
                    name: 'toAccount.name',
                    defaultContent: '—'
                },
                {
                    data: 'amount_currency',
                    name: @json(($presetType === 'incoming' && $presetStatus === 'pending') ? 'partner_amount' : 'amount'),
                    className: 'text-end mono-number',
                    render: function(v) {
                        return v ? `<span class="mono-number">${v}</span>` : '—';
                    }
                },
                {
                    data: 'commission_text',
                    name: 'commission_kabul_amount',
                    className: 'mono-number',
                    render: function(v) {
                        return v ? v : '—';
                    }
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ],
            drawCallback: function() {
                // Keep complete values available when a long cell is abbreviated.
                this.api().table().body().querySelectorAll('td:not(:last-child)').forEach(function(cell) {
                    cell.title = cell.textContent.trim();
                });
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                    if (!bootstrap.Tooltip.getInstance(el)) {
                        new bootstrap.Tooltip(el);
                    }
                });
            }
        });

        $(document).on('click', '.btn-remittance-whatsapp', function() {
            const url = $(this).data('url');

            remittancesSwal.fire({
                title: @json(__('remittances.send_whatsapp')),
                text: @json(__('remittances.prepare_whatsapp')),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: @json(__('remittances.yes_continue'))
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.get(url)
                    .done(function(response) {
                        window.open(response.url, '_blank', 'noopener');
                    })
                    .fail(function(xhr) {
                        remittancesSwal.fire(
                            @json(__('remittances.whatsapp_failed')),
                            xhr.responseJSON?.error || @json(__('remittances.message_failed')),
                            'error'
                        );
                    });
            });
        });

        // Filter actions
        $('#btnFilter').on('click', function() {
            table.ajax.reload();
        });

        $('#btnReset').on('click', function() {
            $('#f_account_id').val('').trigger('change');
            $('#f_currency_id').val('').trigger('change');
            $('#f_date_range').val('');
            table.ajax.reload();
        });

        // REJECT
        $(document).on('click', '.btn-reject', function() {
            let id = $(this).data('id');
            remittancesSwal.fire({
                icon: 'warning',
                title: '{{ __('ui.blade_reject_remittance') }}',
                text: '{{ __('ui.blade_if_paid_or_confirmed_linked_transactions_will_be_reversed') }}',
                showCancelButton: true,
                confirmButtonColor: '#b02a37',
                confirmButtonText: '{{ __('ui.blade_reject') }}'
            }).then((r) => {
                if (!r.isConfirmed) return;
                $.post("{{ url('/admin/remittances/reject') }}/" + id, {
                    _token: "{{ csrf_token() }}"
                }, function() {
                    remittancesSwal.fire({
                        icon: 'success',
                        title: '{{ __('ui.blade_rejected') }}',
                        toast: true,
                        timer: 1800
                    });
                    table.ajax.reload(null, false);
                });
            });
        });

        // DELETE
        $(document).on('click', '.btn-delete', function() {
            let id = $(this).data('id');
            remittancesSwal.fire({
                icon: 'error',
                title: '{{ __('ui.blade_delete_remittance') }}',
                text: '{{ __('ui.blade_this_action_is_irreversible_all_related_transactions_will_be_deleted') }}',
                showCancelButton: true,
                confirmButtonText: '{{ __('ui.blade_permanently_delete') }}',
                confirmButtonColor: '#a12a2a'
            }).then((r) => {
                if (!r.isConfirmed) return;
                $.ajax({
                    url: "{{ url('/admin/remittances') }}/" + id,
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        _method: 'DELETE'
                    },
                    success: function() {
                        remittancesSwal.fire({
                            icon: 'success',
                            title: '{{ __('ui.blade_deleted') }}',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        table.ajax.reload(null, false);
                    }
                });
            });
        });
    </script>
@endsection
