@extends('layouts.admin.base')

@section('title', __('exchange_rates.title'))

@section('css')
<style>
    .rates-page{--rates-ink:#1d304c;--rates-muted:#64758c;--rates-border:#e4eaf3;max-width:1720px;margin-inline:auto;padding:12px 0 25px;color:var(--rates-ink)}
    .rates-hero{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:19px 23px;margin-bottom:16px;background:linear-gradient(112deg,#172946,#2a416f 62%,#3658a2);color:#fff;border-radius:17px;box-shadow:0 11px 28px rgba(30,49,87,.13);position:relative;overflow:hidden}
    .rates-hero:after{content:"";position:absolute;inset-inline-end:-65px;top:-110px;border:26px solid rgba(255,255,255,.05);width:190px;height:190px;border-radius:50%;pointer-events:none}
    .rates-hero>div,.rates-hero-icon{position:relative;z-index:1}
    .rates-eyebrow{color:#c6d5fa;font-size:.69rem;font-weight:800;letter-spacing:.05em;display:flex;align-items:center;gap:7px}
    .rates-hero h2{font-size:1.43rem;font-weight:800;color:#fff;letter-spacing:-.02em;margin:4px 0}
    .rates-hero p{font-size:.76rem;color:#d3def4;margin:0}
    .rates-hero-icon{width:52px;height:52px;flex:0 0 52px;display:grid;place-items:center;background:rgba(255,255,255,.11);border:1px solid rgba(255,255,255,.17);border-radius:14px;font-size:1.45rem}
    .rates-page .rates-card{border:1px solid var(--rates-border)!important;border-radius:15px!important;box-shadow:0 4px 17px rgba(28,46,83,.045)!important;background:#fff;overflow:hidden}
    .rates-page .rates-card-header{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:15px 17px;background:#f9fbfe;border-bottom:1px solid #e9eef6}
    .rates-page .rates-card-header h5{font-size:.91rem;font-weight:800;color:#263d60;margin:0;display:flex;align-items:center;gap:9px}
    .rates-page .rates-card-header h5>i{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:#eaf0ff;color:#4368b0;font-size:1rem}
    .rates-page .rates-form-body{padding:18px}
    .rates-page .form-label{font-size:.72rem;color:#536983;font-weight:750;margin-bottom:7px}
    .rates-page .form-control,.rates-page .form-select{height:42px;border:1px solid #dce5ef;border-radius:9px;background:#fff;color:#253c5d;font-size:.8rem;box-shadow:none}
    .rates-page .form-control:focus,.rates-page .form-select:focus{border-color:#89a6da;box-shadow:0 0 0 3px rgba(72,111,186,.12)}
    .rates-page .rate-input{font-size:1.12rem;font-weight:800;font-variant-numeric:tabular-nums;letter-spacing:-.015em}
    .rates-page #ratePreview{display:flex;align-items:center;justify-content:center;gap:5px;min-height:52px;margin-bottom:15px;background:#eff4ff;border:1px solid #dfe9fd;border-radius:10px;color:#31568e;font-size:.89rem;font-weight:750;font-variant-numeric:tabular-nums}
    .rates-page #ratePreview strong{color:#174a96;font-size:1.06rem}
    .rates-page #saveRateBtn{min-height:43px;border-radius:10px;background:#3158a6;border-color:#3158a6;font-size:.8rem;font-weight:800;box-shadow:0 5px 11px rgba(38,77,154,.13)}
    .rates-page #saveRateBtn:hover{background:#244885;border-color:#244885}
    .rates-page #ratesRefreshBtn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:1px solid #d5e1f0;border-radius:9px;color:#355d9c;background:#fff;font-size:.74rem;font-weight:800;padding:8px 11px;min-height:36px}
    .rates-page #ratesRefreshBtn:hover{background:#edf3ff}
    .rates-page .rates-table-wrap{overflow-x:auto}
    .rates-page .rates-table{min-width:540px;border-collapse:separate;border-spacing:0}
    .rates-page .rates-table thead th{background:#f3f6fb;padding:12px 15px;font-size:.7rem;font-weight:800;letter-spacing:.03em;color:#5e708b;border-bottom:1px solid #e6edf5;white-space:nowrap}
    .rates-page .rates-table tbody td{padding:13px 15px;font-size:.77rem;color:#415674;border-bottom:1px solid #edf1f6;vertical-align:middle}
    .rates-page .rates-table tbody tr:hover td{background:#fafcff}
    .rates-pair{display:inline-flex;align-items:center;gap:7px;font-weight:800;color:#2a456f;white-space:nowrap}
    .rates-pair i{color:#7e95b8}
    .rates-rate-value{font-weight:800;color:#254778;font-variant-numeric:tabular-nums;white-space:nowrap}
    .rates-page .deleteBtn{width:33px;height:33px;border-radius:9px;border:1px solid #f2d9df;background:#fff1f3;color:#c64e62;display:inline-grid;place-items:center;padding:0}
    .rates-page .deleteBtn:hover{background:#ffe2e8}
    .rates-empty{padding:37px 12px!important;color:#8493a9!important;text-align:center}
    .rates-empty i{display:block;font-size:1.4rem;margin-bottom:8px}
    .rates-page :is(button,input,select):focus-visible{outline:3px solid #9ab5e5;outline-offset:2px}
    @media(max-width:991px){.rates-page .rates-card{margin-bottom:0}}
    @media(max-width:767px){.rates-hero{padding:16px}.rates-hero h2{font-size:1.2rem}.rates-hero p{font-size:.7rem}.rates-hero-icon{width:42px;height:42px;flex-basis:42px}.rates-page .rates-form-body{padding:14px}.rates-page .rates-card-header{padding:13px}.rates-page #ratesRefreshBtn span{display:none}}
</style>
@endsection

@section('content')

    <div class="container-fluid rates-page">
        <header class="rates-hero">
            <div><span class="rates-eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i>{{ __('exchange_rates.title') }}</span>
                <h2>{{ __('exchange_rates.title') }}</h2>
                <p>{{ __('ui.base_currency') }} · {{ __('ui.target_currency') }} · {{ __('ui.exchange_rate') }}</p>
            </div>
            <span class="rates-hero-icon" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
        </header>

        @if ($currencies->count() < 2)
            <div class="alert alert-danger rounded-4 shadow-sm">
                <strong>{{ __('ui.blade_at_least_two_currencies_are_required') }}</strong>
                {{ __('exchange_rates.create_currencies') }}
            </div>
        @else
            <div class="row g-3">

                {{-- ===================== FORM SECTION ===================== --}}
                <div class="col-lg-4">
                    <div class="card rates-card h-100">
                        <div class="rates-card-header">
                            <h5 class="card-title fw-bold text-dark mb-0">
                                <i class="bi bi-plus-circle text-primary me-2"></i>
                                {{ __('ui.new_exchange_rate') }}
                            </h5>
                        </div>

                        <div class="card-body rates-form-body">
                            <form id="rateForm">
                                @csrf

                                <div class="mb-4">
                                    <label class="form-label">{{ __('ui.effective_date') }}</label>
                                    <input type="date" name="date" class="form-control bg-light" value="{{ date('Y-m-d') }}" required>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label">{{ __('ui.base_currency') }}</label>
                                        <select name="from_currency_id" id="fromCurrency" class="form-select" required>
                                            <option value="" disabled selected>
                                                {{ __('exchange_rates.select') }}
                                            </option>

                                            @foreach ($currencies as $c)
                                                <option value="{{ $c->id }}" data-code="{{ $c->code }}">
                                                    {{ $c->code }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">{{ __('ui.target_currency') }}</label>
                                        <select name="to_currency_id" id="toCurrency" class="form-select" required>
                                            <option value="" disabled selected>
                                                {{ __('exchange_rates.select') }}
                                            </option>

                                            @foreach ($currencies as $c)
                                                <option value="{{ $c->id }}" data-code="{{ $c->code }}">
                                                    {{ $c->code }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">{{ __('ui.exchange_rate') }}</label>

                                    
                                    <input type="text" id="rateInput" name="rate" class="form-control exchange-rate-input rate-input" inputmode="decimal" placeholder="0.0000"
                                        autocomplete="off" required>
                                </div>

                                <div class="alert alert-info small text-center" id="ratePreview">
                                    1 --- = ---
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-bold py-2" id="saveRateBtn">
                                    {{ __('ui.publish_rates') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ===================== LIST SECTION ===================== --}}
                <div class="col-lg-8">
                    <div class="card rates-card h-100">
                        <div class="rates-card-header">
                            <h5 class="card-title fw-bold text-dark mb-0">
                                <i class="bi bi-graph-up-arrow text-success me-2"></i>
                                {{ __('ui.recent_conversions') }}
                            </h5>

                            <button type="button" id="ratesRefreshBtn" class="btn btn-sm" onclick="loadRates()">
                                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                                <span>{{ __('exchange_rates.refresh') }}</span>
                            </button>
                        </div>

                        <div class="table-responsive rates-table-wrap">
                            <table class="table-hover mb-0 table align-middle rates-table">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">{{ __('ui.date') }}</th>
                                        <th>{{ __('ui.pair') }}</th>
                                        <th>{{ __('ui.rate') }}</th>
                                        <th class="pe-4 text-end">{{ __('ui.action') }}</th>
                                    </tr>
                                </thead>

                                <tbody id="rateTableBody">
                                    <tr>
                                        <td colspan="4" class="text-muted py-4 text-center">
                                            {{ __('exchange_rates.loading') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>

        @endif

    </div>

@endsection

@section('js')

    @if ($currencies->count() >= 2)
        <script>
            const ratesSwal = Swal.mixin({confirmButtonText: @json(__('exchange_rates.ok')), cancelButtonText: @json(__('ui.cancel'))});
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const rateForm = document.getElementById('rateForm');
            const rateInput = document.getElementById('rateInput');
            const fromCurrency = document.getElementById('fromCurrency');
            const toCurrency = document.getElementById('toCurrency');
            const preview = document.getElementById('ratePreview');
            const tableBody = document.getElementById('rateTableBody');
            const saveRateBtn = document.getElementById('saveRateBtn');

            const escapeRateText = value => String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            })[char]);

            // ================= NORMALIZE DIGITS =================
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

            // ================= FORMAT CLEAN =================
            function cleanNumber(val) {
                if (val === null || val === undefined || val === '') {
                    return '0';
                }

                return val.toString()
                    .replace(/,/g, '')
                    .replace(/(\.\d*?[1-9])0+$/, '$1')
                    .replace(/\.0+$/, '');
            }

            // ================= SANITIZE RATE =================
            function sanitizeRate(value) {
                return window.formatNumericInput(value);
            }

            function selectedCode(selectElement) {
                const selectedOption = selectElement.options[selectElement.selectedIndex];

                if (!selectedOption || !selectedOption.dataset.code) {
                    return '---';
                }

                return selectedOption.dataset.code;
            }

            // ================= PREVIEW =================
            function updatePreview() {
                const rate = rateInput.value;
                const fromCode = selectedCode(fromCurrency);
                const toCode = selectedCode(toCurrency);

                if (rate && fromCode !== '---' && toCode !== '---') {
                    preview.innerHTML = `1 ${escapeRateText(fromCode)} = <strong>${escapeRateText(cleanNumber(rate))}</strong> ${escapeRateText(toCode)}`;
                } else {
                    preview.textContent = `1 ${fromCode} = ---`;
                }
            }

            function validateDifferentCurrencies(showAlert = false) {
                if (!fromCurrency.value || !toCurrency.value) {
                    return true;
                }

                if (fromCurrency.value === toCurrency.value) {
                    if (showAlert) {
                        ratesSwal.fire({
                            icon: 'error',
                            title: '{{ __('ui.blade_error') }}',
                            text: '{{ __('ui.blade_base_currency_and_target_currency_cannot_be_the_same') }}'
                        });
                    }

                    return false;
                }

                return true;
            }

            // ================= RATE INPUT =================
            rateInput.addEventListener('input', function() {
                this.value = sanitizeRate(this.value);
                updatePreview();
            });

            rateInput.addEventListener('paste', function(e) {
                e.preventDefault();

                let pasted = (e.clipboardData || window.clipboardData).getData('text');
                this.value = sanitizeRate(pasted);

                updatePreview();
            });

            fromCurrency.addEventListener('change', function() {
                validateDifferentCurrencies(false);
                updatePreview();
            });

            toCurrency.addEventListener('change', function() {
                validateDifferentCurrencies(false);
                updatePreview();
            });

            // ================= RENDER ROW =================
            function renderRow(r) {
                let fromCode = r.from_currency ? r.from_currency.code : '---';
                let toCode = r.to_currency ? r.to_currency.code : '---';
                let rate = cleanNumber(r.rate);
                let date = r.date ? String(r.date).slice(0, 10) : '---';

                return `
            <tr id="row-${r.id}">
                <td class="ps-4">${escapeRateText(date)}</td>
                <td><span class="rates-pair">${escapeRateText(fromCode)} <i class="bi bi-arrow-left-right" aria-hidden="true"></i> ${escapeRateText(toCode)}</span></td>
                <td><span class="rates-rate-value" dir="ltr">1 ${escapeRateText(fromCode)} = ${escapeRateText(rate)} ${escapeRateText(toCode)}</span></td>
                <td class="text-end pe-4">
                    <button type="button" class="btn btn-sm deleteBtn" title="{{ __('exchange_rates.delete') }}" aria-label="{{ __('exchange_rates.delete') }}" data-id="${r.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
            }

            // ================= LOAD DATA =================
            function loadRates() {
                tableBody.innerHTML = `
            <tr>
                <td colspan="4" class="rates-empty">
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>{{ __('exchange_rates.loading') }}
                </td>
            </tr>
        `;

                fetch("{{ route('admin.exchange-rates.list') }}", {
                        method: "GET",
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(async response => {
                        let data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || @json(__('exchange_rates.load_failed')));
                        }

                        return data;
                    })
                    .then(response => {
                        tableBody.innerHTML = '';

                        if (!response.status || !response.data || response.data.length === 0) {
                            tableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="rates-empty">
                            <i class="bi bi-inbox" aria-hidden="true"></i>{{ __('exchange_rates.empty') }}
                        </td>
                    </tr>
                `;
                            return;
                        }

                        response.data.forEach(function(rate) {
                            tableBody.insertAdjacentHTML('beforeend', renderRow(rate));
                        });
                    })
                    .catch(error => {
                        tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="rates-empty text-danger">
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>${escapeRateText(error.message || @json(__('exchange_rates.load_failed')))}
                    </td>
                </tr>
            `;
                    });
            }

            // ================= LOAD ON PAGE LOAD =================
            loadRates();

            // ================= AJAX STORE =================
            rateForm.addEventListener('submit', function(e) {
                e.preventDefault();

                let form = this;

                rateInput.value = sanitizeRate(rateInput.value);

                if (!fromCurrency.value) {
                    ratesSwal.fire({
                        icon: 'error',
                        title: '{{ __('ui.blade_error') }}',
                        text: '{{ __('ui.blade_please_select_base_currency') }}'
                    });
                    return;
                }

                if (!toCurrency.value) {
                    ratesSwal.fire({
                        icon: 'error',
                        title: '{{ __('ui.blade_error') }}',
                        text: '{{ __('ui.blade_please_select_target_currency') }}'
                    });
                    return;
                }

                if (!validateDifferentCurrencies(true)) {
                    return;
                }

                if (!rateInput.value) {
                    ratesSwal.fire({
                        icon: 'error',
                        title: '{{ __('ui.blade_error') }}',
                        text: '{{ __('ui.blade_the_rate_field_is_required') }}'
                    });
                    return;
                }

                let formData = new FormData(form);

                /*
                    Safety fix:
                    Because the base layout may modify numeric input names globally,
                    we explicitly set the correct field here.
                */
                formData.set('rate', rateInput.value);
                formData.set('from_currency_id', fromCurrency.value);
                formData.set('to_currency_id', toCurrency.value);

                saveRateBtn.disabled = true;
                saveRateBtn.innerHTML = `
            <span class="spinner-border spinner-border-sm me-1"></span>
            {{ __('exchange_rates.saving') }}
        `;

                fetch("{{ route('admin.exchange-rates.store') }}", {
                        method: "POST",
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(async response => {
                        let data = await response.json();

                        if (!response.ok) {
                            let message = data.message || @json(__('exchange_rates.validation_error'));

                            if (data.errors) {
                                message = Object.values(data.errors).flat().join('<br>');
                            }

                            ratesSwal.fire({
                                icon: 'error',
                                title: '{{ __('ui.blade_error') }}',
                                text: message
                            });

                            return null;
                        }

                        return data;
                    })
                    .then(response => {
                        if (!response) return;

                        if (response.status) {
                            loadRates();

                            form.reset();

                            const dateInput = form.querySelector('input[name="date"]');
                            if (dateInput) {
                                dateInput.value = "{{ date('Y-m-d') }}";
                            }

                            preview.innerHTML = '1 --- = ---';

                            ratesSwal.fire({
                                icon: 'success',
                                title: '{{ __('ui.blade_saved_2') }}',
                                text: response.message || @json(__('exchange_rates.saved')),
                                timer: 1200,
                                showConfirmButton: false
                            });
                        } else {
                            ratesSwal.fire({
                                icon: 'error',
                                title: '{{ __('ui.blade_error') }}',
                                text: response.message || @json(__('exchange_rates.something_wrong'))
                            });
                        }
                    })
                    .catch(error => {
                        ratesSwal.fire({
                            icon: 'error',
                            title: '{{ __('ui.blade_error') }}',
                            text: error.message || @json(__('exchange_rates.something_wrong'))
                        });
                    })
                    .finally(() => {
                        saveRateBtn.disabled = false;
                        saveRateBtn.innerHTML = `{{ __('ui.publish_rates') }}`;
                    });
            });

            // ================= AJAX DELETE =================
            document.addEventListener('click', function(e) {
                if (e.target.closest('.deleteBtn')) {
                    let btn = e.target.closest('.deleteBtn');
                    let id = btn.dataset.id;

                    ratesSwal.fire({
                        title: '{{ __('ui.blade_delete_this_rate') }}',
                        text: '{{ __('ui.blade_this_cannot_be_undone') }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: '{{ __('ui.blade_yes_delete_it') }}'
                    }).then((result) => {
                        if (result.isConfirmed) {

                            fetch(`/admin/exchange-rates/${id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': csrf,
                                        'Accept': 'application/json'
                                    }
                                })
                                .then(async response => {
                                    let data = await response.json();

                                    if (!response.ok) {
                                        throw new Error(data.message || @json(__('exchange_rates.delete_failed')));
                                    }

                                    return data;
                                })
                                .then(response => {
                                    if (response.status) {
                                        const row = document.getElementById('row-' + id);

                                        if (row) {
                                            row.remove();
                                        }

                                        ratesSwal.fire({
                                            icon: 'success',
                                            title: '{{ __('ui.blade_deleted_2') }}',
                                            timer: 1000,
                                            showConfirmButton: false
                                        });

                                        if (tableBody.children.length === 0) {
                                            tableBody.innerHTML = `
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            {{ __('exchange_rates.empty') }}
                                        </td>
                                    </tr>
                                `;
                                        }

                                    } else {
                                        ratesSwal.fire({
                                            icon: 'error',
                                            title: '{{ __('ui.blade_error') }}',
                                            text: response.message || @json(__('exchange_rates.delete_failed'))
                                        });
                                    }
                                })
                                .catch(error => {
                                    ratesSwal.fire({
                                        icon: 'error',
                                        title: '{{ __('ui.blade_error') }}',
                                        text: error.message || @json(__('exchange_rates.something_wrong'))
                                    });
                                });
                        }
                    });
                }
            });
        </script>
    @endif

@endsection
