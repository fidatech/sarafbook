@extends('layouts.admin.base')

@section('title', __('transactions.title'))

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/febd1387d851569a.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/d55d61f8152b62c7.css') }}">

    <style>
        :root {
            --fin-success: #10b981;
            --fin-danger: #ef4444;
            --fin-bg: #f8fafc;
        }

        body {
            background-color: var(--fin-bg);
        }

        .card-modern {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
            padding: 1.5rem;
        }

        .section-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .section-label::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e2e8f0;
            margin-left: 1rem;
        }

        .entry-row {
            background: #ffffff;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: border-color 0.2s;
        }

        .entry-row:focus-within {
            border-color: #3b82f6;
        }

        #transactions-table thead th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            border: none;
            padding: 12px;
        }

        .badge-type {
            padding: 0.4em 0.8em;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .amount-font {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-weight: 600;
        }

        .balance-summary-item {
            padding: 0.5rem 0.75rem;
            border-left: 3px solid #3b82f6;
            background: #eff6ff;
            border-radius: 4px;
        }

        .mode-box {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            padding-top: 10px;
        }

        .mode-box label {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        /* Transactions: scoped banking-style interface */
        .transactions-page{--tx-ink:#1b2e4b;--tx-muted:#65758d;--tx-line:#e4ebf4;--tx-blue:#3157a5;max-width:1820px;margin-inline:auto;color:var(--tx-ink);padding-bottom:20px}
        .transactions-hero{display:flex;align-items:center;justify-content:space-between;gap:15px;background:linear-gradient(116deg,#192a48,#293f70 65%,#3658a0);color:#fff;border-radius:16px;padding:19px 23px;margin-bottom:15px;box-shadow:0 10px 28px rgba(29,48,90,.12);position:relative;overflow:hidden}
        .transactions-hero:after{content:"";position:absolute;width:200px;height:200px;border:28px solid rgba(255,255,255,.04);border-radius:50%;inset-inline-end:-70px;top:-110px;pointer-events:none}
        .transactions-hero>div{position:relative;z-index:1}
        .transactions-eyebrow{color:#bbd0ff;font-size:.68rem;display:inline-flex;align-items:center;gap:7px;font-weight:750;letter-spacing:.04em}
        .transactions-hero h2{font-size:1.43rem;font-weight:800;color:#fff;margin:4px 0;letter-spacing:-.02em}
        .transactions-hero p{font-size:.75rem;color:#d2def3;margin:0}
        .transactions-hero-icon{width:51px;height:51px;flex:0 0 51px;display:grid;place-items:center;border-radius:14px;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.1);font-size:1.4rem}
        .transactions-page .card-modern{padding:0;border:1px solid var(--tx-line);border-radius:15px;box-shadow:0 4px 16px rgba(30,47,81,.045);overflow:visible;background:#fff}
        .transactions-page .card-modern>.card-body{padding:19px 21px}
        .transactions-page .section-label{color:#405c88;font-size:.71rem;letter-spacing:.04em;font-weight:800;margin:4px 0 13px;gap:2px}
        .transactions-page .section-label:not(:first-child){margin-top:20px}
        .transactions-page .section-label:after{background:#eaf0f6;margin-inline-start:10px;margin-left:10px}
        .transactions-page .form-label{font-size:.72rem;font-weight:750!important;color:#536681;margin-bottom:6px;line-height:1.4}
        .transactions-page .form-control,.transactions-page .form-select{height:40px;border-radius:9px;border:1px solid #dce4ef;background:#fff;color:#263a57;font-size:.79rem;box-shadow:none;min-width:0}
        .transactions-page .form-control:focus,.transactions-page .form-select:focus{border-color:#7b9bda;box-shadow:0 0 0 3px rgba(75,112,188,.12)}
        .transactions-page .form-control[type="file"]{padding:8px 10px;font-size:.74rem}
        .transactions-page .input-group-text{height:40px;background:#f4f7fc!important;color:#58719a!important;border-color:#dce4ef!important;border-radius:9px 0 0 9px}
        .transactions-page .input-group>.form-control{height:40px}
        .transactions-page .select2-container--default .select2-selection--single{height:40px!important;border:1px solid #dce4ef!important;border-radius:9px!important;display:flex;align-items:center;padding:4px 9px!important}
        .transactions-page .select2-selection__rendered{font-size:.78rem!important;color:#263a57!important;line-height:28px!important;padding-inline-start:5px!important}
        .transactions-page .select2-selection__arrow{top:7px!important}
        .transactions-page #account-balance-summary{min-height:41px;background:#f6f9fd;border:1px dashed #dbe5f2;border-radius:10px;padding:9px;align-items:center}
        .transactions-page .balance-summary-item{padding:9px 13px;background:#fff;border:1px solid #e3eaf4;border-inline-start:3px solid #4b72bf;border-left:3px solid #4b72bf;border-radius:9px;min-width:112px}
        .transactions-page .balance-summary-item .fw-bold{font-size:.85rem;font-variant-numeric:tabular-nums}
        .transactions-page .entry-row{display:flex;align-items:center;padding:12px;background:#f9fbfe;border:1px solid #e4ebf5;border-radius:11px;margin:0 0 9px;transition:border-color .16s,background .16s}
        .transactions-page .entry-row:focus-within{background:#fff;border-color:#91ace0;box-shadow:0 0 0 3px rgba(72,111,183,.07)}
        .transactions-page .entry-row .form-control,.transactions-page .entry-row .form-select{background:#fff!important;border:1px solid #dfe7f0!important;border-radius:8px!important;padding:8px 10px!important}
        .transactions-page .entry-row .input-group-text{background:#fff!important;border:1px solid #dfe7f0!important;border-inline-end:0!important}
        .transactions-page .entry-row .input-group .form-control{border-inline-start:0!important}
        .transactions-page .entry-row .btn-link{display:inline-flex;justify-content:center;align-items:center;width:37px;height:37px;border-radius:9px;text-decoration:none}
        .transactions-page .entry-row .add-row{color:#3158a5!important;background:#eaf0ff}
        .transactions-page .entry-row .remove-row{background:#fff1f3}
        .transactions-page .entry-row .btn-link:hover{filter:brightness(.96)}
        .transactions-page .mode-box{display:flex;align-items:center;flex-wrap:wrap;gap:7px;padding-top:0}
        .transactions-page .mode-box label{cursor:pointer;display:inline-flex;gap:8px;align-items:center;padding:9px 12px;border:1px solid #dfe6f0;background:#f8fafd;border-radius:9px;font-size:.75rem;font-weight:750;color:#40536e;min-height:39px;transition:background .15s,border-color .15s}
        .transactions-page .mode-box label:has(input:checked){background:#eaf1ff;border-color:#98b2e4;color:#254e96}
        .transactions-page .mode-box label:focus-within{outline:2px solid #94ade0;outline-offset:2px}
        .transactions-page .mode-box input{accent-color:#3157a5;margin:0}
        .transactions-page .transaction-form-actions{display:flex;align-items:center;gap:9px;border-top:1px solid #ebf0f5;padding-top:17px;margin-top:20px;flex-wrap:wrap}
        .transactions-page .transaction-form-actions .btn{display:inline-flex;align-items:center;justify-content:center;min-height:41px;border-radius:9px;padding:9px 17px;font-size:.77rem;font-weight:800!important;box-shadow:none!important}
        .transactions-page .transaction-submit-btn{background:#2e58a9;border-color:#2e58a9}
        .transactions-page .transaction-submit-btn:hover{background:#234889;border-color:#234889}
        .transactions-page .transaction-cancel-btn{background:#f8fafc;border-color:#dae3ee}
        .transactions-page .transaction-table-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:#fff;border-bottom:1px solid #e9eef5;border-radius:15px 15px 0 0}
        .transactions-page .transaction-table-heading{display:flex;align-items:center;gap:11px}
        .transactions-page .transaction-list-icon{display:grid;place-items:center;width:40px;height:40px;border-radius:11px;background:#edf2ff;color:#385ea9;font-size:1.08rem}
        .transactions-page .transaction-table-heading h3{margin:0;color:#263b5c;font-size:.96rem;font-weight:800}
        .transactions-page .transaction-table-heading small{font-size:.68rem;color:#7b8ba0}
        .transactions-page .filter-card{background:#f8fafd;border:1px solid #e6ebf3;padding:13px 14px;border-radius:11px;margin-bottom:15px}
        .transactions-page .filter-card .form-label{margin-bottom:5px}
        .transactions-page .transaction-filter-actions{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;margin-top:3px}
        .transactions-page .transaction-filter-actions .btn{min-height:36px;padding:7px 13px;border-radius:8px;font-weight:750;font-size:.75rem}
        .transactions-page .transaction-filter-actions .btn-primary{background:#3157a5;border-color:#3157a5}
        .transactions-page .transaction-filter-actions .btn-outline-secondary{border-color:#d5dfed;color:#47607f;background:#fff}
        .transactions-page .transaction-table-wrap{border:1px solid #e8edf4;border-radius:11px;overflow-x:auto}
        .transactions-page #transactions-table{margin-bottom:0!important;min-width:990px;border-collapse:separate;border-spacing:0}
        .transactions-page #transactions-table thead th{padding:12px 11px;background:#f3f6fb;color:#5c6e86;font-size:.69rem;font-weight:800;letter-spacing:.035em;border-bottom:1px solid #e6ebf2;white-space:nowrap}
        .transactions-page #transactions-table tbody td{padding:12px 11px;vertical-align:middle;border-bottom:1px solid #edf1f6;font-size:.76rem;color:#3e506e}
        .transactions-page #transactions-table tbody tr:hover td{background:#f9fbff}
        .transactions-page #transactions-table .amount-font{font-family:inherit;letter-spacing:-.015em;font-variant-numeric:tabular-nums;font-weight:800;font-size:.81rem}
        .transactions-page .badge-type{border-radius:7px;font-size:.69rem;padding:5px 8px;font-weight:800}
        .transactions-page .dataTables_wrapper .dataTables_filter input,.transactions-page .dataTables_wrapper .dataTables_length select{border:1px solid #dfe6f0;border-radius:8px;padding:6px 9px;background:#fff;min-height:35px;font-size:.75rem}
        .transactions-page .dataTables_wrapper .dataTables_info,.transactions-page .dataTables_wrapper .dataTables_paginate,.transactions-page .dataTables_wrapper .dataTables_length,.transactions-page .dataTables_wrapper .dataTables_filter{font-size:.74rem;color:#667791}
        .transactions-page .dataTables_wrapper .row{row-gap:12px}
        .transactions-page button:focus-visible,.transactions-page a:focus-visible{outline:3px solid #91aee6;outline-offset:2px}
        @media(max-width:767px){.transactions-hero{padding:16px}.transactions-hero h2{font-size:1.21rem}.transactions-hero-icon{width:43px;height:43px;flex-basis:43px}.transactions-page .card-modern>.card-body{padding:14px}.transactions-page .transaction-table-header{padding:14px}.transactions-page .filter-card{padding:12px}.transactions-page .entry-row{gap:8px;padding:10px}.transactions-page .mode-box{gap:6px}.transactions-page .mode-box label{padding:8px 10px}.transactions-page .transaction-filter-actions .btn{flex:1}.transactions-page .transaction-form-actions .btn{flex:1}}
        @media(prefers-reduced-motion:reduce){.transactions-page .entry-row,.transactions-page .mode-box label{transition:none}}
    </style>
@endsection

@section('content')
    <div class="transactions-page">
        <header class="transactions-hero">
            <div>
                <span class="transactions-eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i> {{ __('ui.blade_general_information') }}</span>
                <h2>{{ __('transactions.title') }}</h2>
                <p>{{ __('ui.blade_transaction_entries') }} · {{ __('transactions.recent_activity') }}</p>
            </div>
            <div class="transactions-hero-icon" aria-hidden="true"><i class="bi bi-arrow-left-right"></i></div>
        </header>
    <div class="row g-3">

        {{-- ===================== FORM ===================== --}}
        <div class="col-lg-12">
            <div class="card card-modern transaction-form-card">
                <div class="card-body">
                    <form id="transactionForm" enctype="multipart/form-data">
                        @csrf

                        <input type="hidden" id="edit_mode" value="0">
                        <input type="hidden" id="linked_id">

                        <div class="section-label"><i class="bi bi-journal-text me-2" aria-hidden="true"></i>{{ __('ui.blade_general_information') }}</div>

                        <div class="row g-3 mb-5">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">{{ __('ui.blade_transaction_date') }}</label>
                                <input type="date" name="date" id="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">{{ __('ui.blade_target_account') }}</label>
                                <select name="account_id" id="account_id" class="select2 form-select" required>
                                    <option value="">{{ __('ui.select_account') }}</option>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}">
                                            {{ $acc->name }} ({{ __('transactions.types')[$acc->subCategory->name ?? ''] ?? ($acc->subCategory->name ?? '') }}) - [{{ $acc->code }}]
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">{{ __('ui.attachment') }}</label>
                                <input type="file" name="attachment" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">{{ __('ui.blade_reference') }}</label>
                                <input type="text" name="reference" id="reference" class="form-control" placeholder="{{ __('ui.blade_reference_voucher_no') }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">{{ __('transactions.current_balances') }}</label>
                                <div id="account-balance-summary" class="d-flex flex-wrap gap-2">
                                    <span class="text-muted small">{{ __('ui.blade_select_an_account_to_view_liquidity') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="section-label"><i class="bi bi-list-check me-2" aria-hidden="true"></i>{{ __('ui.blade_transaction_entries') }}</div>

                        <div id="entriesWrapper">
                            <div class="entry-row row g-2 align-items-center">
                                <div class="col-md-2">
                                    <select name="entries[0][type]" class="bg-light form-select border-0 shadow-none" required>
                                        <option value="credit">{{ __('ui.credit') }} (+)</option>
                                        <option value="debit">{{ __('ui.debit') }} (-)</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <select name="entries[0][currency_id]" class="select2 form-select" required>
                                        @foreach ($currencies as $c)
                                            <option value="{{ $c->id }}">{{ $c->code }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <div class="input-group">
                                        <span class="input-group-text border-end-0 text-muted bg-white">
                                            <i class="bi bi-cash"></i>
                                        </span>

                                        
                                        <input type="text" name="entries[0][amount]" class="form-control border-start-0 transaction-amount ps-0" inputmode="decimal"
                                            placeholder="0" required>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <input type="text" name="entries[0][note]" class="form-control bg-light border-0 shadow-none" placeholder="{{ __('ui.blade_memo_description') }}">
                                </div>

                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-link text-primary add-row p-0" title="{{ __('ui.blade_add_another_entry') }}">
                                        <i class="bi bi-plus-circle-fill fs-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="section-label"><i class="bi bi-diagram-3 me-2" aria-hidden="true"></i>{{ __('ui.blade_transaction_mode') }}</div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label small">{{ __('ui.mode') }}</label>

                                <div class="mode-box">
                                    <label>
                                        <input type="radio" name="transaction_mode" value="track">
                                        {{ __('ui.track') }}
                                    </label>

                                    <label>
                                        <input type="radio" name="transaction_mode" value="no_track">
                                        {{ __('transactions.no_track') }}
                                    </label>

                                    <label>
                                        <input type="radio" name="transaction_mode" value="treasure" checked>
                                        {{ __('ui.treasure') }}
                                    </label>
                                </div>
                            </div>

                            <div class="col-lg-5 track-account-wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.track_account') }}</label>
                                <select name="track_account_id" id="track_account_id" class="select2 form-select">
                                    <option value="">{{ __('transactions.select_track') }}</option>
                                    @foreach ($accounts as $a)
                                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="transaction-form-actions">
                            <button type="submit" id="submitBtn" class="btn btn-primary transaction-submit-btn">
                                <i class="bi bi-send-fill me-2"></i>
                                {{ __('transactions.submit') }}
                            </button>

                            <button type="button" id="cancelEditBtn" class="btn btn-outline-secondary d-none ms-2 transaction-cancel-btn">
                                {{ __('transactions.cancel_edit') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===================== TABLE ===================== --}}
        <div class="col-lg-12">
            <div class="card card-modern transaction-list-card">

                <div class="card-header transaction-table-header">
                    <div class="transaction-table-heading">
                        <span class="transaction-list-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
                        <div><h3>{{ __('transactions.recent_activity') }}</h3><small>{{ __('transactions.title') }}</small></div>
                    </div>
                </div>

                <div class="card-body">

                    {{-- Filters --}}
                    <div class="filter-card">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">{{ __('ui.blade_from_date') }}</label>
                                <input type="date" id="filter_start_date" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">{{ __('ui.blade_to_date') }}</label>
                                <input type="date" id="filter_end_date" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">{{ __('ui.currency') }}</label>
                                <select id="filter_currency_id" class="form-select">
                                    <option value="">{{ __('ui.all_currencies') }}</option>
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">{{ __('ui.type') }}</label>
                                <select id="filter_transaction_type" class="form-select">
                                    <option value="">{{ __('ui.blade_all_types') }}</option>
                                    <option value="credit">{{ __('ui.credit') }}</option>
                                    <option value="debit">{{ __('ui.debit') }}</option>
                                </select>
                            </div>

                            <div class="col-12 transaction-filter-actions">
                                <button type="button" id="applyFilters" class="btn btn-primary btn-sm">
                                    <i class="bi bi-funnel me-1" aria-hidden="true"></i>{{ __('transactions.apply_filters') }}
                                </button>
                                <button type="button" id="resetFilters" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>{{ __('ui.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive transaction-table-wrap">
                        <table id="transactions-table" class="table-hover mb-0 table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.blade_ref') }}</th>
                                    <th>{{ __('ui.date') }}</th>
                                    <th>{{ __('ui.account') }}</th>
                                    <th style="width: 30%;">{{ __('ui.note') }}</th>
                                    <th>{{ __('ui.type') }}</th>
                                    <th class="text-center">{{ __('ui.amount') }}</th>
                                    <th class="text-center">{{ __('ui.currency') }}</th>
                                    <th class="text-center">{{ __('ui.blade_reference') }}</th>
                                    <th>{{ __('ui.attachment') }}</th>
                                    <th class="text-center">{{ __('ui.action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('assets/offline/33859893654b9de2.js') }}"></script>
    <script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>
    <script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>

    <script>
        const transactionsSwal = Swal.mixin({confirmButtonText: @json(__('transactions.ok')), cancelButtonText: @json(__('ui.cancel'))});
        const formatCurrency = (val) => {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(val || 0);
        };

        function refreshPlugins() {
            if ($.fn.select2) {
                $('.select2').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }

                    $(this).select2({
                        language: @include('admin.transactions.partials.select-language'),
                        width: '100%'
                    });
                });
            }
        }

        refreshPlugins();

        function convertToEnglishNumbers(value) {
            if (value === null || value === undefined) return '';

            return String(value)
                .replace(/[۰-۹]/g, function(d) {
                    return String.fromCharCode(d.charCodeAt(0) - 1728);
                })
                .replace(/[٠-٩]/g, function(d) {
                    return String.fromCharCode(d.charCodeAt(0) - 1584);
                })
                .replace(/[٫]/g, '.')
                .replace(/[٬]/g, '')
                .replace(/[،]/g, '');
        }

        function sanitizeAmount(value) {
            value = convertToEnglishNumbers(value);

            return value
                .replace(/[^0-9.]/g, '')
                .replace(/(\..*)\./g, '$1');
        }

        let rowIndex = 1;

        function getCurrencyOptions() {
            return `
                @foreach ($currencies as $c)
                    <option value="{{ $c->id }}">{{ $c->code }}</option>
                @endforeach
            `;
        }

        function resetForm() {
            $('#transactionForm')[0].reset();

            $('#edit_mode').val(0);
            $('#linked_id').val('');
            $('#transaction_date').val("{{ date('Y-m-d') }}");
            $('#reference').val('');

            $('#account_id').val('').trigger('change');
            $('#track_account_id').val('').trigger('change');

            $('input[name="transaction_mode"][value="treasure"]').prop('checked', true);
            $('.track-account-wrap').hide();

            $('#entriesWrapper').html(`
                <div class="entry-row row g-2 align-items-center">
                    <div class="col-md-2">
                        <select name="entries[0][type]" class="bg-light form-select border-0 shadow-none" required>
                            <option value="credit">{{ __('ui.credit') }} (+)</option>
                            <option value="debit">{{ __('ui.debit') }} (-)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="entries[0][currency_id]" class="select2 form-select" required>
                            ${getCurrencyOptions()}
                        </select>
                    </div>

                    <div class="col-md-2">
                        <div class="input-group">
                            <span class="input-group-text border-end-0 text-muted bg-white">
                                <i class="bi bi-cash"></i>
                            </span>
                            <input type="text" name="entries[0][amount]" class="form-control border-start-0 transaction-amount ps-0" inputmode="decimal" placeholder="0" required>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <input type="text" name="entries[0][note]" class="form-control bg-light border-0 shadow-none" placeholder="{{ __('ui.blade_memo_description') }}">
                    </div>

                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-link text-primary add-row p-0" title="{{ __('ui.blade_add_another_entry') }}">
                            <i class="bi bi-plus-circle-fill fs-4"></i>
                        </button>
                    </div>
                </div>
            `);

            rowIndex = 1;
            refreshPlugins();

            $('#submitBtn').html('<i class="bi bi-send-fill me-2"></i> ' + @json(__('transactions.submit')));
            $('#cancelEditBtn').addClass('d-none');

            $('#account-balance-summary').html('<span class="text-muted small">{{ __('ui.blade_select_an_account_to_view_liquidity') }}</span>');
        }

        $(document).on('click', '.add-row', function() {
            let row = `
                <div class="entry-row row g-2 align-items-center">
                    <div class="col-md-2">
                        <select name="entries[${rowIndex}][type]" class="form-select border-0 bg-light shadow-none" required>
                            <option value="credit">{{ __('ui.credit') }} (+)</option>
                            <option value="debit">{{ __('ui.debit') }} (-)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="entries[${rowIndex}][currency_id]" class="form-select select2" required>
                            ${getCurrencyOptions()}
                        </select>
                    </div>

                    <div class="col-md-2">
                        <input type="text" name="entries[${rowIndex}][amount]" class="form-control transaction-amount" inputmode="decimal" placeholder="0" required>
                    </div>

                    <div class="col-md-5">
                        <input type="text" name="entries[${rowIndex}][note]" class="form-control border-0 bg-light shadow-none" placeholder="{{ __('ui.blade_memo') }}">
                    </div>

                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-link text-danger p-0 remove-row">
                            <i class="bi bi-x-circle-fill fs-4"></i>
                        </button>
                    </div>
                </div>
            `;

            $('#entriesWrapper').append(row);
            refreshPlugins();
            rowIndex++;
        });

        $(document).on('click', '.remove-row', function() {
            $(this).closest('.entry-row').remove();
        });

        $('#account_id').on('change', function() {
            let id = $(this).val();

            if (!id) {
                $('#account-balance-summary').html('<span class="text-muted small">{{ __('ui.blade_select_an_account') }}</span>');
                return;
            }

            $.get("{{ route('admin.transactions.account-balances') }}", {
                account_id: id
            }, function(res) {
                let html = '';

                if (!res.balances || Object.keys(res.balances).length === 0) {
                    html = '<span class="text-muted small">{{ __('ui.blade_no_balance_found') }}</span>';
                } else {
                    Object.entries(res.balances).forEach(([cur, b]) => {
                        html += `
                            <div class="balance-summary-item">
                                <div class="small text-muted text-uppercase fw-bold" style="font-size:0.65rem">${cur}</div>
                                <div class="fw-bold ${b.balance < 0 ? 'text-danger' : 'text-dark'}">${formatCurrency(b.balance)}</div>
                            </div>
                        `;
                    });
                }

                $('#account-balance-summary').html(html);
            });
        });

        let table = $('#transactions-table').DataTable({
            language: @include('admin.transactions.partials.table-language'),
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.transactions.data') }}",
                data: function(d) {
                    let startDate = $('#filter_start_date').val();
                    let endDate = $('#filter_end_date').val();

                    if (startDate && endDate) {
                        d.date_range = startDate + ' - ' + endDate;
                    }

                    d.currency_id = $('#filter_currency_id').val();
                    d.transaction_type = $('#filter_transaction_type').val();
                }
            },
            rowId: 'id',
            order: [
                [1, 'desc']
            ],
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function(data) {
                        return `<span class="text-muted small">${data}</span>`;
                    }
                },
                {
                    data: 'account_name',
                    name: 'account_name',
                    render: function(data) {
                        return `<span class="fw-semibold">${data}</span>`;
                    }
                },
                {
                    data: 'note',
                    name: 'note'
                },
                {
                    data: 'transaction_type',
                    name: 'transaction_type',
                    render: function(data) {
                        let color = data === 'credit' ? 'bg-success' : 'bg-danger';
                        return `<span class="badge ${color} badge-type text-uppercase">${({credit: @json(__('ui.credit')), debit: @json(__('ui.debit'))})[data] ?? data}</span>`;
                    }
                },
                {
                    data: 'amount',
                    name: 'amount',
                    className: 'text-center'
                },
                {
                    data: 'currency_code',
                    name: 'currency_code',
                    className: 'text-center'
                },
                {
                    data: 'reference',
                    name: 'reference',
                    className: 'text-center',
                    render: function(data) {
                        return data ? `<span class="badge bg-light text-dark border">${data}</span>` : '-';
                    }
                },
                {
                    data: 'attachment',
                    name: 'attachment',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'actions',
                    name: 'actions',
                    className: 'text-center',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $('#applyFilters').on('click', function() {
            table.ajax.reload();
        });

        $('#resetFilters').on('click', function() {
            $('#filter_start_date').val('');
            $('#filter_end_date').val('');
            $('#filter_currency_id').val('');
            $('#filter_transaction_type').val('');
            table.ajax.reload();
        });

        $('#transactionForm').submit(function(e) {
            e.preventDefault();

            $('.transaction-amount').each(function() {
                this.value = sanitizeAmount(this.value);
            });

            let formData = new FormData(this);

            let edit = $('#edit_mode').val();
            let linked = $('#linked_id').val();

            let url = "{{ route('admin.transactions.store') }}";
            let method = "POST";

            if (edit == 1) {
                url = "/admin/transactions/" + linked;
                method = "POST";
                formData.append('_method', 'PUT');
            }

            $('#submitBtn').prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm me-1"></span>
                {{ __('transactions.saving') }}
            `);

            $.ajax({
                url: url,
                type: method,
                data: formData,
                processData: false,
                contentType: false,

                success: function(res) {
                    transactionsSwal.fire({
                        icon: 'success',
                        title: res.message || @json(__('transactions.saved')),
                        timer: 1200,
                        showConfirmButton: false
                    });

                    resetForm();
                    table.ajax.reload(null, false);
                },

                error: function(xhr) {
                    let message = @json(__('transactions.error'));

                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        } else if (xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                    }

                    transactionsSwal.fire({
                        icon: 'error',
                        title: '{{ __('ui.blade_error') }}',
                        html: message
                    });
                },

                complete: function() {
                    $('#submitBtn').prop('disabled', false);

                    if ($('#edit_mode').val() == 1) {
                        $('#submitBtn').html('<i class="bi bi-pencil-square me-2"></i> ' + @json(__('transactions.update')));
                    } else {
                        $('#submitBtn').html('<i class="bi bi-send-fill me-2"></i> ' + @json(__('transactions.submit')));
                    }
                }
            });
        });

        $(document).on('click', '.btn-edit', function() {
            let id = $(this).data('id');

            $.get("{{ url('/admin/transactions/fetch') }}/" + id, function(res) {
                let t = res.transaction;

                $('#edit_mode').val(1);
                $('#linked_id').val(t.linked_id);

                $('#account_id').val(t.account_id).trigger('change');
                $('#reference').val(t.reference ?? '');

                if (t.created_at) {
                    $('#transaction_date').val(t.created_at.substring(0, 10));
                }

                $('#entriesWrapper').html(`
                    <div class="entry-row row g-2 align-items-center">
                        <div class="col-md-2">
                            <select name="entries[0][type]" class="form-select" required>
                                <option value="credit" ${t.transaction_type == 'credit' ? 'selected' : ''}>{{ __('ui.credit') }}</option>
                                <option value="debit" ${t.transaction_type == 'debit' ? 'selected' : ''}>{{ __('ui.debit') }}</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select name="entries[0][currency_id]" class="form-select select2" required>
                                ${getCurrencyOptions()}
                            </select>
                        </div>

                        <div class="col-md-2">
                            <input type="text" name="entries[0][amount]" class="form-control transaction-amount" inputmode="decimal" value="${t.amount}" required>
                        </div>

                        <div class="col-md-5">
                            <input type="text" name="entries[0][note]" class="form-control" value="${t.note ?? ''}">
                        </div>

                        <div class="col-md-1 text-end"></div>
                    </div>
                `);

                $('select[name="entries[0][currency_id]"]').val(t.currency_id);

                refreshPlugins();

                $('#submitBtn').html('<i class="bi bi-pencil-square me-2"></i> ' + @json(__('transactions.update')));
                $('#cancelEditBtn').removeClass('d-none');

                $('html,body').animate({
                    scrollTop: 0
                }, 300);
            });
        });

        $('#cancelEditBtn').on('click', function() {
            resetForm();
        });

        $(document).on('click', '.btn-delete', function() {
            let id = $(this).data('id');

            transactionsSwal.fire({
                title: '{{ __('ui.blade_delete_transaction') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{{ __('ui.delete') }}}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('/admin/transactions') }}/" + id,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function() {
                            table.ajax.reload(null, false);

                            transactionsSwal.fire({
                                icon: 'success',
                                title: '{{ __('ui.blade_deleted') }}',
                                timer: 1000,
                                showConfirmButton: false
                            });
                        },

                        error: function(xhr) {
                            let message = xhr.responseJSON?.message || @json(__('transactions.delete_failed'));

                            transactionsSwal.fire({
                                icon: 'error',
                                title: '{{ __('ui.blade_error') }}',
                                text: message
                            });
                        }
                    });
                }
            });
        });

        const urlParams = new URLSearchParams(window.location.search);
        const editId = urlParams.get('edit');

        if (editId) {
            $.get("/admin/transactions/fetch/" + editId, function(res) {
                let t = res.transaction;

                $('#edit_mode').val(1);
                $('#linked_id').val(t.linked_id);

                $('#account_id').val(t.account_id).trigger('change');
                $('#reference').val(t.reference ?? '');

                if (t.created_at) {
                    $('#transaction_date').val(t.created_at.substring(0, 10));
                }

                $('#entriesWrapper').html(`
                    <div class="entry-row row g-2 align-items-center">
                        <div class="col-md-2">
                            <select name="entries[0][type]" class="form-select" required>
                                <option value="credit" ${t.transaction_type == 'credit' ? 'selected' : ''}>{{ __('ui.credit') }}</option>
                                <option value="debit" ${t.transaction_type == 'debit' ? 'selected' : ''}>{{ __('ui.debit') }}</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select name="entries[0][currency_id]" class="form-select select2" required>
                                ${getCurrencyOptions()}
                            </select>
                        </div>

                        <div class="col-md-2">
                            <input type="text" name="entries[0][amount]" class="form-control transaction-amount" inputmode="decimal" value="${t.amount}" required>
                        </div>

                        <div class="col-md-5">
                            <input type="text" name="entries[0][note]" class="form-control" value="${t.note ?? ''}">
                        </div>

                        <div class="col-md-1 text-end"></div>
                    </div>
                `);

                $('select[name="entries[0][currency_id]"]').val(t.currency_id);

                refreshPlugins();

                $('#submitBtn').html('<i class="bi bi-pencil-square me-2"></i> ' + @json(__('transactions.update')));
                $('#cancelEditBtn').removeClass('d-none');

                $('html,body').animate({
                    scrollTop: 0
                }, 300);
            });
        }

        $(document).on('change', 'input[type=radio][name="transaction_mode"]', function() {
            let mode = $('input[type=radio][name="transaction_mode"]:checked').val();
            $('.track-account-wrap').toggle(mode === 'track');
        });
    </script>
@endsection
