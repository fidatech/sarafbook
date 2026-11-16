@extends('layouts.admin.base')
@section('title', __('dashboard.title'))

@section('content')
<div class="db-page" id="dashboardPage">
    <x-page-header :title="__('dashboard.heading')" icon="gauge-high" :subtitle="__('dashboard.intro')">
        @can('create exchanges')
            <a class="btn btn-primary" href="{{ route('admin.exchanges.create') }}"><x-icon name="coins" /> {{ __('ui.new_exchange') }}</a>
        @endcan
        @can('create remittances')
            <a class="btn btn-soft-primary" href="{{ route('admin.remittances.createNew') }}"><x-icon name="paper-plane" /> {{ __('ui.new_remittance') }}</a>
        @endcan
        @can('view journal')
            <a class="btn btn-light-secondary" href="{{ url('admin/journal') }}"><x-icon name="book" /> {{ __('ui.journal') }}</a>
        @endcan
        @can('update settings')
            <button type="button" id="refreshSystemBalances" class="btn btn-light-secondary" title="{{ __('ui.dashboard_refresh.button') }}">
                <x-icon name="rotate" /> <span class="refresh-label">{{ __('ui.dashboard_refresh.button') }}</span>
            </button>
        @endcan
    </x-page-header>

    <form id="dashboardFilters" action="{{ route('admin.dashboard') }}" method="GET" class="db-filters card-soft" aria-label="{{ __('dashboard.period') }}">
        <div class="db-filter">
            <label class="form-label" for="dashboardCurrency">{{ __('dashboard.currency') }}</label>
            <select id="dashboardCurrency" class="form-select" name="currency_id">
                <option value="">{{ __('dashboard.all_currencies') }}</option>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}" @selected($selectedCurrencyId === $currency->id)>{{ $currency->code }} — {{ $currency->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="db-filter">
            <label class="form-label" for="periodPreset">{{ __('dashboard.period') }}</label>
            <select class="form-select" name="preset" id="periodPreset">
                @foreach (['today', 'yesterday', 'this_week', 'this_month', 'last_30_days', 'this_year'] as $preset)
                    <option value="{{ $preset }}" @selected($datePreset === $preset)>{{ __('dashboard.'.$preset) }}</option>
                @endforeach
                <option value="custom" @selected($datePreset === 'custom')>{{ __('dashboard.custom_range') }}</option>
            </select>
        </div>
        <div class="db-filter custom-date" @if ($datePreset !== 'custom') hidden @endif>
            <label class="form-label" for="dashboardFrom">{{ __('dashboard.from') }}</label>
            <input id="dashboardFrom" class="form-control" type="date" name="date_from" value="{{ $dateFrom }}">
        </div>
        <div class="db-filter custom-date" @if ($datePreset !== 'custom') hidden @endif>
            <label class="form-label" for="dashboardTo">{{ __('dashboard.to') }}</label>
            <input id="dashboardTo" class="form-control" type="date" name="date_to" value="{{ $dateTo }}">
        </div>
        <div class="db-filter db-filter-action">
            <button class="btn btn-primary w-100" type="submit"><x-icon name="filter" /> {{ __('dashboard.apply') }}</button>
        </div>
        <div class="db-filter-status" id="dashboardStatus" role="status" aria-live="polite" hidden><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> {{ __('dashboard.updating') }}</div>
    </form>

    @include('admin.dashboard-content')
</div>
@endsection

