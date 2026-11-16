@extends('layouts.admin.base')

@section('title', __('safe.title'))

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/febd1387d851569a.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/3218bf0b4c8ec67e.css') }}">

    <style>
        body {
            background: #f8fafc;
        }

        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
        }

        .table th {
            font-size: 11px;
            text-transform: uppercase;
            background: #f1f5f9;
            color: #64748b;
            white-space: nowrap;
        }

        .table td {
            font-size: 12px;
            vertical-align: middle;
            white-space: nowrap;
        }

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .amount-credit {
            color: #16a34a;
            font-weight: 600;
        }

        .amount-debit {
            color: #dc2626;
            font-weight: 600;
        }

        .balance-cell {
            font-weight: 700;
            color: #0f172a;
        }

        .opening-row {
            background: #f8fafc;
            font-weight: 600;
        }

        .footer-row {
            background: #fff7ed;
            font-weight: 700;
        }

        .summary-card {
            border-radius: 12px;
            padding: 15px;
            background: #ffffff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
        }

        .summary-title {
            font-size: 11px;
            color: #64748b;
        }

        .summary-value {
            font-size: 16px;
            font-weight: 700;
        }

        .badge-credit {
            background: #16a34a;
        }

        .badge-debit {
            background: #dc2626;
        }

        .select2-results__option .select2-checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .select2-results__option .select2-checkbox-item input {
            pointer-events: none;
        }

        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ced4da;
            min-height: 38px;
            border-radius: 0.375rem;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            font-size: 12px;
            border-radius: 6px;
            padding: 2px 8px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            display: none;
        }

        .select2-container--default .select2-selection--multiple::before {
            content: attr(data-selected-text);
            font-size: 13px;
            color: #111827;
            padding-left: 8px;
            line-height: 34px;
        }

        /* Safe ledger: compact banking interface, scoped to this page */
        .safe-page{--safe-ink:#172a46;--safe-muted:#64748b;--safe-line:#e5ebf3;--safe-accent:#3157a5;max-width:1840px;margin-inline:auto;color:var(--safe-ink)}
        .safe-page-header{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:19px 22px;margin-bottom:14px;border-radius:17px;background:linear-gradient(112deg,#172846 0%,#273f70 64%,#3859a4 100%);color:#fff;box-shadow:0 11px 28px rgba(25,47,92,.12);position:relative;overflow:hidden}
        .safe-page-header:after{content:"";position:absolute;width:175px;height:175px;border:25px solid rgba(255,255,255,.045);border-radius:50%;right:-62px;top:-82px;pointer-events:none}
        .safe-page-heading,.safe-page-icon{position:relative;z-index:1}
        .safe-page-eyebrow{display:inline-flex;align-items:center;gap:6px;color:#bbcdfa;font-size:.7rem;font-weight:750;letter-spacing:.04em}
        .safe-page-heading h2{font-size:1.45rem;font-weight:800;letter-spacing:-.025em;color:#fff;margin:4px 0}
        .safe-page-heading p{color:#d0dcf3;font-size:.75rem;margin:0}
        .safe-page-icon{display:grid;place-items:center;width:52px;height:52px;border:1px solid rgba(255,255,255,.16);border-radius:15px;background:rgba(255,255,255,.11);font-size:1.6rem}
        .safe-page .card{border:1px solid var(--safe-line);border-radius:15px;box-shadow:0 4px 15px rgba(28,46,82,.04);background:#fff}
        .safe-filter-card .card-body{padding:16px 18px}
        .safe-section-title{display:flex;align-items:center;gap:8px;font-size:.76rem;color:#354c72;font-weight:800;margin-bottom:12px}
        .safe-section-title i{color:#4267aa}
        .safe-field-label{display:block;color:#66758c;font-size:.7rem;font-weight:750;margin-bottom:6px}
        .safe-filter-card .form-control,.safe-filter-card .form-select{height:40px;border:1px solid #dfe6f0;border-radius:9px;background-color:#fff;font-size:.79rem;color:#293c58;box-shadow:none}
        .safe-filter-card .form-control:focus,.safe-filter-card .form-select:focus{border-color:#7e9bd6;box-shadow:0 0 0 3px rgba(73,112,184,.12)}
        .safe-filter-actions{display:flex;gap:9px;align-items:center}
        .safe-filter-actions .btn{display:inline-flex;gap:8px;align-items:center;justify-content:center;min-height:40px;border-radius:9px;padding:9px 13px;font-size:.76rem;font-weight:750;flex:1;white-space:nowrap}
        .safe-btn-primary{background:#284d98;color:#fff;border:1px solid #284d98}
        .safe-btn-primary:hover{background:#1d3d7e;color:#fff}
        .safe-btn-outline{background:#f6f8fc;color:#344e78;border:1px solid #dce5f2}
        .safe-btn-outline:hover{background:#eaf1ff;color:#274a8d}
        .safe-filter-actions .btn:focus-visible{outline:3px solid #90ade3;outline-offset:2px}
        .safe-content-title{display:flex;align-items:center;justify-content:space-between;margin:17px 2px 11px}
        .safe-content-title span{font-size:.66rem;text-transform:uppercase;letter-spacing:.07em;color:#5975a9;font-weight:800}
        .safe-content-title h3{font-size:1rem;font-weight:800;color:#1e3455;margin:3px 0 0}
        .safe-page #summary-cards{display:flex}
        .safe-page #summary-cards>[class*="col-"]{min-width:0}
        .safe-page .summary-card{height:100%;min-height:116px;padding:16px 17px;border:1px solid var(--safe-line);border-radius:14px;box-shadow:0 4px 13px rgba(28,46,82,.035);position:relative;overflow:hidden;background:linear-gradient(160deg,#fff 0%,#fafcff 100%)}
        .safe-page .summary-card:before{content:"";position:absolute;inset-inline-start:0;top:0;bottom:0;width:3px;background:#496cbd}
        .safe-page .summary-card:after{content:"";position:absolute;width:88px;height:88px;border-radius:50%;background:#edf2fe;opacity:.6;bottom:-55px;inset-inline-end:-40px;pointer-events:none}
        .safe-summary-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}
        .safe-page .summary-title{font-size:.74rem;color:#566b8b;font-weight:800;background:#f0f4fb;padding:5px 8px;border-radius:7px}
        .safe-summary-icon{display:grid;place-items:center;background:#eaf0ff;border-radius:10px;width:31px;height:31px;color:#4668ae;font-size:.96rem}
        .safe-page .summary-value{font-size:1.43rem;font-weight:850;line-height:1.25;color:#193453;overflow-wrap:anywhere;letter-spacing:-.018em;font-variant-numeric:tabular-nums}
        .safe-ledger-card .card-body{padding:17px 18px}
        .safe-ledger-title{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:13px}
        .safe-ledger-title h3{font-size:.98rem;margin:0;font-weight:800;color:#203b60}
        .safe-ledger-title small{color:#71829a;font-size:.71rem}
        .safe-page .table-responsive{border:1px solid #e7ecf4;border-radius:11px;scrollbar-width:thin}
        .safe-page .table{margin-bottom:0;border-collapse:separate;border-spacing:0;min-width:750px}
        .safe-page .table th{font-size:.69rem;font-weight:800;letter-spacing:.035em;color:#5c6d87;background:#f4f7fc;padding:11px 12px;border-color:#e5eaf2;vertical-align:middle}
        .safe-page .table td{font-size:.76rem;color:#40516c;padding:11px 12px;border-color:#edf0f5;vertical-align:middle}
        .safe-page .table tbody tr:not(.opening-row):not(.footer-row):hover td{background:#f9fbff}
        .safe-page .table thead th{position:sticky;top:0;z-index:2;box-shadow:inset 0 -1px #e7edf5}
        .safe-page .table .amount-credit{color:#0e916d;font-weight:800}
        .safe-page .table .amount-debit{color:#d34e64;font-weight:800}
        .safe-page .table .balance-cell{font-weight:800;color:#1b3453}
        .safe-page .table .opening-row td{background:#edf4fc;color:#29436c;font-weight:800}
        .safe-page .table .footer-row td{background:#edf7f3;color:#1e624d;font-weight:800;border-top:2px solid #c6e4d7}
        .safe-page .badge-credit,.safe-page .badge-debit{display:inline-flex;padding:6px 9px;border-radius:7px;font-size:.68rem;font-weight:750}
        .safe-page .badge-credit{background:#e5f7f0;color:#117454}
        .safe-page .badge-debit{background:#fdecef;color:#bd4055}
        .safe-page .table :is(.text-end){font-variant-numeric:tabular-nums}
        .safe-empty-state{padding:42px 15px;text-align:center;color:#8794a7}
        .safe-empty-state i{font-size:1.7rem;color:#a5b3ca;display:block;margin-bottom:9px}
        .safe-empty-state strong{font-size:.85rem;color:#526681}
        .safe-page .safe-error{border-color:#f3d8de;background:#fff8f9;color:#ad4255}
        @media(max-width:767px){.safe-page-header{padding:16px}.safe-page-heading h2{font-size:1.24rem}.safe-page-icon{width:43px;height:43px}.safe-filter-card .card-body,.safe-ledger-card .card-body{padding:13px}.safe-filter-actions{width:100%}.safe-page .summary-value{font-size:1.2rem}.safe-page .summary-card{min-height:105px;padding:13px}.safe-ledger-title{align-items:flex-start;flex-direction:column;gap:3px}}
    </style>
@endsection

@section('content')

    <div class="container-fluid safe-page py-3">
        <div class="safe-page-header">
            <div class="safe-page-heading">
                <span class="safe-page-eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i> {{ __('ui.balances') }}</span>
                <h2>{{ __('safe.title') }}</h2>
                <p>{{ __('ui.date_range') }} · {{ __('ui.blade_opening_balance') }} / {{ __('ui.blade_final_balance') }}</p>
            </div>
            <span class="safe-page-icon" aria-hidden="true"><i class="bi bi-safe2"></i></span>
        </div>

        <!-- FILTERS -->
        <div class="card safe-filter-card mb-3">
            <div class="card-body">
                <div class="safe-section-title"><i class="bi bi-funnel" aria-hidden="true"></i><span>{{ __('ui.blade_range') }}</span></div>
                <div class="row g-2 align-items-end">

                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="safe-field-label" for="range_type">{{ __('ui.blade_range') }}</label>
                        <select id="range_type" class="form-select">
                            <option value="today">{{ __('ui.today') }}</option>
                            <option value="yesterday">{{ __('ui.yesterday') }}</option>
                            <option value="this_week">{{ __('ui.this_week') }}</option>
                            <option value="last_week">{{ __('ui.blade_last_week') }}</option>
                            <option value="this_month">{{ __('ui.this_month') }}</option>
                            <option value="last_month">{{ __('ui.blade_last_month') }}</option>
                            <option value="this_year">{{ __('ui.this_year') }}</option>
                            <option value="last_year">{{ __('ui.blade_last_year') }}</option>
                            <option value="custom">{{ __('ui.blade_custom_date') }}</option>
                            <option value="all">{{ __('ui.all') }}</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="safe-field-label" for="date_range">{{ __('ui.date_range') }}</label>
                        <input type="text" id="date_range" class="form-control">
                    </div>


                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="safe-field-label" for="transaction_type">{{ __('ui.type') }}</label>
                        <select id="transaction_type" class="form-select">
                            <option value="">{{ __('ui.all') }}</option>
                            <option value="credit">{{ __('ui.credit') }}</option>
                            <option value="debit">{{ __('ui.debit') }}</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="safe-filter-actions">
                            <button id="safeLoadBtn" type="button" class="btn safe-btn-primary" onclick="loadLedger()">
                                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i><span>{{ __('safe.load') }}</span>
                            </button>
                            <button type="button" class="btn safe-btn-outline" onclick="printLedger()">
                                <i class="bi bi-printer" aria-hidden="true"></i><span>{{ __('safe.print') }}</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- SUMMARY -->
        <div class="safe-content-title"><div><span>{{ __('ui.balances') }}</span><h3>{{ __('ui.blade_final_balance') }}</h3></div></div>
        <div class="row g-3 mb-3" id="summary-cards" aria-live="polite"></div>

        <!-- LEDGER -->
        <div id="ledger-container" aria-live="polite"></div>

    </div>

@endsection

@section('js')
    <script src="{{ asset('assets/offline/ac82083f08b38e5c.js') }}"></script>
    <script src="{{ asset('assets/offline/00b712c3c875b8cd.js') }}"></script>

    <script>
        const escapeSafeText = value => String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[character]);
        const formatSafeAmount = value => {
            const amount = Number(value ?? 0);
            return Number.isFinite(amount) ? amount.toLocaleString(undefined, {maximumFractionDigits: 8}) : '—';
        };

        $('#date_range').daterangepicker({
            autoUpdateInput: false,
            locale: {
                format: 'YYYY-MM-DD',
                applyLabel: @json(__('safe.apply')),
                cancelLabel: @json(__('safe.cancel')),
                fromLabel: @json(__('safe.from')),
                toLabel: @json(__('safe.to')),
                customRangeLabel: @json(__('safe.custom_range')),
                weekLabel: @json(__('safe.week')),
                daysOfWeek: @json(__('safe.days')),
                monthNames: @json(__('safe.months'))
            }
        });

        $('#date_range').on('apply.daterangepicker', function(ev, picker) {
            let value = picker.startDate.format('YYYY-MM-DD') + ' - ' +
                picker.endDate.format('YYYY-MM-DD');

            $(this).val(value);

            $('#range_type').val('custom');

            loadLedger();
        });

        let safeLedgerRequest = null;
        function loadLedger() {
            if (safeLedgerRequest) safeLedgerRequest.abort();
            const loadButton = document.getElementById('safeLoadBtn');
            if (loadButton) {
                loadButton.disabled = true;
                loadButton.setAttribute('aria-busy', 'true');
                loadButton.querySelector('span').textContent = @json(__('dashboard.updating'));
            }
            $('#ledger-container').html('<div class="card safe-ledger-card"><div class="safe-empty-state"><i class="bi bi-arrow-repeat" aria-hidden="true"></i><strong>' + escapeSafeText(@json(__('dashboard.updating'))) + '</strong></div></div>');
            safeLedgerRequest = $.get("{{ route('admin.safe.ledger') }}", {
                transaction_type: $('#transaction_type').val(),
                range_type: $('#range_type').val(),
                date_range: $('#date_range').val()
            }, function(res) {

                if (!res.rows || !res.rows.length) {
                    $('#summary-cards').html('');

                    $('#ledger-container').html(`
                        <div class="card safe-ledger-card">
                            <div class="safe-empty-state">
                                <i class="bi bi-inbox" aria-hidden="true"></i>
                                <strong>{{ __('safe.no_transactions') }}</strong>
                            </div>
                        </div>
                    `);

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | SUMMARY CARDS
                |--------------------------------------------------------------------------
                */
                let summaryHtml = '';

                res.currencies.forEach(c => {
                    let val = res.closing_balances[c.id] ?? 0;

                    summaryHtml += `
                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="summary-card">
                                <div class="safe-summary-top">
                                    <span class="summary-title">${escapeSafeText(c.code)}</span>
                                    <span class="safe-summary-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
                                </div>
                                <div class="summary-value" dir="ltr">${formatSafeAmount(val)}</div>
                            </div>
                        </div>
                    `;
                });

                $('#summary-cards').html(summaryHtml);

                /*
                |--------------------------------------------------------------------------
                | LEDGER TABLE
                |--------------------------------------------------------------------------
                */
                let html = `
                    <div class="card safe-ledger-card">
                        <div class="card-body">
                            <div class="safe-ledger-title">
                                <h3><i class="bi bi-journal-text me-2" aria-hidden="true"></i>{{ __('safe.title') }}</h3>
                                <small>{{ __('safe.title') }} · ${res.rows.length}</small>
                            </div>
                            <div class="table-responsive" style="max-height:620px;">
                                <table class="table table-sm table-bordered">

                                    <thead>
                                        <tr>
                                            <th rowspan="2">#</th>
                                            <th rowspan="2">{{ __('ui.date') }}</th>
                                            <th rowspan="2">{{ __('ui.account') }}</th>
                                            <th rowspan="2">{{ __('ui.note') }}</th>
                                            <th rowspan="2">{{ __('ui.type') }}</th>
                `;

                res.currencies.forEach(c => {
                    html += `<th colspan="2" class="text-center">${escapeSafeText(c.code)}</th>`;
                });

                html += `
                                        </tr>
                                        <tr>
                `;

                res.currencies.forEach(() => {
                    html += `
                        <th class="text-end">{{ __('ui.amount') }}</th>
                        <th class="text-end">{{ __('ui.balances') }}</th>
                    `;
                });

                html += `
                                        </tr>
                                    </thead>
                                    <tbody>
                `;

                /*
                |--------------------------------------------------------------------------
                | OPENING BALANCE
                |--------------------------------------------------------------------------
                */
                html += `
                    <tr class="opening-row">
                        <td colspan="5">{{ __('ui.blade_opening_balance') }}</td>
                `;

                res.currencies.forEach(c => {
                    let opening = res.opening_balances[c.id] ?? 0;

                    html += `
                        <td></td>
                        <td class="text-end balance-cell" dir="ltr">${formatSafeAmount(opening)}</td>
                    `;
                });

                html += `</tr>`;

                /*
                |--------------------------------------------------------------------------
                | ROWS
                |--------------------------------------------------------------------------
                */
                let i = 1;

                res.rows.forEach(r => {
                    html += `
                        <tr>
                            <td>${i++}</td>
                            <td>${escapeSafeText(r.date ?? '')}</td>
                            <td>${escapeSafeText(r.account_code ?? '')} - ${escapeSafeText(r.account_name ?? @json(__('safe.cash')))}</td>
                            <td>${escapeSafeText(r.note ?? '')}</td>
                            <td>
                                <span class="badge ${r.type === 'credit' ? 'badge-credit' : 'badge-debit'}">
                                    ${escapeSafeText(({credit: @json(__('ui.credit')), debit: @json(__('ui.debit'))})[r.type] ?? r.type ?? '')}
                                </span>
                            </td>
                    `;

                    res.currencies.forEach(c => {
                        let d = r.data[c.code];

                        let amount = '';
                        let balance = '';
                        let amountClass = '';

                        if (d && d.amount !== null) {
                            amountClass = d.amount > 0 ? 'amount-credit' :
                                d.amount < 0 ? 'amount-debit' : '';

                            amount = formatSafeAmount(d.amount);
                            balance = formatSafeAmount(d.balance);
                        }

                        html += `
                            <td class="text-end ${amountClass}" dir="ltr">${amount}</td>
                            <td class="text-end balance-cell" dir="ltr">${balance}</td>
                        `;
                    });

                    html += `</tr>`;
                });

                /*
                |--------------------------------------------------------------------------
                | FINAL BALANCE
                |--------------------------------------------------------------------------
                */
                html += `
                    <tr class="footer-row">
                        <td colspan="5">{{ __('ui.blade_final_balance') }}</td>
                `;

                res.currencies.forEach(c => {
                    let val = res.closing_balances[c.id] ?? 0;

                    html += `
                        <td></td>
                        <td class="text-end balance-cell" dir="ltr">${formatSafeAmount(val)}</td>
                    `;
                });

                html += `
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                `;

                $('#ledger-container').html(html);
            }).fail(function(xhr, status) {
                if (status === 'abort') return;
                let message = xhr.responseJSON?.message ?? @json(__('safe.load_failed'));

                $('#summary-cards').html('');

                $('#ledger-container').html(`
                    <div class="card safe-error">
                        <div class="safe-empty-state">
                            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                            <strong>${escapeSafeText(message)}</strong>
                        </div>
                    </div>
                `);
            }).always(function() {
                if (loadButton && safeLedgerRequest?.readyState === 4) {
                    loadButton.disabled = false;
                    loadButton.removeAttribute('aria-busy');
                    loadButton.querySelector('span').textContent = @json(__('safe.load'));
                    safeLedgerRequest = null;
                }
            });
        }

        let isUpdating = false;

        $('#range_type').on('change', function() {
            if (isUpdating) return;

            isUpdating = true;

            $('#date_range').val('');

            isUpdating = false;

            loadLedger();
        });

        $('#date_range').on('apply.daterangepicker', function(ev, picker) {
            if (isUpdating) return;

            isUpdating = true;

            $(this).val(
                picker.startDate.format('YYYY-MM-DD') + ' - ' +
                picker.endDate.format('YYYY-MM-DD')
            );

            $('#range_type').val('custom');

            isUpdating = false;
        });

        $('#transaction_type').on('change', function() {
            loadLedger();
        });

        function printLedger() {
            let params = $.param({
                transaction_type: $('#transaction_type').val(),
                range_type: $('#range_type').val(),
                date_range: $('#date_range').val()
            });

            window.open("{{ route('admin.safe.ledger.print') }}?" + params, '_blank');
        }

        $(document).ready(function() {
            loadLedger();
        });
    </script>
@endsection
