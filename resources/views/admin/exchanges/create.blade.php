@extends('layouts.admin.base')

@section('title', __('exchanges.create_title'))

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/febd1387d851569a.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/offline/d55d61f8152b62c7.css') }}">

    <style>
        :root {
            --fin-bg: #f8fafc;
            --card-radius: 14px;
            --shadow: 0 10px 25px rgba(15, 23, 42, .07);
        }

        body {
            background: var(--fin-bg);
        }

        .card-modern {
            border: none;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header-modern {
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 18px;
        }

        .card-body-modern {
            padding: 18px;
        }

        .section-title {
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            margin: 15px 0 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title:after {
            content: "";
            height: 1px;
            background: #e2e8f0;
            flex: 1;
        }

        .form-label.small {
            font-weight: 700;
            color: #334155;
        }

        .balance-group {
            width: 100%;
            padding: 12px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            min-height: 60px;
        }

        .balance-chip {
            border-radius: 10px;
            padding: 8px 12px;
            background: #f1f5f9;
            border-left: 4px solid #6366f1;
            min-width: 120px;
        }

        .balance-chip .cur {
            font-size: .72rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
        }

        .mono {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
        }

        .balance-amount {
            font-weight: 900;
            margin-top: 2px;
        }

        .exchange-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            height: 100%;
        }

        .btn-pill {
            border-radius: 999px !important;
            padding: .55rem 1.05rem;
            font-weight: 800;
        }

        .input-lg-mono {
            font-family: 'JetBrains Mono';
            font-weight: 800;
            font-size: 1.1rem;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .date-input {
            height: 46px;
            border-radius: 8px;
            border: 1px solid #d9e2ec;
            background-color: #fff;
            padding: 0.55rem 0.75rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: #334155;
            box-shadow: none;
        }

        .date-input:focus {
            border-color: #7367f0;
            box-shadow: 0 0 0 0.15rem rgba(115, 103, 240, 0.15);
            outline: none;
        }

        .date-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0.75;
        }

        /* Create/edit exchange visual design */
        .exchange-create-page{--exc-ink:#1b304f;--exc-muted:#63748c;--exc-line:#e3eaf3;max-width:1810px;margin-inline:auto;padding-bottom:22px;color:var(--exc-ink)}
        .exchange-create-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;border-radius:17px;padding:19px 23px;margin-bottom:15px;background:linear-gradient(113deg,#172945,#29416e 62%,#3658a3);box-shadow:0 12px 29px rgba(28,48,89,.13);color:white;overflow:hidden;position:relative}
        .exchange-create-hero:after{content:"";position:absolute;inset-inline-end:-65px;top:-115px;width:190px;height:190px;border-radius:50%;border:26px solid rgba(255,255,255,.05);pointer-events:none}
        .exchange-create-hero>div,.exchange-create-hero-icon{position:relative;z-index:1}
        .exchange-create-eyebrow{display:inline-flex;align-items:center;gap:7px;color:#bfd2fa;font-size:.69rem;letter-spacing:.04em;font-weight:800}
        .exchange-create-hero h2{color:#fff;font-size:1.43rem;margin:4px 0;font-weight:800;letter-spacing:-.02em}
        .exchange-create-hero p{color:#d3def5;font-size:.76rem;margin:0}
        .exchange-create-hero-icon{width:53px;height:53px;display:grid;place-items:center;flex:0 0 53px;border-radius:14px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.17);font-size:1.5rem}
        .exchange-create-page .card-modern{border:1px solid var(--exc-line);border-radius:16px;box-shadow:0 5px 20px rgba(27,45,80,.045);overflow:visible;background:#fff}
        .exchange-create-page .card-header-modern{border-radius:16px 16px 0 0;background:#f9fbfe;border-bottom:1px solid #e8eef5;padding:14px 19px}
        .exchange-card-header-title{display:inline-flex;align-items:center;gap:9px;color:#233b5e;font-size:.87rem;font-weight:800}
        .exchange-card-header-title i{display:grid;place-items:center;width:34px;height:34px;border-radius:10px;background:#eaf0ff;color:#4269b3;font-size:1rem}
        .exchange-auto-reference{display:inline-flex;align-items:center;gap:6px;color:#72839a;font-size:.69rem;font-weight:700}
        .exchange-create-page .card-body-modern{padding:18px 20px 21px}
        .exchange-create-page .section-title{font-size:.72rem;font-weight:800;letter-spacing:.05em;color:#42608d;margin:18px 0 12px;gap:10px}
        .exchange-create-page .section-title:first-of-type{margin-top:2px}
        .exchange-create-page .section-title:after{background:#e9eef6}
        .exchange-create-page .form-label{font-size:.73rem;font-weight:750;color:#536780;margin-bottom:7px}
        .exchange-create-page .form-control,.exchange-create-page .form-select,.exchange-create-page .date-input{min-height:42px;height:42px;padding:9px 11px;border:1px solid #dce4ee;border-radius:9px;background:#fff;color:#243b5a;font-size:.8rem;font-weight:500;box-shadow:none}
        .exchange-create-page textarea.form-control{height:auto;min-height:128px;resize:vertical;font-size:.8rem;line-height:1.5}
        .exchange-create-page .form-control:focus,.exchange-create-page .form-select:focus,.exchange-create-page .date-input:focus{border-color:#809fdc;box-shadow:0 0 0 3px rgba(80,119,194,.12);outline:none}
        .exchange-create-page .select2-container{max-width:100%}
        .exchange-create-page .select2-container--default .select2-selection--single{height:42px!important;min-height:42px;border:1px solid #dce4ee!important;border-radius:9px!important;display:flex;align-items:center;padding:5px 10px}
        .exchange-create-page .select2-container--default .select2-selection--single .select2-selection__rendered{font-size:.8rem;color:#243b5a;line-height:28px;padding-inline-start:3px}
        .exchange-create-page .select2-container--default .select2-selection--single .select2-selection__arrow{top:8px}
        .exchange-create-page .balance-wrap{margin-bottom:14px}
        .exchange-create-page .balance-group{min-height:72px;padding:11px;background:#f8fafd;border:1px dashed #dce5f2;border-radius:12px;align-items:center;flex-wrap:wrap}
        .exchange-create-page .balance-chip{min-width:125px;border:1px solid #e3eaf4;border-inline-start:3px solid #426ab8;border-left:3px solid #426ab8;border-radius:10px;background:#fff;padding:10px 12px}
        .exchange-create-page .balance-chip .cur{font-size:.69rem;color:#617591;font-weight:800}
        .exchange-create-page .balance-amount{font-family:inherit;font-size:.92rem;font-weight:850;color:#233a59;overflow-wrap:anywhere;font-variant-numeric:tabular-nums}
        .exchange-create-page .exchange-logic-grid>[class*="col-"]{display:flex}
        .exchange-create-page .exchange-box{width:100%;padding:17px;border:1px solid var(--exc-line);border-radius:13px;background:linear-gradient(155deg,#fff,#fbfcff);box-shadow:0 3px 11px rgba(30,45,76,.025)}
        .exchange-create-page .exchange-sold-box{border-top:3px solid #d45c6c}
        .exchange-create-page .exchange-purchased-box{border-top:3px solid #169478}
        .exchange-create-page .exchange-calculation-box{border-top:3px solid #4c6fbb}
        .exchange-create-page .exchange-note-box{border-top:3px solid #93a3b9}
        .exchange-create-page .exchange-box .form-label{display:block;font-weight:800}
        .exchange-create-page .exchange-box .text-danger{color:#bb4359!important}
        .exchange-create-page .exchange-box .text-success{color:#0d896e!important}
        .exchange-create-page .input-lg-mono{font-family:inherit;font-variant-numeric:tabular-nums;font-weight:800;font-size:1.13rem!important;letter-spacing:-.015em;text-align:start}
        .exchange-create-page #rateHint{font-size:.72rem;background:#edf3ff;color:#426297!important;border-radius:8px;padding:8px 10px;min-height:32px;overflow-wrap:anywhere}
        .exchange-create-page .exchange-calculation-box>.mt-3{border-top:1px solid #e8edf5;padding-top:11px;margin-top:11px!important;display:grid;gap:7px}
        .exchange-create-page .exchange-calculation-box>.mt-3>div{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:6px;font-size:.73rem}
        .exchange-create-page #baseRateText,.exchange-create-page #spreadText,.exchange-create-page #profitText{font-variant-numeric:tabular-nums;font-weight:850;overflow-wrap:anywhere}
        .exchange-create-page #profitText{font-size:.86rem}
        .exchange-submit-actions{border-top:1px solid #e8edf5;margin-top:17px;padding-top:18px;display:flex;align-items:center;gap:10px}
        .exchange-create-page .exchange-save-button{display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:10px;padding:10px 23px;min-height:43px;background:#3159a6;border-color:#3159a6;font-size:.8rem;font-weight:800;box-shadow:0 5px 11px rgba(36,79,150,.13)}
        .exchange-create-page .exchange-save-button:hover{background:#244b90;border-color:#244b90}
        .exchange-create-page :is(button,select,input,textarea):focus-visible{outline:3px solid #9bb4e6;outline-offset:2px}
        @media(max-width:767px){.exchange-create-hero{padding:16px}.exchange-create-hero h2{font-size:1.22rem}.exchange-create-hero p{font-size:.69rem}.exchange-create-hero-icon{width:42px;height:42px;flex-basis:42px}.exchange-create-page .card-header-modern{padding:12px 14px}.exchange-auto-reference{font-size:.61rem;text-align:end}.exchange-create-page .card-body-modern{padding:14px}.exchange-create-page .exchange-box{padding:13px}.exchange-create-page .exchange-save-button{width:100%}}
    </style>
@endsection

@section('content')
    @php
        $usd = $currencies->firstWhere('code', 'USD');
        $usdId = $usd?->id;
    @endphp
    @php
        $isEdit = isset($exchange);
    @endphp

    <div class="exchange-create-page">
        <header class="exchange-create-hero">
            <div>
                <span class="exchange-create-eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i> {{ __('ui.blade_exchange_logic') }}</span>
                <h2>{{ $isEdit ? __('exchanges.create_title') : __('exchanges.create_title') }}</h2>
                <p>{{ __('exchanges.live_balances') }} · {{ __('exchanges.base_rate') }} · {{ __('exchanges.profit_loss') }}</p>
            </div>
            <span class="exchange-create-hero-icon" aria-hidden="true"><i class="bi bi-currency-exchange"></i></span>
        </header>
    <div class="row g-3">
        <div class="col-12">
            <div class="card card-modern">
                <div class="card-header-modern d-flex justify-content-between align-items-center gap-2">
                    <div class="exchange-card-header-title"><i class="bi bi-journal-plus" aria-hidden="true"></i><span>{{ __('ui.blade_new_transaction') }}</span></div>
                    <div class="exchange-auto-reference"><i class="bi bi-check-circle" aria-hidden="true"></i>{{ __('ui.blade_uuid_auto_generated') }}</div>
                </div>

                <div class="card-body-modern">
                    <form id="exchangeForm">
                        @csrf

                        @if ($isEdit)
                            <input type="hidden" name="id" value="{{ $exchange->id }}">
                        @endif
                        <div class="section-title">{{ __('ui.blade_identity_mode') }}</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3 col-lg-3">
                                <label class="form-label small">{{ __('ui.date') }}</label>
                                <input type="date" name="date" id="date" class="form-control date-input"
                                    value="{{ $isEdit ? \Carbon\Carbon::parse($exchange->date)->format('Y-m-d') : date('Y-m-d') }}">
                            </div>
                            <div class="col-md-3 col-lg-3">
                                <label class="form-label small">{{ __('ui.mode') }}</label>
                                <select id="mode" name="mode" class="select2 form-select">
                                    <option value="cash" {{ $isEdit && $exchange->mode == 'cash' ? 'selected' : '' }}>{{ __('ui.blade_office') }}</option>
                                    <option value="customer" {{ $isEdit && $exchange->mode == 'customer' ? 'selected' : '' }}>{{ __('ui.customer') }}</option>
                                    <option value="saraf" {{ $isEdit && $exchange->mode == 'saraf' ? 'selected' : '' }}>{{ __('ui.blade_saraf') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-3">
                                <label class="form-label small">{{ __('ui.select_account') }}</label>
                                <select id="account_id" name="account_id" class="select2 form-select"></select>
                            </div>
                        </div>

                        <div class="section-title">{{ __('exchanges.live_balances') }}</div>
                        <div id="account-balance-summary" class="balance-wrap d-flex mb-3 flex-wrap gap-2">
                            <div class="balance-group w-100 d-flex gap-2">
                                <span class="text-muted small">{{ __('ui.blade_select_an_account_to_view_details') }}</span>
                            </div>
                        </div>

                        <div class="section-title">{{ __('ui.blade_exchange_logic') }}</div>
                        <div class="row g-3 mb-3 exchange-logic-grid">
                            <div class="col-lg-3 col-md-6">
                                <div class="exchange-box exchange-sold-box">
                                    <label class="form-label small text-danger">{{ __('exchanges.sold') }}</label>
                                    <select name="from_currency_id" id="from_currency_id" class="select2 currency-selector form-select">
                                        <option value="">{{ __('ui.blade_select') }}</option>
                                        @foreach ($currencies as $c)
                                            <option value="{{ $c->id }}">{{ $c->code }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" step="any" name="from_amount" id="from_amount" class="form-control input-lg-mono calc-field numeric-input mt-3"
                                        inputmode="decimal" placeholder="0">
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <div class="exchange-box exchange-calculation-box">
                                    <label class="form-label small text-primary">{{ __('ui.blade_calculation') }}</label>
                                    <select name="operation_type" id="operation_type" class="calc-field form-select mb-3">
                                        <option value="multiply">{{ __('exchanges.multiply') }}</option>
                                        <option value="divide">{{ __('exchanges.divide') }}</option>
                                    </select>
                                    <label class="form-label small">{{ __('ui.exchange_rate') }}</label>
                                    <input type="text" step="any" name="rate" id="rate" class="form-control input-lg-mono calc-field" inputmode="decimal"
                                        placeholder="1.00">
                                    <div class="small text-muted mt-2" id="rateHint">—</div>

                                    <div class="mt-3">
                                        <div class="small text-muted">
                                            {{ __('exchanges.base_rate') }}
                                            <span id="baseRateText">—</span>
                                        </div>

                                        <div class="small text-muted">
                                            {{ __('exchanges.spread') }}
                                            <span id="spreadText">—</span>
                                        </div>

                                        <div class="fw-bold text-success">
                                            {{ __('exchanges.profit_loss') }}
                                            <span id="profitText">—</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <div class="exchange-box exchange-purchased-box">
                                    <label class="form-label small text-success">{{ __('exchanges.purchased') }}</label>
                                    <select name="to_currency_id" id="to_currency_id" class="select2 currency-selector form-select">
                                        <option value="">{{ __('ui.blade_select') }}</option>
                                        @foreach ($currencies as $c)
                                            <option value="{{ $c->id }}">{{ $c->code }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" step="any" name="to_amount" id="to_amount" class="form-control input-lg-mono calc-field numeric-input mt-3"
                                        inputmode="decimal" placeholder="0">
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <div class="exchange-box exchange-note-box">
                                    <label class="form-label small">{{ __('ui.note') }}</label>
                                    <textarea name="note" class="form-control" rows="4" placeholder="{{ __('ui.blade_transaction_note') }}"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="exchange-submit-actions">
                            <button type="submit" class="btn btn-primary exchange-save-button">
                                <i class="bi bi-check2-circle me-1"></i> {{ __('exchanges.save') }}
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('assets/offline/33859893654b9de2.js') }}"></script>

    <script>
        const exchangesSwal = Swal.mixin({confirmButtonText: @json(__('exchanges.ok')), cancelButtonText: @json(__('ui.cancel'))});
        $(document).ready(function() {

            const cashAccount = @json($cashAccount);
            const customers = @json($customers);
            const sarafan = @json($sarafan);
            const currencies = @json($currencies->map(fn($c) => ['id' => $c->id, 'code' => $c->code])->values());
            const USD_ID = {{ (int) ($usdId ?? 0) }};
            let BASE_RATE = 0;
            let rateTouchedByUser = false;

            // =============================
            // CSRF (for AJAX post)
            // =============================
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // =============================
            // SELECT2 INIT
            // =============================
            $('.select2').select2({
                    language: @include('admin.exchanges.partials.select-language'),
                width: '100%'
            });

            const exchange = @json($exchange ?? null);

            if (exchange) {

                $('#date').val(exchange.date);

                $('#mode').val(exchange.mode).trigger('change');

                setTimeout(() => {
                    $('#account_id').val(exchange.account_id).trigger('change');
                }, 300);

                $('#from_currency_id').val(exchange.from_currency_id).trigger('change');
                $('#to_currency_id').val(exchange.to_currency_id).trigger('change');

                $('#from_amount').val(exchange.from_amount);
                $('#to_amount').val(exchange.to_amount);
                $('#rate').val(exchange.rate);
                $('#operation_type').val(exchange.operation_type).trigger('change');
                $('#note').val(exchange.note);

                $('#baseRateText').text(exchange.base_rate);
                $('#spreadText').text(exchange.spread);
                $('#profitText').text(exchange.profit);

            }

            function getCurrencyCode(id) {
                const cur = currencies.find(x => String(x.id) === String(id));
                return cur ? cur.code : '';
            }

            // =============================
            // ACCOUNTS (FIXED)
            // =============================
            function loadAccounts() {
                const mode = $('#mode').val();
                const $acc = $('#account_id');

                $acc.off('change');
                $acc.empty();

                if (mode === 'cash') {
                    $acc.append(new Option(cashAccount.name, cashAccount.id, true, true));
                    $acc.prop('disabled', true);
                } else if (mode === 'customer') {
                    $acc.prop('disabled', false);
                    $acc.append(new Option(@json(__('exchanges.select_customer')), '', true, false));
                    customers.forEach(c => $acc.append(new Option(c.name, c.id)));
                } else if (mode === 'saraf') {
                    $acc.prop('disabled', false);
                    $acc.append(new Option(@json(__('exchanges.select_saraf')), '', true, false));
                    sarafan.forEach(c => $acc.append(new Option(c.name, c.id)));
                }

                $acc.select2({
                    language: @include('admin.exchanges.partials.select-language'),
                    width: '100%'
                });

                $acc.on('change', function() {
    loadBalances($(this).val());
    calculateLiveProfit();
});

                const firstId = $acc.val();
                loadBalances(firstId);
            }

            $('#mode').on('change', loadAccounts);

            // =============================
            // BALANCES (FIXED)
            // =============================
            function loadBalances(accountId) {
                if (!accountId) {
                    $('#account-balance-summary').html(`
                        <div class="balance-group w-100 d-flex gap-2">
                            <span class="text-muted small">{{ __('ui.blade_select_an_account_to_view_details') }}</span>
                        </div>
                    `);
                    return;
                }

                $.get("{{ route('admin.exchanges.balances') }}", {
                        account_id: accountId
                    })
                    .done(function(res) {
                        let html = '<div class="balance-group w-100 d-flex flex-wrap gap-2">';

                        if (!res || !res.balances || Object.keys(res.balances).length === 0) {
                            html += '<span class="text-muted small">{{ __('ui.blade_no_balances') }}</span>';
                        } else {
                            $.each(res.balances, function(code, val) {
                                const bal = parseFormattedNumber(val.balance ?? 0);
                                const balanceClass = bal < 0 ? 'text-danger' : '';
                                html += `
                                    <div class="balance-chip">
                                        <div class="cur">${code}</div>
                                        <div class="balance-amount mono ${balanceClass}">
                                            ${new Intl.NumberFormat().format(bal)}
                                        </div>
                                    </div>
                                `;
                            });
                        }

                        html += '</div>';
                        $('#account-balance-summary').html(html);
                    })
                    .fail(function() {
                        $('#account-balance-summary').html(`
                            <div class="balance-group w-100 d-flex gap-2">
                                <span class="text-danger small">{{ __('ui.blade_failed_to_load_balances_2') }}</span>
                            </div>
                        `);
                    });
            }

            // =============================
            // CURRENCY EXCLUSION + AUTO OPERATION
            // =============================
            function enforceCurrencyExclusion() {
                const from = $('#from_currency_id').val();
                const to = $('#to_currency_id').val();

                $("#from_currency_id option, #to_currency_id option").prop('disabled', false);
                if (from) $('#to_currency_id option[value="' + from + '"]').prop('disabled', true);
                if (to) $('#from_currency_id option[value="' + to + '"]').prop('disabled', true);

                $('#from_currency_id, #to_currency_id').select2({
                    language: @include('admin.exchanges.partials.select-language'),
                    width: '100%'
                });
            }

            function autoOperationBasedOnUSD() {
                const from = parseInt($('#from_currency_id').val() || 0);
                const to = parseInt($('#to_currency_id').val() || 0);

                if (!from || !to) return;

                /*
                |--------------------------------------------------------------------------
                | Keep existing USD behavior
                |--------------------------------------------------------------------------
                | USD -> other currency usually multiply
                | other currency -> USD usually divide
                |--------------------------------------------------------------------------
                */
                if (USD_ID && from === USD_ID && to !== USD_ID) {
                    $('#operation_type').val('multiply').trigger('change');
                    return;
                }

                if (USD_ID && to === USD_ID && from !== USD_ID) {
                    $('#operation_type').val('divide').trigger('change');
                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Cross currency pairs
                |--------------------------------------------------------------------------
                | For PKR -> AFN, AED -> AFN, etc.
                | Default is multiply because rate means:
                | 1 FROM = X TO
                |--------------------------------------------------------------------------
                */
                $('#operation_type').val('multiply').trigger('change');
            }

            // =============================
            // RATE AUTO FETCH (SMART ENGINE)
            // =============================
            function fetchMarketRate() {
                const from = $('#from_currency_id').val();
                const to = $('#to_currency_id').val();

                if (!from || !to) return;

                if (rateTouchedByUser) return;

                $.get("{{ route('admin.exchanges.rate') }}", {
                    from_currency_id: from,
                    to_currency_id: to
                }).done(function(res) {

                    if (res && res.rate) {

                        $('#rate').val(res.rate);

                        BASE_RATE = parseFormattedNumber(res.base_rate || res.rate || 0);

                        autoOperationBasedOnUSD();
                        updateRateHint();
                        calculateFromSource('from_amount');
                        calculateLiveProfit();

                        $('#rateHint').text(@json(__('exchanges.auto_market_rate')));

                    } else {
                        BASE_RATE = 0;
                        $('#rateHint').text(@json(__('exchanges.no_rate')));
                        $('#baseRateText').text('—');
                        $('#spreadText').text('—');
                        $('#profitText').text('—');
                    }

                }).fail(function() {
                    $('#rateHint').text(@json(__('exchanges.rate_failed')));
                });
            }

            function updateRateHint() {
                const rate = parseFormattedNumber($('#rate').val() || 0);
                const from = parseInt($('#from_currency_id').val() || 0);
                const to = parseInt($('#to_currency_id').val() || 0);

                if (!rate || !from || !to) {
                    $('#rateHint').text('—');
                    return;
                }

                const fromCode = getCurrencyCode(from);
                const toCode = getCurrencyCode(to);

                $('#rateHint').text(`1 ${fromCode} = ${rate} ${toCode}`);
            }

            // =============================
            // CALCULATION ENGINE
            // =============================
            function calculateFromSource(sourceId) {
                const op = $('#operation_type').val();
                const rate = parseFormattedNumber($('#rate').val() || 0);

                if (!rate || rate <= 0) return;

                const fromVal = parseFormattedNumber($('#from_amount').val() || 0);
                const toVal = parseFormattedNumber($('#to_amount').val() || 0);

                if (sourceId === 'to_amount') {
                    if (toVal <= 0) return;

                    const res = (op === 'multiply') ? (toVal / rate) : (toVal * rate);
                    $('#from_amount').val(res.toFixed(2));
                } else {
                    if (fromVal <= 0) return;

                    const res = (op === 'multiply') ? (fromVal * rate) : (fromVal / rate);
                    $('#to_amount').val(res.toFixed(2));
                }
            }

            // =============================
            // EVENTS
            // =============================
            $('.currency-selector').on('change', function() {
                enforceCurrencyExclusion();
                autoOperationBasedOnUSD();
                fetchMarketRate();
            });

            $('#operation_type').on('change', function() {
                calculateFromSource('from_amount');
            });

            $('#from_amount').on('input', function() {
                calculateFromSource('from_amount');
                calculateLiveProfit();
            });

            $('#to_amount').on('input', function() {
                calculateFromSource('to_amount');
                calculateLiveProfit();
            });

            $('#rate').on('input', function() {
                rateTouchedByUser = true;
                updateRateHint();
                calculateFromSource('from_amount');
                calculateLiveProfit();
            });

            $('#date').on('change', function() {
                rateTouchedByUser = false;
                fetchMarketRate();
            });

            // =============================
            // SUBMIT
            // =============================
            $('#exchangeForm').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);

                // 🔥 FIX: restore correct names
                form.find('#from_amount').attr('name', 'from_amount');
                form.find('#to_amount').attr('name', 'to_amount');
                form.find('#rate').attr('name', 'rate');

                // 🔥 Normalize numbers
                form.find('.numeric-input').each(function() {
                    this.value = convertToEnglishNumbers(this.value);
                });

                // 🔥 Validation
                let fromAmount = parseFormattedNumber(form.find('[name="from_amount"]').val());

                if (!fromAmount || fromAmount <= 0) {
                    exchangesSwal.fire({
                        icon: 'warning',
                        title: '{{ __('ui.blade_sold_amount_required') }}',
                        text: '{{ __('ui.blade_please_enter_sold_amount') }}'
                    });
                    return;
                }

                console.log(form.serialize()); // DEBUG

                // =============================
                // 🔥 CREATE vs UPDATE DETECTION
                // =============================
                let exchangeId = form.find('[name="id"]').val();

                let url = exchangeId ?
                    `/admin/exchanges/${exchangeId}` :
                    "{{ route('admin.exchanges.store') }}";

                let method = exchangeId ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    type: method,
                    data: form.serialize(),

                    success: function(res) {
                        exchangesSwal.fire({
                            icon: 'success',
                            title: '{{ __('ui.blade_saved') }}',
                            text: res.message || @json(__('exchanges.exchange_saved')),
                            timer: 1200,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    },

                    error: function(xhr) {

                        console.log(xhr.responseJSON);

                        let msg = xhr.responseJSON?.message || @json(__('exchanges.failed'));

                        if (xhr.responseJSON?.errors) {
                            msg = Object.values(xhr.responseJSON.errors)
                                .flat()
                                .join('<br>');
                        }

                        exchangesSwal.fire({
                            icon: 'error',
                            title: '{{ __('ui.blade_error') }}',
                            html: msg
                        });
                    }
                });
            });

            function calculateLiveProfit() {

    const rate = parseFormattedNumber($('#rate').val() || 0);
    const baseRate = parseFormattedNumber(BASE_RATE || 0);

    const from = parseInt($('#from_currency_id').val() || 0);
    const to = parseInt($('#to_currency_id').val() || 0);

    const fromVal = parseFormattedNumber($('#from_amount').val() || 0);
    const toVal = parseFormattedNumber($('#to_amount').val() || 0);

    const accountId = parseInt($('#account_id').val() || 0);

    if (!rate || !baseRate || !from || !to || !fromVal || !toVal) {
        $('#baseRateText').text('—');
        $('#spreadText').text('—');
        $('#profitText').text('—');
        return;
    }

    const fromCode = getCurrencyCode(from);
    const toCode = getCurrencyCode(to);

    /*
    |--------------------------------------------------------------------------
    | Backend same logic
    |--------------------------------------------------------------------------
    | Normal profit:
    | actual received - market expected received
    |
    | If account_id != 1:
    | profit works opposite, same as adjustProfitByAccountPerspective()
    |--------------------------------------------------------------------------
    */

    const marketToAmount = fromVal * baseRate;
    const actualToAmount = toVal;

    let profitAmount = actualToAmount - marketToAmount;
    let spread = rate - baseRate;

    // 🔥 Same as backend: if account_id is not 1, reverse profit/loss
    if (accountId && accountId !== 1) {
        profitAmount = profitAmount * -1;
        spread = spread * -1;
    }

    $('#baseRateText').text(`1 ${fromCode} = ${baseRate.toFixed(8)} ${toCode}`);
    $('#spreadText').text(spread.toFixed(8));

    const formattedProfit = new Intl.NumberFormat().format(
        parseFormattedNumber(profitAmount.toFixed(2))
    );

    $('#profitText').text(`${formattedProfit} ${toCode}`);

    if (profitAmount < 0) {
        $('#profitText').removeClass('text-success').addClass('text-danger');
    } else {
        $('#profitText').removeClass('text-danger').addClass('text-success');
    }
}

            $('#from_amount, #to_amount, #rate, #operation_type, #account_id, #mode').on('input change', function() {
    calculateLiveProfit();
});

            // =============================
            // INITIAL BOOT
            // =============================
            loadAccounts();
            enforceCurrencyExclusion();
            updateRateHint();
        });
    </script>
@endsection
