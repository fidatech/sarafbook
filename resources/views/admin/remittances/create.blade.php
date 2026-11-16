@extends('layouts.admin.base')

@section('title', __('ui.create_new_remittance'))

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
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
            margin: 4px 0 10px;
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

        .balance-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .balance-group {
            width: 100%;
            padding: 10px 12px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .balance-group-title {
            font-size: .78rem;
            font-weight: 900;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .balance-chip {
            border-radius: 10px;
            padding: 10px 12px;
            background: #f1f5f9;
            border-left: 4px solid #3b82f6;
            min-width: 140px;
        }

        .balance-chip .cur {
            font-size: .72rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .mono {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
        }

        .balance-amount {
            font-weight: 900;
            margin-top: 2px;
        }

        .bal-neg {
            color: #dc2626;
        }

        .bal-pos {
            color: #0f172a;
        }

        .btn-pill {
            border-radius: 999px !important;
            padding: .55rem 1.05rem;
            font-weight: 800;
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

        /* Remittance creation: compact, consistent financial form */
        .remit-create-page{--rm-ink:#1d3150;--rm-muted:#63748d;--rm-border:#e4ebf4;max-width:1820px;margin-inline:auto;padding-bottom:24px;color:var(--rm-ink)}
        .remit-hero{display:flex;justify-content:space-between;align-items:center;gap:18px;background:linear-gradient(113deg,#172946,#29416f 62%,#3659a4);color:#fff;border-radius:17px;padding:19px 23px;margin-bottom:15px;position:relative;overflow:hidden;box-shadow:0 12px 28px rgba(27,47,89,.13)}
        .remit-hero:after{content:"";position:absolute;inset-inline-end:-75px;top:-110px;width:195px;height:195px;border-radius:50%;border:27px solid rgba(255,255,255,.045);pointer-events:none}
        .remit-hero>div,.remit-header-action{position:relative;z-index:1}
        .remit-eyebrow{display:inline-flex;align-items:center;gap:7px;font-size:.69rem;color:#c6d6fa;letter-spacing:.04em;font-weight:800}
        .remit-hero h2{font-size:1.43rem;color:#fff;font-weight:800;letter-spacing:-.025em;margin:4px 0}
        .remit-hero p{color:#d3def3;font-size:.76rem;margin:0}
        .remit-header-action{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;white-space:nowrap;border-radius:10px;background:#fff;color:#284a8b;font-weight:800;font-size:.76rem;padding:9px 14px;border:1px solid rgba(255,255,255,.85);box-shadow:0 4px 12px rgba(16,29,60,.12)}
        .remit-header-action:hover{background:#eaf1ff;color:#1e3d75}
        .remit-create-page .card-modern{border:1px solid var(--rm-border);border-radius:15px;box-shadow:0 4px 18px rgba(29,47,83,.045);overflow:visible;background:#fff}
        .remit-create-page .card-header-modern{padding:14px 19px;border-bottom:1px solid #e8eef5;background:#f9fbfe;border-radius:15px 15px 0 0}
        .remit-form-heading{display:flex;align-items:center;gap:11px}
        .remit-form-heading strong{display:block;color:#243a5c;font-size:.89rem;font-weight:800}
        .remit-form-heading small{display:block;color:#798ba1;font-size:.68rem;margin-top:2px}
        .remit-form-icon{display:grid;place-items:center;flex:0 0 37px;height:37px;border-radius:10px;background:#eaf0ff;color:#4168b0;font-size:1.08rem}
        .remit-create-page .card-body-modern{padding:19px 20px 21px}
        .remit-create-page .section-title{font-size:.72rem;letter-spacing:.045em;color:#41618f;font-weight:800;margin:20px 0 12px;gap:10px}
        .remit-create-page .section-title:first-of-type{margin-top:0}
        .remit-create-page .section-title:after{background:#e7edf5}
        .remit-create-page .form-label{font-size:.72rem!important;font-weight:750;color:#586b83;margin-bottom:6px}
        .remit-create-page .form-control,.remit-create-page .form-select{min-height:41px;height:41px;background:#fff;border:1px solid #dce5ef;border-radius:9px;color:#263b59;font-size:.79rem;box-shadow:none;padding:8px 11px}
        .remit-create-page .form-control:focus,.remit-create-page .form-select:focus{border-color:#8ca7dd;box-shadow:0 0 0 3px rgba(80,117,187,.11)}
        .remit-create-page .form-control[inputmode="decimal"]{font-weight:800;letter-spacing:-.01em;font-variant-numeric:tabular-nums}
        .remit-create-page .select2-container{max-width:100%}
        .remit-create-page .select2-container--default .select2-selection--single{height:41px!important;min-height:41px;border:1px solid #dce5ef!important;border-radius:9px!important;display:flex;align-items:center;padding:4px 10px!important}
        .remit-create-page .select2-container--default .select2-selection__rendered{font-size:.79rem;color:#263b59!important;line-height:28px!important;padding-inline-start:3px!important}
        .remit-create-page .select2-container--default .select2-selection__arrow{top:8px!important}
        .remit-create-page .balance-wrap{background:#f8fafd;border:1px dashed #dce5f0;border-radius:12px;padding:10px;min-height:50px;align-items:center}
        .remit-create-page .balance-group{background:#fff;border:1px solid #e2eaf3;padding:12px;border-radius:11px}
        .remit-create-page .balance-group-title{font-weight:800;color:#304969;font-size:.74rem;border-bottom:1px solid #edf1f6;padding-bottom:9px;margin-bottom:10px}
        .remit-create-page .balance-chip{background:#f7faff;border:1px solid #e2e9f5;border-inline-start:3px solid #5278bf;border-left:3px solid #5278bf;border-radius:9px;min-width:120px;padding:9px 12px}
        .remit-create-page .balance-chip .cur{font-size:.68rem;color:#677b97;letter-spacing:.02em}
        .remit-create-page .balance-amount{font-family:inherit;font-size:.92rem;font-weight:850;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}
        .remit-create-page .bal-neg{color:#c64b60}.remit-create-page .bal-pos{color:#1e456d}
        .remit-create-page .mode-box{display:flex;flex-wrap:wrap;gap:7px;padding-top:0;min-height:41px;align-items:center}
        .remit-create-page .mode-box label{display:inline-flex;align-items:center;gap:7px;padding:9px 11px;border:1px solid #dfe6f0;border-radius:9px;background:#f8fafd;color:#3e526f;font-size:.74rem;font-weight:750;min-height:38px;cursor:pointer;transition:background .16s,border-color .16s}
        .remit-create-page .mode-box label:has(input:checked){background:#eaf1ff;color:#295298;border-color:#a7bbe6}
        .remit-create-page .mode-box label:has(input:disabled){opacity:.45;cursor:not-allowed}
        .remit-create-page .mode-box input{accent-color:#315aaf;margin:0}
        .remit-form-actions{display:flex;align-items:center;gap:9px;flex-wrap:wrap;border-top:1px solid #e9eef5;padding-top:18px;margin-top:18px}
        .remit-create-page .remit-submit-btn,.remit-create-page .remit-cancel-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:10px;min-height:42px;padding:10px 22px;font-size:.79rem;font-weight:800;box-shadow:none}
        .remit-create-page .remit-submit-btn{background:#16836b;border-color:#16836b}
        .remit-create-page .remit-submit-btn:hover{background:#0e6f59;border-color:#0e6f59}
        .remit-create-page .remit-cancel-btn{border-color:#d9e2ef;background:#f7f9fc;color:#425a78}
        .remit-create-page .remit-cancel-btn:hover{background:#ebf0f7}
        .remit-create-page :is(button,a,input,select):focus-visible{outline:3px solid #97b0e1;outline-offset:2px}
        @media(max-width:767px){.remit-hero{padding:16px}.remit-hero h2{font-size:1.2rem}.remit-hero p{font-size:.69rem}.remit-header-action{padding:9px}.remit-header-action span{display:none}.remit-create-page .card-header-modern{padding:13px}.remit-create-page .card-body-modern{padding:14px}.remit-create-page .section-title{margin-top:16px}.remit-create-page .remit-submit-btn,.remit-create-page .remit-cancel-btn{flex:1}}
        @media(prefers-reduced-motion:reduce){.remit-create-page .mode-box label{transition:none}}
    </style>
@endsection

@section('content')
    <div class="remit-create-page">
        <header class="remit-hero">
            <div>
                <span class="remit-eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i>{{ __('ui.create_new_remittance') }}</span>
                <h2>{{ __('ui.create_new_remittance') }}</h2>
                <p>{{ __('ui.saraf_accounts') }} · {{ __('remittances.amounts_currencies') }}</p>
            </div>
            <a href="{{ route('admin.remittances.outgoing') }}" class="btn remit-header-action"><i class="bi bi-list-ul" aria-hidden="true"></i><span>{{ __('ui.pending_remittances') }}</span></a>
        </header>
    <div class="row g-3">
        <div class="col-12">
            <div class="card card-modern">
                <div class="card-header-modern">
                    <div class="remit-form-heading"><span class="remit-form-icon"><i class="bi bi-send" aria-hidden="true"></i></span><div><strong>{{ __('ui.create_new_remittance') }}</strong><small>{{ __('ui.basic') }}</small></div></div>
                </div>

                <div class="card-body-modern">
                    <form id="remittanceForm">
                        @csrf
                        <input type="hidden" name="page" value="remittance">

                        <div class="section-title">{{ __('ui.saraf_accounts') }}</div>
                        <div class="row g-3 mb-3">

                            <div class="col-md-3">
                                <label class="form-label small">{{ __('ui.type') }}</label>
                                <select name="type" id="type" class="form-select">
                                    <option value="outgoing">{{ __('ui.outgoing') }}</option>
                                    <option value="incoming">{{ __('ui.incoming') }}</option>
                                </select>
                            </div>

                            <div class="col-md-4" id="track_account_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.track_account') }}</label>
                                <select name="track_account_id" id="track_account_id" class="select2 form-select">
                                    <option value="">{{ __('remittances.select_track_account') }}</option>
                                    @foreach ($accounts->whereIn('account_sub_category_id', [1, 2]) as $a)
                                        <option value="{{ $a->id }}" data-sub-category-id="{{ $a->account_sub_category_id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4" id="from_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.from_saraf') }}</label>
                                <select name="from_account_id" id="from_account_id" class="select2 form-select">
                                    <option value="">{{ __('remittances.select_from_saraf') }}</option>
                                    @foreach ($sarafs as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4" id="to_wrap">
                                <label class="form-label small">{{ __('ui.to_saraf') }}</label>
                                <select name="to_account_id" id="to_account_id" class="select2 form-select">
                                    <option value="">{{ __('remittances.select_to_saraf') }}</option>
                                    @foreach ($sarafs as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        <div class="section-title">{{ __('ui.selected_saraf_balances') }}</div>
                        <div id="account-balance-summary" class="balance-wrap mb-3">
                            <span class="text-muted small">{{ __('ui.select_from_to_account_to_view_balances') }}</span>
                        </div>

                        <div class="section-title">{{ __('ui.basic') }}</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small">{{ __('ui.date') }}</label>
                                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small">{{ __('ui.hawala_no') }}</label>
                                <input type="text" name="hawala_number" class="form-control" placeholder="">
                            </div>

                            <div class="col-md-3" id="hawala_no_wrap" style="display:none;">
                                <label class="form-label small">{{ __('remittances.track_hawala_no') }}</label>
                                <input type="text" name="track_hawala_number" class="form-control" placeholder="">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small">{{ __('ui.mode') }}</label>
                                <div class="mode-box">
                                    <label><input type="radio" name="remittance_mode" value="track"> {{ __('ui.track') }}</label>
                                    <label><input type="radio" name="remittance_mode" value="no_track"> {{ __('ui.unpaid') }}</label>
                                    <label><input type="radio" name="remittance_mode" value="treasure" checked> {{ __('ui.treasure') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="section-title">{{ __('remittances.amounts_currencies') }}</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-2">
                                <label class="form-label small" id="sarafCurrencyLabel">{{ __('remittances.saraf_currency') }}</label>
                                <select name="currency_id" id="currency_id" class="select2 form-select">
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small" id="sarafAmountLabel">{{ __('remittances.saraf_receipt') }}</label>
                                <input type="text" id="amount" name="amount" class="form-control numeric-input" inputmode="decimal" placeholder="0" required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small" id="partnerCurrencyLabel">{{ __('remittances.execution_currency') }}</label>
                                <select name="partner_currency_id" id="partner_currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small" id="partnerAmountLabel">{{ __('remittances.execution_amount') }}</label>
                                <input type="text" inputmode="decimal" step="0.01" name="partner_amount" class="form-control" placeholder="0">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small" id="actualCurrencyLabel">{{ __('remittances.actual_currency') }}</label>
                                <select name="actual_currency_id" id="actual_currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small" id="actualAmountLabel">{{ __('remittances.actual_amount') }}</label>
                                <input type="text" inputmode="decimal" step="0.01" name="actual_amount" class="form-control" placeholder="0">
                            </div>
                        </div>

                        <div class="section-title">{{ __('ui.parties') }}</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small">{{ __('ui.sender') }}</label>
                                <input type="text" name="sender" class="form-control" placeholder="">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">{{ __('ui.receiver') }}</label>
                                <input type="text" name="receiver" class="form-control" placeholder="">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">{{ __('ui.description') }}</label>
                                <input type="text" name="description" class="form-control" placeholder="">
                            </div>
                        </div>

                        <div class="section-title">{{ __('ui.commission') }}</div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small">{{ __('ui.commission_mode') }}</label>
                                <select name="commission_mode" id="commission_mode" class="form-select">
                                    <option value="none">{{ __('ui.no_commission') }}</option>
                                    <option value="company">{{ __('ui.company_commission') }}</option>
                                    <option value="split">{{ __('ui.split_commission') }}</option>
                                    <option value="pay">{{ __('ui.pay_commission') }}</option>
                                </select>
                            </div>

                            <div class="col-md-3" id="commission_currency_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.commission_currency') }}</label>
                                <select name="commission_currency_id" id="commission_currency_id" class="select2 form-select">
                                    <option value="">{{ __('ui.select_currency') }}</option>
                                    @foreach ($currencies as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3" id="kabul_commission_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.commission_kabul') }}</label>
                                <input type="text" inputmode="decimal" step="0.01" name="commission_kabul_amount" class="form-control" placeholder="0">
                            </div>

                            <div class="col-md-3" id="partner_commission_wrap" style="display:none;">
                                <label class="form-label small">{{ __('ui.commission_partner') }}</label>
                                <input type="text" inputmode="decimal" step="0.01" name="commission_partner_amount" class="form-control" placeholder="0">
                            </div>
                        </div>

                        <div class="remit-form-actions">
                            <button type="submit" id="remittanceSaveBtn" class="btn btn-success remit-submit-btn">
                                <i class="bi bi-check-circle me-2"></i> {{ __('ui.save') }}
                            </button>
                            <a href="{{ route('admin.remittances.outgoing') }}" class="btn btn-outline-secondary remit-cancel-btn">
                                {{ __('ui.cancel') }}
                            </a>
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
        const remittancesSwal = Swal.mixin({confirmButtonText: @json(__('remittances.ok')), cancelButtonText: @json(__('ui.cancel'))});
        const formatCurrency = (val) => new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2
        }).format(val);

        $('.select2').select2({
            language: @include('admin.remittances.partials.select-language'),
            width: '100%'
        });

        $(document).ready(function() {

            function updateCurrencyLabelColors() {
                let type = $('#type').val();

                // remove all first (important)
                $('#sarafCurrencyLabel, #sarafAmountLabel, #partnerCurrencyLabel, #partnerAmountLabel')
                    .removeClass('text-primary text-danger');

                if (type === 'incoming') {
                    // incoming
                    $('#sarafCurrencyLabel, #sarafAmountLabel').addClass('text-danger');
                    $('#partnerCurrencyLabel, #partnerAmountLabel').addClass('text-primary');
                } else {
                    // outgoing (default)
                    $('#sarafCurrencyLabel, #sarafAmountLabel').addClass('text-primary');
                    $('#partnerCurrencyLabel, #partnerAmountLabel').addClass('text-danger');
                }
            }

            // run on load
            updateCurrencyLabelColors();

            // run on change
            $('#type').on('change', function() {
                updateCurrencyLabelColors();
            });

        });

        // ===============================
        // TYPE TOGGLE + AUTO MODE
        // ===============================
        function toggleTypeFields() {
            let t = $('#type').val();
            $('#from_wrap').toggle(t === 'incoming');
            $('#to_wrap').toggle(t === 'outgoing');
        }
        toggleTypeFields();

        $('#type').on('change', function() {
            toggleTypeFields();

            let type = $(this).val();

            if (type === 'outgoing') {
                // auto select treasure
                $('input[name="remittance_mode"][value="treasure"]')
                    .prop('checked', true)
                    .trigger('change');

                // disable no_track
                $('input[name="remittance_mode"][value="no_track"]')
                    .prop('disabled', true);

            } else {
                // enable no_track again
                $('input[name="remittance_mode"][value="no_track"]')
                    .prop('disabled', false);

                // auto select no_track
                $('input[name="remittance_mode"][value="no_track"]')
                    .prop('checked', true)
                    .trigger('change');
            }

            toggleModeFields();
            renderBalanceSummary();
            autoGenerateHawala();
        });

        // ===============================
        // MODE TOGGLE
        // ===============================
        let hawalaManuallyChanged = false;
        let trackHawalaManuallyChanged = false;

        function toggleModeFields() {
            let mode = $('input[name="remittance_mode"]:checked').val();
            let trackSubCategoryId = Number($('#track_account_id option:selected').data('sub-category-id'));
            let showTrackHawalaNumber = mode === 'track' && trackSubCategoryId !== 1;

            $('#track_account_wrap').toggle(mode === 'track');
            $('#hawala_no_wrap').toggle(showTrackHawalaNumber);

            if (!showTrackHawalaNumber) {
                $('input[name="track_hawala_number"]').val('');
                trackHawalaManuallyChanged = false;
            }
        }
        toggleModeFields();

        $('input[name="remittance_mode"]').on('change', function() {
            toggleModeFields();
            renderBalanceSummary();
            autoGenerateHawala();
        });

        // ===============================
        // CURRENCY SYNC (FIXED FOR SELECT2)
        // ===============================
        function syncPartnerCurrency() {
            let localCurrency = $('#currency_id').val();
            if (!localCurrency) return;

            $('#partner_currency_id')
                .val(localCurrency)
                .trigger('change.select2');
            $('#actual_currency_id')
                .val(localCurrency)
                .trigger('change.select2');
        }

        // init after select2 ready
        setTimeout(() => {
            syncPartnerCurrency();
        }, 100);

        $('#currency_id').on('change', function() {
            syncPartnerCurrency();
        });

        // ===============================
        // AMOUNT SYNC
        // ===============================
        let partnerAmountManuallyChanged = false;
        let actualAmountManuallyChanged = false;

        $('input[name="amount"]').on('input', function() {
            if (!partnerAmountManuallyChanged) {
                $('input[name="partner_amount"]').val($(this).val());
                $('input[name="actual_amount"]').val($(this).val());
            }
        });

        $('input[name="partner_amount"]').on('input', function() {
            partnerAmountManuallyChanged = true;
            actualAmountManuallyChanged = true;
        });

        // ===============================
        // COMMISSION
        // ===============================
        function toggleCommissionFields() {
            let mode = $('#commission_mode').val();

            if (mode === 'none') {
                $('#commission_currency_wrap,#kabul_commission_wrap,#partner_commission_wrap').hide();
                return;
            }

            $('#commission_currency_wrap,#kabul_commission_wrap').show();
            $('#partner_commission_wrap').toggle(mode === 'split' || mode === 'pay');
            $('#kabul_commission_wrap').toggle(mode === 'company' || mode === 'split');
        }
        $('#commission_mode').on('change', function() {
            let mode = $(this).val();

            if (mode === 'pay') {
                $('input[name="commission_kabul_amount"]').val('');
            }

            toggleCommissionFields();
        });

        toggleCommissionFields();

        $('#commission_mode').on('change', toggleCommissionFields);

        // ===============================
        // ACCOUNT BALANCES
        // ===============================
        function fetchAccountBalances(accountId, cb) {
            if (!accountId) return cb(null);

            $.get("{{ route('admin.remittances.account-balances') }}", {
                account_id: accountId
            }, function(res) {
                cb(res.balances || {});
            });
        }

        function renderBalanceSummary() {
            let type = $('#type').val();
            let mode = $('input[name="remittance_mode"]:checked').val();
            let fromId = $('#from_account_id').val();
            let toId = $('#to_account_id').val();
            let trackId = $('#track_account_id').val();

            $('#account-balance-summary').html('<span class="text-muted small">{{ __('ui.blade_loading') }}</span>');

            let accountsToCheck = [];

            if (type === 'incoming' && fromId) accountsToCheck.push({
                id: fromId,
                label: @json(__('remittances.from_saraf'))
            });
            if (type === 'outgoing' && toId) accountsToCheck.push({
                id: toId,
                label: @json(__('remittances.to_saraf'))
            });
            if (mode === 'track' && trackId) accountsToCheck.push({
                id: trackId,
                label: @json(__('remittances.track_account'))
            });

            if (accountsToCheck.length === 0) {
                $('#account-balance-summary').html('<span class="text-muted small">{{ __('ui.blade_select_accounts_to_view_balances') }}</span>');
                return;
            }

            let html = '';
            let pending = accountsToCheck.length;

            accountsToCheck.forEach((item) => {
                fetchAccountBalances(item.id, (balances) => {

                    html += `<div class="balance-group">
                        <div class="balance-group-title">
                            <span>${item.label}</span>
                            <span class="text-muted mono">#${item.id}</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">`;

                    if (!balances || Object.keys(balances).length === 0) {
                        html += `<div class="text-muted small">{{ __('ui.blade_no_balance_data') }}</div>`;
                    } else {
                        Object.entries(balances).forEach(([cur, b]) => {
                            let cls = (b.balance < 0) ? 'bal-neg' : 'bal-pos';
                            html += `<div class="balance-chip">
                                <div class="cur">${cur}</div>
                                <div class="balance-amount mono ${cls}">${formatCurrency(b.balance)}</div>
                             </div>`;
                        });
                    }

                    html += `</div></div>`;

                    pending--;
                    if (pending === 0) {
                        $('#account-balance-summary').html(html);
                    }
                });
            });
        }

        const trackAccountOptions = $('#track_account_id option').map(function() {
            return $(this).clone()[0];
        }).get();

        function syncTrackAccountOptions() {
            let selectedAccountIds = new Set([
                String($('#from_account_id').val() || ''),
                String($('#to_account_id').val() || '')
            ].filter(Boolean));
            let currentTrackAccountId = String($('#track_account_id').val() || '');
            let trackSelect = $('#track_account_id');

            trackSelect.empty();

            trackAccountOptions.forEach(function(option) {
                if (!option.value || !selectedAccountIds.has(String(option.value))) {
                    trackSelect.append($(option).clone());
                }
            });

            if (currentTrackAccountId && !selectedAccountIds.has(currentTrackAccountId)) {
                trackSelect.val(currentTrackAccountId);
            } else {
                trackSelect.val('');
                trackHawalaManuallyChanged = false;
                $('input[name="track_hawala_number"]').val('');
            }

            trackSelect.trigger('change.select2');
            toggleModeFields();
        }

        $('#from_account_id,#to_account_id,#track_account_id').on('change', function() {
            if (this.id === 'from_account_id' || this.id === 'to_account_id') {
                syncTrackAccountOptions();
            }

            toggleModeFields();
            renderBalanceSummary();
            autoGenerateHawala();
        });

        // ===============================
        // FORM SUBMIT
        // ===============================

        $('#remittanceForm').submit(function(e) {
            e.preventDefault();

            let form = $(this);
            form.find('input').each(function() {
                let name = $(this).attr('name');

                if (!name) return;

                if (name.startsWith('amount')) $(this).attr('name', 'amount');
                if (name.startsWith('partner_amount')) $(this).attr('name', 'partner_amount');
                if (name.startsWith('actual_amount')) $(this).attr('name', 'actual_amount');
            });

            // 🔥 FIX: restore correct names
            $('#amount').attr('name', 'amount');
            $('input[name^="partner_amount"]').attr('name', 'partner_amount');
            $('input[name^="actual_amount"]').attr('name', 'actual_amount');

            $('input[name^="commission_kabul_amount"]').attr('name', 'commission_kabul_amount');
            $('input[name^="commission_partner_amount"]').attr('name', 'commission_partner_amount');

            // normalize first
            form.find('.numeric-input').each(function() {
                this.value = convertToEnglishNumbers(this.value);
            });

            let raw = $('#amount').val();
            let amount = parseFormattedNumber(raw);

            console.log('RAW:', raw, 'PARSED:', amount);

            if (!raw || isNaN(amount) || amount <= 0) {
                remittancesSwal.fire({
                    icon: 'warning',
                    title: '{{ __('ui.blade_amount_required') }}',
                    text: '{{ __('ui.blade_please_enter_a_valid_amount') }}'
                });
                $('#amount').focus();
                return;
            }


            $.post("{{ route('admin.remittances.store') }}", form.serialize())
                .done(function(res) {

                    let msg = res?.message || @json(__('remittances.saved'));

                    remittancesSwal.fire({
                        icon: 'success',
                        title: msg,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    setTimeout(() => {
                        form[0].reset();
                        $('.select2').val(null).trigger('change');
                        syncPartnerCurrency();
                    }, 1200);

                })
                .fail(function(xhr) {

                    console.log(xhr.responseJSON);

                    let msg = @json(__('remittances.validation_failed'));

                    if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                    if (xhr.responseJSON?.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }

                    remittancesSwal.fire({
                        icon: 'error',
                        title: '{{ __('ui.blade_error') }}',
                        html: msg
                    });
                });
        });

        // ===============================
        // AUTO HAWALA NUMBER
        // ===============================
        $('input[name="hawala_number"]').on('input', function() {
            hawalaManuallyChanged = true;
        });

        $('input[name="track_hawala_number"]').on('input', function() {
            trackHawalaManuallyChanged = true;
        });

        function fetchNextHawala(accountId, column, callback) {
            if (!accountId) return;

            let type = $('#type').val();

            $.get("{{ url('/admin/remittances/next-hawala') }}", {
                account_id: accountId,
                column: column,
                type: type
            }, function(res) {
                callback(res.number);
            });
        }

        function autoGenerateHawala() {

            let type = $('#type').val();
            let mode = $('input[name="remittance_mode"]:checked').val();

            let fromId = $('#from_account_id').val();
            let toId = $('#to_account_id').val();
            let trackId = $('#track_account_id').val();

            let mainAccountId = (type === 'incoming') ? fromId : toId;

            if (!hawalaManuallyChanged && mainAccountId) {
                fetchNextHawala(mainAccountId, 'hawala_number', function(num) {
                    $('input[name="hawala_number"]').val(num);
                });
            }

            let trackSubCategoryId = Number($('#track_account_id option:selected').data('sub-category-id'));

            if (mode === 'track' && trackId && trackSubCategoryId !== 1 && !trackHawalaManuallyChanged) {
                fetchNextHawala(trackId, 'track_hawala_number', function(num) {
                    $('input[name="track_hawala_number"]').val(num);
                });
            }
        }

        // ===============================
        // INIT
        // ===============================
        syncTrackAccountOptions();
        renderBalanceSummary();
        autoGenerateHawala();
        $('#type').trigger('change');
    </script>
@endsection