@section('js')
<script defer src="{{ asset_v('assets/offline/bd19dbbb831f8c42.js') }}"></script>
<script>
(function () {
    var charts = [];
    var text = {
        credit: @json(__('dashboard.credit')), debit: @json(__('dashboard.debit')), net: @json(__('dashboard.net_pnl')),
        cash: @json(__('dashboard.cash')), customers: @json(__('dashboard.customers')), sarafs: @json(__('dashboard.sarafs')),
        ok: @json(__('dashboard.ok')), invalid: @json(__('dashboard.invalid_dates')), select: @json(__('dashboard.select_dates')),
        failed: @json(__('dashboard.update_failed')), again: @json(__('dashboard.try_again')),
        working: @json(__('ui.dashboard_refresh.working')), refresh: @json(__('ui.dashboard_refresh.button')), refreshFailed: @json(__('ui.dashboard_refresh.failed'))
    };
    var css = function (name, fallback) { return (getComputedStyle(document.documentElement).getPropertyValue(name) || '').trim() || fallback; };
    var compact = function (value) {
        var n = Number(value), a = Math.abs(n);
        if (a >= 1e9) return (n / 1e9).toFixed(1).replace(/\.0$/, '') + 'B';
        if (a >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
        if (a >= 1e3) return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
        return String(n);
    };

    function toggleDates() {
        var custom = document.getElementById('periodPreset').value === 'custom';
        document.querySelectorAll('.custom-date').forEach(function (el) { el.hidden = !custom; });
    }

    function renderCharts() {
        charts.forEach(function (chart) { chart.destroy(); });
        charts = [];
        var node = document.getElementById('dashboardChartData');
        if (!node || typeof Chart === 'undefined') return;
        var data;
        try { data = JSON.parse(node.textContent); } catch (e) { return; }

        var muted = css('--muted', '#8a92a3'), border = css('--border', '#e9edf3'), primary = css('--primary', '#0c3b7a');
        var green = '#199e70', red = '#dc3545', violet = '#7c5cd6', gold = '#c9a227';
        Chart.defaults.color = muted;
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        var rtl = document.documentElement.dir === 'rtl';
        var common = {
            responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom', rtl: rtl, labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, padding: 16, font: { size: 11.5 } } }, tooltip: { rtl: rtl, padding: 10, callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + Number(c.parsed.y).toLocaleString('en-US', { maximumFractionDigits: 2 }); } } } },
            scales: { x: { reverse: rtl, grid: { display: false }, ticks: { font: { size: 10.5 }, maxRotation: 0, autoSkipPadding: 14 } }, y: { position: rtl ? 'right' : 'left', beginAtZero: true, border: { display: false }, grid: { color: border }, ticks: { font: { size: 10.5 }, callback: compact } } }
        };

        var flow = document.getElementById('dailyFlowChart');
        if (flow && data.dailyFlow.length) charts.push(new Chart(flow, { type: 'line', data: { labels: data.dailyFlow.map(function (x) { return String(x.date).slice(5); }), datasets: [
            { label: text.credit, data: data.dailyFlow.map(function (x) { return Number(x.credit); }), borderColor: green, backgroundColor: 'rgba(25,158,112,.10)', fill: true, tension: .35, borderWidth: 2, pointRadius: data.dailyFlow.length > 20 ? 0 : 3 },
            { label: text.debit, data: data.dailyFlow.map(function (x) { return Number(x.debit); }), borderColor: red, backgroundColor: 'rgba(220,53,69,.07)', fill: true, tension: .35, borderWidth: 2, pointRadius: data.dailyFlow.length > 20 ? 0 : 3 }
        ] }, options: common }));

        var pnl = document.getElementById('profitLossChart');
        if (pnl && data.profitLoss.length) charts.push(new Chart(pnl, { type: 'bar', data: { labels: data.profitLoss.map(function (x) { return x.currency; }), datasets: [
            { label: text.net, data: data.profitLoss.map(function (x) { return Number(x.net_profit); }), backgroundColor: data.profitLoss.map(function (x) { return Number(x.net_profit) >= 0 ? green : red; }), borderRadius: 6, maxBarThickness: 46 }
        ] }, options: Object.assign({}, common, { plugins: Object.assign({}, common.plugins, { legend: { display: false } }) }) }));

        var labels = data.balanceLabels || [], balance = document.getElementById('balanceChart');
        if (balance && labels.length) charts.push(new Chart(balance, { type: 'bar', data: { labels: labels, datasets: [
            { label: text.cash, data: labels.map(function (x) { return Number(data.cashBalances[x] || 0); }), backgroundColor: primary, borderRadius: 5, maxBarThickness: 26 },
            { label: text.customers, data: labels.map(function (x) { return Number(data.customerBalances[x] || 0); }), backgroundColor: violet, borderRadius: 5, maxBarThickness: 26 },
            { label: text.sarafs, data: labels.map(function (x) { return Number(data.sarafBalances[x] || 0); }), backgroundColor: gold, borderRadius: 5, maxBarThickness: 26 }
        ] }, options: common }));
    }

    function busy(on) {
        document.getElementById('dashboardStatus').hidden = !on;
        var content = document.getElementById('dashboardContent');
        if (content) content.classList.toggle('is-loading', on);
    }

    async function load(form) {
        var from = form.querySelector('[name=date_from]'), to = form.querySelector('[name=date_to]');
        if (form.preset.value === 'custom' && (!from.value || !to.value || from.value > to.value)) {
            Swal.fire({ confirmButtonText: text.ok, icon: 'warning', title: text.invalid, text: text.select });
            return;
        }
        busy(true);
        var url = form.action + '?' + new URLSearchParams(new FormData(form));
        try {
            var response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            if (!response.ok) throw new Error();
            var result = await response.json();
            document.getElementById('dashboardContent').outerHTML = result.html;
            history.replaceState({}, '', url);
            renderCharts();
        } catch (error) {
            Swal.fire({ confirmButtonText: text.ok, icon: 'error', title: text.failed, text: text.again });
        } finally {
            busy(false);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('dashboardFilters');
        toggleDates();
        renderCharts();
        // currency and period apply at once; a custom range waits for the button
        form.addEventListener('change', function (e) {
            if (e.target.id === 'periodPreset') { toggleDates(); if (e.target.value !== 'custom') load(form); }
            else if (e.target.id === 'dashboardCurrency') load(form);
        });
        form.addEventListener('submit', function (e) { e.preventDefault(); e.stopImmediatePropagation(); load(form); }, true);
        new MutationObserver(renderCharts).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });

        var button = document.getElementById('refreshSystemBalances');
        if (button) button.addEventListener('click', async function () {
            var label = button.querySelector('.refresh-label');
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            label.textContent = text.working;
            try {
                var response = await fetch(@json(route('admin.dashboard.refresh-balances')), { method: 'POST', headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' } });
                var result = await response.json();
                if (!response.ok) throw new Error(result.message);
                await Swal.fire({ icon: 'success', text: result.message, timer: 1500, showConfirmButton: false });
                window.location.reload();
            } catch (error) {
                Swal.fire({ confirmButtonText: text.ok, icon: 'error', text: error.message || text.refreshFailed });
                button.disabled = false;
                button.removeAttribute('aria-busy');
                label.textContent = text.refresh;
            }
        });
    });
})();
</script>
@endsection
