@extends('layouts.admin.base')

@section('title', __('journal.title'))

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/febd1387d851569a.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/3218bf0b4c8ec67e.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/9fb6ddf0a1dcf229.css') }}">

    <style>
        :root {
            --fin-primary: #2563eb;
            --fin-bg: #f8fafc;
        }

        body {
            background-color: var(--fin-bg);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
        }

        /* Compact Luxury Summary Cards */
        .summary-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .summary-card:hover {
            transform: translateY(-2px);
        }

        .summary-card::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
        }

        .summary-card.gold::after {
            background: #fbbf24;
        }

        .summary-card.silver::after {
            background: #94a3b8;
        }

        .summary-card.platinum::after {
            background: #1e293b;
        }

        .cur-badge {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 4px;
            background: #f1f5f9;
            color: #475569;
        }

        /* Metric Grid inside Card */
        .mini-metric-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
        }

        .mini-metric-item {
            text-align: center;
        }

        .mini-metric-val {
            font-size: 0.85rem;
            font-weight: 700;
            display: block;
        }

        .mini-metric-lbl {
            font-size: 0.65rem;
            color: #94a3b8;
            text-transform: uppercase;
        }

        /* Professional Table Styling */
        #journal-table thead th {
            background: #fff;
            color: #64748b;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            font-weight: 700;
            border-bottom: 2px solid #f1f5f9;
        }

        .account-link {
            color: #1e293b;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }

        .account-link:hover {
            color: var(--fin-primary);
            text-decoration: underline;
        }

        .type-pill {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
        }

        .amount-mono {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
        }
        .transaction-color-dot{display:inline-block;width:18px;height:18px;border-radius:6px;border:1px solid rgba(15,23,42,.12);vertical-align:middle}.transaction-color-dot.empty{background:#fff;border-style:dashed}.transaction-bulk-bar{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:10px 12px}#journal-table tbody tr.transaction-row-colored,#journal-table tbody tr.transaction-row-colored:hover{--bs-table-bg:var(--transaction-row-color)!important;--bs-table-accent-bg:var(--transaction-row-color)!important;--bs-table-hover-bg:var(--transaction-row-color)!important;background-color:var(--transaction-row-color)!important}#journal-table tbody tr.transaction-row-colored>td,#journal-table tbody tr.transaction-row-colored:hover>td{background-color:var(--transaction-row-color)!important;box-shadow:none!important}.transaction-ok-toggle,.transaction-select,#journal-select-all{cursor:pointer}
    /* Journal bulk actions stay on one row, including on narrow screens. */
        #journal-transaction-bulk-bar {
            display: flex; flex-wrap: nowrap !important; align-items: center; gap: 12px;
            width: 100%; min-width: 0; overflow-x: auto; padding: 10px 12px;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            box-shadow: 0 2px 8px rgba(15,23,42,.04); scrollbar-width: thin;
        }
        #journal-transaction-bulk-bar > * { flex: 0 0 auto; white-space: nowrap; }
        #journal-transaction-bulk-bar .bulk-group { display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; }
        #journal-transaction-bulk-bar .bulk-selection { gap: 8px; font-size: 12px; font-weight: 600; color: #64748b; }
        #journal-transaction-bulk-bar .selected-transaction-count {
            display: inline-flex; align-items: center; justify-content: center; min-width: 28px;
            height: 28px; padding: 0 7px; border-radius: 8px; background: #f1f5f9; color: #475569;
            font-variant-numeric: tabular-nums;
        }
        #journal-transaction-bulk-bar.has-selection .selected-transaction-count { background: #dbeafe; color: #1d4ed8; }
        #journal-transaction-bulk-bar .bulk-divider { width: 1px; height: 24px; background: #e2e8f0; }
        #journal-transaction-bulk-bar .btn { height: 32px; border-radius: 7px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        #journal-transaction-bulk-bar .bulk-color { width: 135px; height: 32px; font-size: 12px; border-radius: 7px; }
        #journal-transaction-bulk-bar .bulk-reset { margin-inline-start: auto; }
        #journal-transaction-bulk-bar .btn:disabled { opacity: .4; }
        #journal-transaction-bulk-bar :focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }

        /* Journal visual language, scoped to the journal page. */
        .journal-page{--journal-ink:#1c304f;--journal-muted:#65748b;--journal-line:#e5ebf3;--journal-blue:#3157a5;max-width:1830px;margin-inline:auto;padding-bottom:22px;color:var(--journal-ink)}
        .journal-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;background:linear-gradient(112deg,#172946 0%,#2a426f 62%,#3559a4 100%);border-radius:17px;padding:19px 22px;margin-bottom:15px;color:#fff;position:relative;overflow:hidden;box-shadow:0 12px 28px rgba(28,50,93,.13)}
        .journal-hero:after{content:"";position:absolute;border:28px solid rgba(255,255,255,.045);width:200px;height:200px;border-radius:50%;inset-inline-end:-70px;top:-120px;pointer-events:none}
        .journal-hero-copy,.journal-print-btn{position:relative;z-index:1}
        .journal-eyebrow{display:inline-flex;align-items:center;gap:6px;color:#bfd0fa;font-size:.69rem;font-weight:800;letter-spacing:.04em}
        .journal-hero h2{font-size:1.45rem;font-weight:800;color:#fff;letter-spacing:-.02em;margin:4px 0}
        .journal-hero p{color:#d0dcf2;font-size:.76rem;margin:0}
        .journal-print-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;border-radius:10px;padding:9px 14px;background:#fff;color:#294a8b;border:1px solid rgba(255,255,255,.8);font-size:.77rem;font-weight:800;white-space:nowrap;box-shadow:0 4px 14px rgba(10,25,60,.13)}
        .journal-print-btn:hover{background:#eaf1ff;color:#1f3c76}
        .journal-block-heading{display:flex;justify-content:space-between;align-items:center;margin:6px 2px 11px}
        .journal-block-heading span,.journal-table-heading span{font-size:.68rem;font-weight:800;letter-spacing:.05em;color:#5b76a8}
        .journal-block-heading h3,.journal-table-heading h3{font-size:1.02rem;font-weight:800;color:#203957;margin:3px 0 0}
        .journal-page .card{padding:0;border:1px solid var(--journal-line);border-radius:15px;background:#fff;box-shadow:0 4px 15px rgba(30,49,86,.04)}
        .journal-page .summary-card{height:100%;min-height:170px;border:1px solid var(--journal-line);border-radius:15px;padding:17px;box-shadow:0 4px 13px rgba(28,47,87,.04);background:linear-gradient(150deg,#fff 0%,#fafcff 100%);transition:transform .17s,box-shadow .17s}
        .journal-page .summary-card:hover{transform:translateY(-2px);box-shadow:0 9px 21px rgba(28,47,87,.08)}
        .journal-page .summary-card:after{inset-inline-start:0;left:auto;width:3px}
        .journal-page .summary-card.gold:after{background:#d6a34b}
        .journal-page .summary-card.silver:after{background:#4b74ba}
        .journal-page .summary-card.platinum:after{background:#12957b}
        .journal-page .summary-card h6{font-size:.73rem;color:#62748d;font-weight:750!important;line-height:1.35;margin-bottom:5px!important}
        .journal-page .summary-card .amount-mono.fs-5{font-family:inherit;font-size:1.4rem!important;font-weight:850;letter-spacing:-.03em;line-height:1.2;overflow-wrap:anywhere;font-variant-numeric:tabular-nums}
        .journal-page .cur-badge{display:inline-flex;align-items:center;justify-content:center;min-width:42px;background:#edf2fc;color:#3a5b91;padding:6px 8px;border:1px solid #e3eafa;border-radius:8px;font-size:.72rem}
        .journal-page .summary-card>.d-flex:nth-child(2){background:#f6f9fd;border-radius:8px;padding:8px;margin-top:6px;gap:8px;flex-wrap:wrap}
        .journal-page .journal-flow-value{display:inline-flex;align-items:center;gap:3px;flex-wrap:wrap;font-size:.74rem;font-weight:750;font-variant-numeric:tabular-nums;color:#3a4f6e}
        .journal-page .journal-flow-value small{font-size:.65rem;color:#74839a;font-weight:700}
        .journal-page .mini-metric-grid{gap:6px;padding-top:12px;margin-top:12px;border-top:1px solid #eaf0f5}
        .journal-page .mini-metric-item{min-width:0;padding:0 3px}
        .journal-page .mini-metric-val{font-size:.84rem;color:#233b5f;font-weight:800;overflow-wrap:anywhere;font-variant-numeric:tabular-nums}
        .journal-page .mini-metric-lbl{font-size:.64rem;letter-spacing:.02em;color:#75849a}
        .journal-filter-card .card-body{padding:16px 18px}
        .journal-filter-title{display:flex;align-items:center;gap:7px;color:#354f7a;font-size:.78rem;font-weight:800;margin-bottom:12px}
        .journal-filter-title i{color:#4c70b3}
        .journal-field-label{display:block;font-weight:750;font-size:.69rem;color:#65758a;margin-bottom:6px}
        .journal-page .journal-filter-card .form-control,.journal-page .journal-filter-card .form-select{height:40px;min-height:40px;border-radius:9px;border:1px solid #dde5ef!important;background:#fff!important;color:#283f5d;font-size:.78rem;box-shadow:none!important}
        .journal-page .journal-filter-card .input-group-text{height:40px;border:1px solid #dde5ef!important;background:#f4f7fc!important;color:#5a76aa;border-radius:9px 0 0 9px}
        .journal-page .journal-filter-card .input-group .form-control{border-inline-start:0!important}
        .journal-page .journal-filter-card .select2-container--bootstrap-5 .select2-selection{min-height:40px;height:40px;border:1px solid #dde5ef;border-radius:9px;display:flex;align-items:center;font-size:.78rem;color:#283f5d}
        .journal-page .journal-filter-card .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered{font-size:.78rem;line-height:26px}
        .journal-page .journal-filter-card .form-control:focus,.journal-page .journal-filter-card .form-select:focus{border-color:#91a9d5!important;box-shadow:0 0 0 3px rgba(80,117,183,.12)!important}
        .journal-table-card{overflow:hidden}
        .journal-table-heading{display:flex;justify-content:space-between;align-items:center;padding:15px 18px 12px;border-bottom:1px solid #edf1f6}
        .journal-table-heading>i{width:37px;height:37px;display:grid;place-items:center;border-radius:10px;background:#edf2ff;color:#4064aa;font-size:1rem}
        .journal-page #journal-transaction-bulk-bar{border:1px solid #e6ecf4;border-radius:10px;box-shadow:none;background:#f8fafd;margin:12px 15px!important;width:calc(100% - 30px);min-height:52px}
        .journal-page #journal-transaction-bulk-bar .btn{height:35px;font-size:.74rem;border-radius:8px;font-weight:750}
        .journal-page #journal-transaction-bulk-bar .bulk-color{height:35px;border:1px solid #dce5f0;border-radius:8px}
        .journal-page #journal-transaction-bulk-bar .selected-transaction-count{background:#e8eef8;color:#36588e;font-weight:800}
        .journal-page #journal-table{min-width:1120px;margin-bottom:0!important;border-collapse:separate;border-spacing:0}
        .journal-page #journal-table thead th{background:#f3f6fb;color:#5e6f89;font-size:.69rem;font-weight:800;padding:13px 11px;border-bottom:1px solid #e8edf4;vertical-align:middle;white-space:nowrap}
        .journal-page #journal-table tbody td{padding:12px 11px;border-bottom:1px solid #edf1f6;vertical-align:middle;font-size:.75rem;color:#41536c}
        .journal-page #journal-table tbody tr:not(.transaction-row-colored):hover td{background:#fafcff}
        .journal-page .account-link{color:#28456f;font-size:.78rem;font-weight:750}
        .journal-page .account-link:hover{color:#315bb0}
        .journal-page #journal-table .amount-mono{font-family:inherit;font-variant-numeric:tabular-nums;font-size:.82rem;font-weight:850;letter-spacing:-.01em}
        .journal-page #journal-table .type-pill{display:inline-block;border-radius:7px;padding:6px 9px;font-size:.7rem;font-weight:750;white-space:nowrap}
        .journal-page #journal-table .btn-delete,.journal-page #journal-table .btn-primary,.journal-page #journal-table .btn-print{display:inline-grid;place-items:center;width:31px;height:31px;padding:0;border-radius:8px;font-size:.73rem;line-height:1}
        .journal-page #journal-table .btn-primary{background:#eaf0ff;color:#3159a5;border:1px solid #dce5fa}
        .journal-page #journal-table .btn-print{background:#e8f7f0;color:#17845f;border:1px solid #ccebdd}
        .journal-page #journal-table .btn-danger{background:#fff0f2;color:#c84c60;border:1px solid #f8dbe0}
        .journal-page #journal-table .btn-primary:hover{background:#dce7ff}
        .journal-page #journal-table .btn-print:hover{background:#d7f1e5;color:#116b4d}
        .journal-page #journal-table .btn-danger:hover{background:#ffe1e7}
        .journal-page .dataTables_wrapper .dataTables_filter input,.journal-page .dataTables_wrapper .dataTables_length select{border:1px solid #dce5f0;border-radius:8px;min-height:35px;padding:6px 9px;background:#fff;font-size:.75rem}
        .journal-page .dataTables_wrapper .dataTables_info,.journal-page .dataTables_wrapper .dataTables_paginate,.journal-page .dataTables_wrapper .dataTables_length,.journal-page .dataTables_wrapper .dataTables_filter{font-size:.74rem;color:#687992}
        .journal-page .dataTables_wrapper>.row{row-gap:10px}
        .journal-page :is(button,a,input,select):focus-visible{outline:3px solid #91ade0;outline-offset:2px}
        @media(max-width:767px){.journal-hero{padding:16px;align-items:flex-start}.journal-hero h2{font-size:1.22rem}.journal-hero p{font-size:.7rem}.journal-print-btn{padding:9px;min-height:40px}.journal-print-btn span{display:none}.journal-filter-card .card-body{padding:13px}.journal-page .summary-card{min-height:153px;padding:13px}.journal-page .summary-card .amount-mono.fs-5{font-size:1.18rem!important}.journal-page .mini-metric-val{font-size:.72rem}.journal-page .mini-metric-lbl{font-size:.6rem}.journal-table-heading{padding:12px}.journal-page #journal-transaction-bulk-bar{margin:10px!important;width:calc(100% - 20px)}}
        @media(prefers-reduced-motion:reduce){.journal-page .summary-card{transition:none}.journal-page .summary-card:hover{transform:none}}
        </style>
@endsection

@section('content')
    <div class="journal-page">
        <header class="journal-hero">
            <div class="journal-hero-copy">
                <span class="journal-eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i> {{ __('journal.title') }}</span>
                <h2>{{ __('ui.blade_general_journal') }}</h2>
                <p>{{ __('ui.blade_monitor_and_audit_all_system_transactions') }}</p>
            </div>
            <button type="button" onclick="printJournal()" class="btn journal-print-btn">
                <i class="bi bi-printer" aria-hidden="true"></i><span>{{ __('journal.print_button') }}</span>
            </button>
        </header>
        <div class="journal-block-heading"><div><span>{{ __('journal.currency_balance') }}</span><h3>{{ __('ui.balances') }}</h3></div></div>

    <div id="journal_summary" class="row g-3 mb-3" aria-live="polite">
    </div>

    <div class="card journal-filter-card mb-3">
        <div class="card-body">
            <div class="journal-filter-title"><i class="bi bi-funnel" aria-hidden="true"></i><span>{{ __('ui.date_range') }}</span></div>
            <div class="row g-2 align-items-end">
                <div class="col-md-6 col-xl-3">
                    <label for="date_range" class="journal-field-label">{{ __('ui.date_range') }}</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-calendar3"></i></span>
                        <input type="text" id="date_range" class="form-control bg-light small border-0 shadow-none" placeholder="{{ __('ui.date_range') }}">
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="account_filter" class="journal-field-label">{{ __('ui.all_accounts') }}</label>
                    <select id="account_filter" class="select2-sm">
                        <option value="">{{ __('ui.all_accounts') }}</option>
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}">[{{ $a->code }}] {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label for="currency_filter" class="journal-field-label">{{ __('ui.currency') }}</label>
                    <select id="currency_filter" class="select2-sm">
                        <option value="">{{ __('ui.all_currencies') }}</option>
                        @foreach ($currencies as $c)
                            <option value="{{ $c->id }}">{{ $c->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2"><label for="color_filter" class="journal-field-label">{{ __('journal.color') }}</label><select id="color_filter" class="form-select form-select-sm"><option value="">{{ __('journal.all_colors') }}</option><option value="none">{{ __('journal.no_color') }}</option><option value="#FEF3C7">{{ __('ui.bulk_toolbar.yellow') }}</option><option value="#DCFCE7">{{ __('ui.bulk_toolbar.green') }}</option><option value="#DBEAFE">{{ __('ui.bulk_toolbar.blue') }}</option><option value="#FCE7F3">{{ __('ui.bulk_toolbar.pink') }}</option><option value="#FEE2E2">{{ __('ui.bulk_toolbar.red') }}</option><option value="#EDE9FE">{{ __('ui.bulk_toolbar.purple') }}</option></select></div>
                <div class="col-md-6 col-xl-2"><label for="ok_filter" class="journal-field-label">{{ __('journal.all_statuses') }}</label><select id="ok_filter" class="form-select form-select-sm"><option value="">{{ __('journal.all_statuses') }}</option><option value="1">{{ __('journal.ok') }}</option><option value="0">{{ __('journal.not_ok') }}</option></select></div>
            </div>
        </div>
    </div>

    <div class="card journal-table-card">
            <div class="journal-table-heading"><div><span>{{ __('journal.title') }}</span><h3>{{ __('ui.blade_general_journal') }}</h3></div><i class="bi bi-journal-text" aria-hidden="true"></i></div>
            <div id="journal-transaction-bulk-bar" class="transaction-bulk-bar mt-2 mb-2" role="group" aria-label="{{ __('ui.bulk_toolbar.actions') }}">
                <div class="bulk-group bulk-selection" role="status" aria-live="polite" aria-atomic="true">
                    <span class="selected-transaction-count">0</span>
                    <span>{{ __('ui.bulk_toolbar.selected') }}</span>
                </div>
                <span class="bulk-divider" aria-hidden="true"></span>
                <div class="bulk-group">
                    <select class="form-select form-select-sm bulk-color" aria-label="{{ __('ui.choose_color') }}">
                        <option value="">{{ __('ui.choose_color') }}</option>
                        @foreach (['#FEF3C7' => 'yellow', '#DCFCE7' => 'green', '#DBEAFE' => 'blue', '#FCE7F3' => 'pink', '#FEE2E2' => 'red', '#EDE9FE' => 'purple'] as $color => $label)
                            <option value="{{ $color }}">{{ __('ui.bulk_toolbar.'.$label) }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-sm btn-primary apply-bulk-color" disabled><i class="bi bi-palette me-1" aria-hidden="true"></i>{{ __('ui.bulk_toolbar.apply') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary clear-bulk-color" disabled><i class="bi bi-eraser me-1" aria-hidden="true"></i>{{ __('ui.bulk_toolbar.clear') }}</button>
                </div>
                <span class="bulk-divider" aria-hidden="true"></span>
                <div class="bulk-group">
                    <button type="button" class="btn btn-sm btn-outline-success bulk-ok" data-ok="1" disabled><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>{{ __('ui.bulk_toolbar.ok') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-warning bulk-ok" data-ok="0" disabled><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ __('ui.bulk_toolbar.not_ok') }}</button>
                </div>
                <button type="button" class="btn btn-sm btn-light bulk-reset" disabled><i class="bi bi-x-lg me-1" aria-hidden="true"></i>{{ __('ui.bulk_toolbar.deselect') }}</button>
            </div>

        <div class="table-responsive">
            <table id="journal-table" class="table-hover mb-0 table" dir="{{ app()->getLocale() == 'en' ? 'ltr' : 'rtl' }}">
                <thead>
                    <tr>
                        <th><input type="checkbox" class="form-check-input" id="journal-select-all"></th>
                        <th class="ps-4">{{ __('ui.blade_ref') }}</th>
                        <th>{{ __('ui.date') }}</th>
                        <th>{{ __('ui.blade_account_entity') }}</th>
                        <th>{{ __('ui.blade_memo') }}</th>
                        <th class="text-center">{{ __('ui.type') }}</th>
                        <th class="pe-4 text-end">{{ __('ui.blade_transaction_amount') }}</th>
                        <th class="text-center">{{ __('ui.attachment') }}</th>
                        <th class="text-center">{{ __('journal.color') }}</th>
                        <th class="text-center">{{ __('journal.ok') }}</th>
                        <th class="text-center">{{ __('ui.action') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('assets/offline/33859893654b9de2.js') }}"></script>
    <script src="{{ asset('assets/offline/ac82083f08b38e5c.js') }}"></script>
    <script src="{{ asset('assets/offline/00b712c3c875b8cd.js') }}"></script>
    <script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>
    <script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>

    <script>
        const journalSwal = Swal.mixin({confirmButtonText: @json(__('journal.ok')), cancelButtonText: @json(__('ui.cancel'))});
        $(function() {
            $('.select2-sm').select2({
                language: @include('admin.journal.partials.select-language'),
                width: '100%',
                theme: 'bootstrap-5'
            });

            let startDate = null;
            let endDate = null;

            $('#date_range').daterangepicker({
                    autoUpdateInput: false,
                    locale: {
                        format: 'YYYY-MM-DD',
                        cancelLabel: @json(__('journal.clear')),
                        applyLabel: @json(__('journal.apply')),
                        fromLabel: @json(__('journal.from')),
                        toLabel: @json(__('journal.to')),
                        customRangeLabel: @json(__('journal.custom_range')),
                        weekLabel: @json(__('journal.week')),
                        daysOfWeek: @json(__('journal.days')),
                        monthNames: @json(__('journal.months'))
                    }
                })
                .on('apply.daterangepicker', function(ev, picker) {

                    startDate = picker.startDate.format('YYYY-MM-DD');
                    endDate = picker.endDate.format('YYYY-MM-DD');

                    journalStartDate = startDate;
                    journalEndDate = endDate;

                    $(this).val(
                        picker.startDate.format('DD MMM YYYY') +
                        ' - ' +
                        picker.endDate.format('DD MMM YYYY')
                    );

                    table.draw();
                    loadSummary();

                })
                .on('cancel.daterangepicker', function() {

                    $(this).val('');
                    startDate = null;
                    endDate = null;

                    journalStartDate = null;
                    journalEndDate = null;

                    table.draw();
                    loadSummary();
                });

            const table = $('#journal-table').DataTable({
                language: @include('admin.journal.partials.table-language'),
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.journal.data') }}',
                    data: d => {
                        d.account_id = $('#account_filter').val();
                        d.currency_id = $('#currency_filter').val();
                        d.color = $('#color_filter').val();
                        d.is_ok = $('#ok_filter').val();
                        d.start_date = startDate;
                        d.end_date = endDate;
                    }
                },
                columns: [{data:'select',orderable:false,searchable:false,className:'text-center'}, {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4 text-muted'
                    },
                    {
                        data: 'created_at',
                        render: d => `<span class="fw-medium">${moment(d).format('DD MMM YYYY')}</span>`
                    },
                    {
                        data: null,
                        render: r => `
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-3 p-2 me-2">
                                    <i class="bi bi-wallet2 text-primary"></i>
                                </div>
                                <div>
                                    <a href="/admin/accounts/${r.account_id}" class="account-link">
                                        ${r.account_name}
                                    </a>
                                </div>
                            </div>`
                    },
                    {
                        data: 'note',
                        className: 'text-muted small'
                    },
                    {
                        data: 'transaction_type',
                        className: 'text-center',
                        render: t => t === 'debit' ?
                            `<span class="type-pill bg-danger-subtle text-danger">{{ __('ui.debit') }}</span>` :
                            `<span class="type-pill bg-success-subtle text-success">{{ __('ui.credit') }}</span>`
                    },
                    {
                        data: null,
                        className: 'text-end pe-4 amount-mono',
                        render: r => {
                            let color = r.transaction_type === 'debit' ? 'text-danger' : 'text-success';
                            return `<span class="${color}">
                                ${parseFloat(r.amount).toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })}
                            </span>
                            <span class="text-muted x-small ms-1">${r.currency_symbol}</span>`;
                        }
                    },
                    {
                        data: 'attachment',
                        name: 'attachment',
                        className: 'text-center',

                    },
                    {data:'color_display',orderable:false,searchable:false,className:'text-center'},
                    {data:'ok_display',orderable:false,searchable:false,className:'text-center'},
                    {
                        data: null,
                        className: 'text-center',
                        orderable: false,
                        searchable: false,
                        render: function(row) {
                            const printButton = String(row.table_name || '').toLowerCase() === 'journal' ? `
                                <a href="/admin/transactions/${row.id}/print" target="_blank"
                                class="btn btn-sm btn-print" title="{{ __('journal_receipt.print_receipt') }}" aria-label="{{ __('journal_receipt.print_receipt') }}">
                                <i class="bi bi-printer"></i>
                                </a>` : '';
                            return `
                            <div class="d-flex justify-content-center gap-2">

                                ${printButton}

                                <a href="/admin/transactions?edit=${row.id}"
                                class="btn btn-sm btn-primary" title="{{ __('journal.edit') }}" aria-label="{{ __('journal.edit') }}">
                                <i class="bi bi-pencil"></i>
                                </a>

                                <button class="btn btn-sm btn-danger btn-delete" title="{{ __('journal.delete') }}" aria-label="{{ __('journal.delete') }}"
                                    data-id="${row.id}">
                                    <i class="bi bi-trash"></i>
                                </button>

                            </div>`;
                        }
                    }
                ],
                rowCallback:function(row,data){if(data.color){row.style.setProperty('--transaction-row-color',data.color);row.classList.add('transaction-row-colored')}else{row.classList.remove('transaction-row-colored');row.style.removeProperty('--transaction-row-color')}},
                initComplete:function(){const bar=$('#journal-transaction-bulk-bar');$('#journal-table_wrapper > .row').first().after(bar)},
                drawCallback: function() {
                    $('.dataTables_paginate > .pagination').addClass('pagination-sm');
                    $('#journal-select-all').prop('checked',false);updateTransactionSelectionCount();
                }
            });

            function selectedTransactionIds(){return $('.transaction-select:checked').map(function(){return Number(this.value)}).get()}
            function updateTransactionSelectionCount() {
                const bar = $('#journal-transaction-bulk-bar');
                const count = selectedTransactionIds().length;
                const total = $('#journal-table .transaction-select').length;
                bar.find('.selected-transaction-count').text(count);
                bar.toggleClass('has-selection', count > 0);
                bar.find('.clear-bulk-color, .bulk-ok, .bulk-reset').prop('disabled', count === 0);
                bar.find('.apply-bulk-color').prop('disabled', count === 0 || !bar.find('.bulk-color').val());
                $('#journal-select-all').prop('checked', total > 0 && count === total)
                    .prop('indeterminate', count > 0 && count < total);
            }
            $('#journal-transaction-bulk-bar .bulk-color').on('change', updateTransactionSelectionCount);
            $('#journal-transaction-bulk-bar .bulk-reset').on('click', function() {
                $('#journal-table .transaction-select').prop('checked', false);
                updateTransactionSelectionCount();
            });
            function updateTransactionMeta(ids,payload){if(!ids.length){journalSwal.fire(@json(__('journal.select_transactions')),@json(__('journal.select_one_transaction')),'info');return $.Deferred().reject().promise()}return $.ajax({url:'{{ route('admin.transactions.bulk-meta') }}',type:'PATCH',data:Object.assign({_token:'{{ csrf_token() }}',ids:ids},payload)}).done(function(res){table.ajax.reload(null,false);journalSwal.fire({icon:'success',title:res.message,timer:1100,showConfirmButton:false})}).fail(function(xhr){journalSwal.fire(@json(__('journal.update_failed')),xhr.responseJSON?.message||@json(__('journal.transactions_update_failed')),'error')})}
            function confirmTransactionUpdate(title,text,callback){return journalSwal.fire({title:title,text:text,icon:'question',showCancelButton:true,confirmButtonText:@json(__('journal.yes_continue')),cancelButtonText:@json(__('ui.cancel'))}).then(function(result){if(result.isConfirmed)callback();return result})}
            $(document).on('change','#journal-select-all',function(){$('.transaction-select').prop('checked',this.checked);updateTransactionSelectionCount()});
            $(document).on('change','.transaction-select',updateTransactionSelectionCount);
            $(document).on('change','.transaction-ok-toggle',function(){const checkbox=this,isOk=checkbox.checked,id=Number($(checkbox).data('id'));confirmTransactionUpdate(isOk?@json(__('journal.mark_ok')):@json(__('journal.mark_not_ok')),isOk?@json(__('journal.checked_correct')):@json(__('journal.needs_review')),function(){updateTransactionMeta([id],{is_ok:isOk?1:0})}).then(function(result){if(!result.isConfirmed)checkbox.checked=!isOk})});
            $('.apply-bulk-color').on('click',function(){const color=$('.bulk-color').val(),ids=selectedTransactionIds();if(!color){journalSwal.fire(@json(__('journal.choose_color')),@json(__('journal.select_palette')),'info');return}if(!ids.length){journalSwal.fire(@json(__('journal.select_transactions')),@json(__('journal.select_one_transaction')),'info');return}confirmTransactionUpdate(@json(__('journal.apply_color')),@json(__('journal.apply_color_count')).replace(':count', ids.length),function(){updateTransactionMeta(ids,{color:color})})});
            $('.clear-bulk-color').on('click',function(){const ids=selectedTransactionIds();if(!ids.length){journalSwal.fire(@json(__('journal.select_transactions')),@json(__('journal.select_one_transaction')),'info');return}confirmTransactionUpdate(@json(__('journal.clear_colors')),@json(__('journal.clear_colors_count')).replace(':count', ids.length),function(){updateTransactionMeta(ids,{color:''})})});
            $('.bulk-ok').on('click',function(){const isOk=Number($(this).data('ok')),ids=selectedTransactionIds();if(!ids.length){journalSwal.fire(@json(__('journal.select_transactions')),@json(__('journal.select_one_transaction')),'info');return}confirmTransactionUpdate(isOk?@json(__('journal.mark_selected_ok')):@json(__('journal.mark_selected_not_ok')),(isOk ? @json(__('journal.confirm_count')) : @json(__('journal.flag_count'))).replace(':count', ids.length),function(){updateTransactionMeta(ids,{is_ok:isOk})})});

            function loadSummary() {
                $.get('{{ route('admin.journal.summary') }}', res => {
                    let html = '';
                    const themes = ['gold', 'silver', 'platinum'];
                    let i = 0;

                    Object.entries(res.summary).forEach(([cur, d]) => {
                        const theme = themes[i % 3];
                        i++;
                        html += `
                <div class="col-xl-3 col-lg-6">
                    <div class="summary-card ${theme}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-0 fw-bold">${@json(__('journal.currency_balance')).replace(':currency', cur)}</h6>
                                <div class="amount-mono fs-5 ${parseFloat(d.balance) < 0 ? 'text-danger' : 'text-dark'}">
                                    ${Number(d.balance).toLocaleString('en-US', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    })}
                                </div>
                            </div>
                            <span class="cur-badge">${cur}</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span class="journal-flow-value"><i class="bi bi-arrow-down-left text-success me-1" aria-hidden="true"></i><small>{{ __('ui.credit') }}</small> ${Number(d.credit).toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            })}</span>
                            <span class="journal-flow-value"><i class="bi bi-arrow-up-right text-danger me-1" aria-hidden="true"></i><small>{{ __('ui.debit') }}</small> ${Number(d.debit).toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            })}</span>
                        </div>
                        <div class="mini-metric-grid">
                            <div class="mini-metric-item">
                                <span class="mini-metric-val">${d.customers}</span>
                                <span class="mini-metric-lbl">{{ __('ui.users') }}</span>
                            </div>
                            <div class="mini-metric-item">
                                <span class="mini-metric-val">${d.saraf}</span>
                                <span class="mini-metric-lbl">{{ __('ui.blade_saraf') }}</span>
                            </div>
                            <div class="mini-metric-item">
                                <span class="mini-metric-val text-primary">${Number(d.cash).toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })}</span>
                                <span class="mini-metric-lbl">{{ __('ui.blade_cash') }}</span>
                            </div>
                        </div>
                    </div>
                </div>`;
                    });
                    $('#journal_summary').html(html);
                });
            }

            $('#account_filter, #currency_filter, #color_filter, #ok_filter').on('change', () => {
                table.draw();
                loadSummary();
            });
            loadSummary();


            $(document).on('click', '.btn-delete', function() {

                let id = $(this).data('id');

                journalSwal.fire({
                    title: '{{ __('ui.blade_delete_this_transaction') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '{{{ __('ui.delete') }}}'
                }).then((result) => {

                    if (result.isConfirmed) {

                        $.ajax({
                            url: '/admin/journal/' + id,
                            type: 'DELETE',
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function() {

                                table.ajax.reload();

                                journalSwal.fire({
                                    icon: 'success',
                                    title: '{{ __('ui.blade_deleted') }}',
                                    timer: 1000,
                                    showConfirmButton: false
                                });

                            }
                        });

                    }

                });

            });

        });
    </script>
    <script>
        let journalStartDate = null;
        let journalEndDate = null;

        window.printJournal = function() {
            let params = new URLSearchParams();

            const account = $('#account_filter').val();
            const currency = $('#currency_filter').val();

            if (account) {
                params.append('account_id', account);
            }

            if (currency) {
                params.append('currency_id', currency);
            }

            if (journalStartDate && journalEndDate) {
                params.append('start_date', journalStartDate);
                params.append('end_date', journalEndDate);
            }

            const url = "{{ route('admin.journal.print') }}" + '?' + params.toString();

            window.open(url, '_blank');
        }
    </script>
@endsection
