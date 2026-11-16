@extends('layouts.admin.base')

@section('title', __('reports.debtors_title'))

@section('css')
<link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
<link rel="stylesheet" href="{{ asset('assets/offline/febd1387d851569a.css') }}" />

<style>
    .totals-card {
        border-radius: 16px;
        padding: 20px;
        background: linear-gradient(to right, #1e293b, #334155);
        color: #fff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        min-width: 240px;
        max-width: 280px;
        flex: 1;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.3s ease;
    }
    .totals-card:hover {
        transform: translateY(-5px);
    }
    .totals-card h6 {
        font-size: 1rem;
        margin-bottom: 0.75rem;
        color: #38bdf8;
    }
    .totals-card div {
        font-size: 0.9rem;
    }
    .totals-chart {
        margin-top: 12px;
        height: 40px;
    }
</style>
@endsection

@section('content')
<div class="card rounded-4 border-0 shadow">
    <div class="card-header">
        <h4 class="card-title mb-3">{{ __('reports.debtors_title') }}</h4>

        <div class="row g-3 mb-4">

            <div class="col-md-4">
                <label class="form-label">👤 {{ __('ui.sub_category') }}</label>
                <select id="filter_sub" class="form-select">
                    <option value="">{{ __('ui.all_sub_categories') }}</option>
                    @foreach($subCategories as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">💱 {{ __('ui.currency') }}</label>
                <select id="filter_currency" class="form-select">
                    <option value="">{{ __('ui.all_currencies') }}</option>
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}">{{ $currency->code }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <a href="#" id="exportPdfBtn" class="btn btn-sm btn-danger w-100" style="height: 47px;">
                    {{ __('ui.export_pdf') }}
                </a>
            </div>

        </div>
    </div>

    <div class="card-body">

        <div class="d-flex flex-wrap gap-3 mb-4" id="totalsCards"></div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle" id="debtorsTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('ui.account') }}</th>
                        <th>{{ __('ui.balances') }}</th>
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
<script src="{{ asset('assets/offline/459cd6d7de2fb24b.js') }}"></script>
<script>
    function renderMiniChart(canvasId, value) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Debt', 'Remaining'],
                datasets: [{
                    data: [Math.abs(value), 0],
                    backgroundColor: ['#38bdf8', '#e2e8f0'],
                    borderWidth: 0,
                }]
            },
            options: {
                cutout: '75%',
                plugins: { legend: { display: false } },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    let table = $('#debtorsTable').DataTable({
                language: @include('admin.reports.partials.table-language'),
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: '{{ route('admin.reports.debtors.data') }}',
            data: function(d) {
                d.sub_category_id = $('#filter_sub').val();
                d.currency_id = $('#filter_currency').val();
            },
            dataSrc: function(json) {
                const $totalsCards = $('#totalsCards');
                $totalsCards.empty();
                if (json.totals_by_currency) {
                    let index = 0;
                    for (let currency in json.totals_by_currency) {
                        const canvasId = 'miniChart' + index;
                        const row = json.totals_by_currency[currency];
                        $totalsCards.append(`
                            <div class="totals-card">
                                <h6>💱 ${currency}</h6>
                                <div><strong>{{ __('ui.blade_total_debt') }}</strong> ${row.balance}</div>
                                <div><strong>{{ __('ui.accounts') }}</strong> ${row.count}</div>
                                <div class="totals-chart">
                                    <canvas id="${canvasId}"></canvas>
                                </div>
                            </div>
                        `);
                        setTimeout(() => renderMiniChart(canvasId, parseFloat(row.balance.replace(/,/g, ''))), 100);
                        index++;
                    }
                }
                return json.data;
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            {
                    data: null,
                    render: r => `
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-1 me-2">
                                    <i class="bi bi-person-circle text-primary" style="font-size: 0.875rem;"></i>
                                </div>
                                <div style="line-height: 1.2;">
                                    <a href="/admin/accounts/${r.account_id}" class="text-dark fw-bold text-decoration-none small">
                                        ${r.account_name}
                                    </a>
                                </div>
                            </div>`
                },
            { data: 'balances', name: 'balances', render: d => `<span style="color: red">${d}</span>` },
        ]
    });

    $('#filter_sub, #filter_currency').on('change', function() {
        table.ajax.reload();
    });

    $('#exportPdfBtn').on('click', function(e) {
        e.preventDefault();
        const sub = $('#filter_sub').val() || '';
        const currency = $('#filter_currency').val() || '';
        const url = `{{ route('admin.reports.debtors.export.pdf') }}?sub_category_id=${sub}&currency_id=${currency}`;
        window.open(url, '_blank');
    });
</script>
@endsection
