<div id="dashboardContent" class="dashboard-content">
    <script type="application/json" id="dashboardChartData">{!! json_encode([
        'dailyFlow' => $dailyFlow->values(),
        'profitLoss' => $profitLossByCurrency->values(),
        'balanceLabels' => $cashBalances->pluck('currency')->merge($customerBalances->pluck('currency'))->merge($sarafBalances->pluck('currency'))->unique()->values(),
        'cashBalances' => $cashBalances->pluck('balance', 'currency'),
        'customerBalances' => $customerBalances->pluck('balance', 'currency'),
        'sarafBalances' => $sarafBalances->pluck('balance', 'currency'),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <div class="dashboard-heading dashboard-overview-heading">
        <div><span>{{ __('dashboard.snapshot') }}</span><h4>{{ __('dashboard.overview') }}</h4></div>
        <small><i class="bi bi-calendar3 me-1" aria-hidden="true"></i>{{ $selectedDate }}</small>
    </div>
    <div class="metric-grid">
        @php
            $cards = [
                [__('dashboard.customers'), $totals['customers'], 'bi-people', 'blue', url('admin/accounts')],
                [__('dashboard.cash_accounts'), $totals['cash_accounts'], 'bi-wallet2', 'teal', url('admin/accounts')],
                [__('dashboard.sarafs'), $totals['sarafs'], 'bi-bank', 'violet', url('admin/sarafs')],
                [__('dashboard.remittances'), $totals['remittances'], 'bi-send', 'orange', url('admin/remittances')],
                [__('dashboard.exchanges'), $totals['exchanges'], 'bi-currency-exchange', 'green', url('admin/exchanges')],
                [__('dashboard.users'), $totals['users'], 'bi-person-badge', 'pink', url('admin/users')],
                [__('dashboard.active_accounts'), $totals['accounts'], 'bi-journal-check', 'navy', url('admin/accounts')],
                [__('dashboard.period_entries'), $totals['transactions'], 'bi-arrow-left-right', 'amber', url('admin/transactions')],
            ];
        @endphp
        @foreach($cards as [$label, $value, $icon, $tone, $url])
            <a href="{{ $url }}" class="metric-card metric-{{ $tone }}" aria-label="{{ $label }}: {{ format_amount($value, 0) }}">
                <span class="metric-top">
                    <span class="metric-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                    <span class="metric-arrow"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                </span>
                <span class="metric-copy">
                    <small>{{ $label }}</small>
                    <strong dir="ltr">{{ format_amount($value, 0) }}</strong>
                </span>
            </a>
        @endforeach
    </div>

    <div class="dashboard-heading">
        <div><span>{{ __('dashboard.current_position') }}</span><h4>{{ __('dashboard.balances_by_currency') }}</h4></div>
        <small>{{ __('dashboard.live_balances') }} · {{ $selectedCurrency?->code ?? __('dashboard.all_currencies') }}</small>
    </div>

    @php
        $balancePanels = [
            [__('dashboard.cash_accounts'), __('dashboard.cash_available'), $cashBalances, 'bi-safe2', 'blue'],
            [__('dashboard.customer_balances'), __('dashboard.customer_position'), $customerBalances, 'bi-people', 'violet'],
            [__('dashboard.saraf_balances'), __('dashboard.saraf_position'), $sarafBalances, 'bi-bank2', 'teal'],
        ];
    @endphp
    <div class="balance-grid">
        @foreach($balancePanels as [$title, $subtitle, $rows, $icon, $tone])
            <section class="panel balance-panel tone-{{ $tone }}">
                <div class="panel-head"><div class="panel-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div><div class="panel-head-copy"><h5>{{ $title }}</h5><p>{{ $subtitle }}</p></div><span class="panel-head-mark"><i class="bi bi-three-dots" aria-hidden="true"></i></span></div>
                <div class="currency-list">
                    @forelse($rows as $row)
                        <div class="currency-row">
                            <span class="currency-badge">{{ $row->currency }}</span>
                            <div class="currency-amount"><strong dir="ltr" class="{{ $row->balance < 0 ? 'amount-negative' : 'amount-positive' }}">{{ format_amount(abs($row->balance), 2) }}</strong><small>{{ $row->balance < 0 ? __('dashboard.debit') : __('dashboard.credit') }} · {{ $row->account_count }} {{ __('dashboard.accounts') }}</small></div>
                        </div>
                    @empty
                        <div class="empty-state"><i class="bi bi-inbox"></i><span>{{ __('dashboard.no_balance') }}</span></div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <div class="position-grid">
        <section class="panel position-card position-credit">
            <div class="position-title"><span><i class="bi bi-arrow-down-left"></i></span><div><small>{{ __('dashboard.total_position') }}</small><h5>{{ __('dashboard.creditors') }}</h5></div></div>
            @forelse($creditorBalances as $row)<div class="position-row"><span>{{ $row->currency }} <small>{{ $row->account_count }} {{ __('dashboard.accounts') }}</small></span><strong>{{ format_amount($row->balance, 2) }}</strong></div>@empty<div class="empty-inline">{{ __('dashboard.no_creditors') }}</div>@endforelse
        </section>
        <section class="panel position-card position-debit">
            <div class="position-title"><span><i class="bi bi-arrow-up-right"></i></span><div><small>{{ __('dashboard.total_position') }}</small><h5>{{ __('dashboard.debtors') }}</h5></div></div>
            @forelse($debtorBalances as $row)<div class="position-row"><span>{{ $row->currency }} <small>{{ $row->account_count }} {{ __('dashboard.accounts') }}</small></span><strong>{{ format_amount($row->balance, 2) }}</strong></div>@empty<div class="empty-inline">{{ __('dashboard.no_debtors') }}</div>@endforelse
        </section>
    </div>

    <div class="dashboard-heading"><div><span>{{ __('dashboard.performance') }}</span><h4>{{ __('dashboard.finance_summary') }}</h4></div><small>{{ $selectedDate }}</small></div>
    <div class="finance-grid">
        <section class="panel finance-card">
            <div class="finance-head"><span class="finance-icon profit"><i class="bi bi-graph-up-arrow"></i></span><div><small>{{ __('dashboard.current_ledger') }}</small><h5>{{ __('dashboard.profit_loss') }}</h5></div></div>
            @forelse($pnlBalances as $row)<div class="finance-line"><span>{{ $row->currency }}</span><strong class="{{ $row->balance < 0 ? 'amount-negative' : 'amount-positive' }}">{{ format_amount($row->balance, 2) }}</strong></div>@empty<div class="empty-inline">{{ __('dashboard.no_profit_loss') }}</div>@endforelse
        </section>
        <section class="panel finance-card">
            <div class="finance-head"><span class="finance-icon expense"><i class="bi bi-receipt"></i></span><div><small>{{ $selectedDate }}</small><h5>{{ __('dashboard.approved_expenses') }}</h5></div></div>
            @forelse($expensesByCurrency as $row)<div class="finance-line"><span>{{ $row->currency }}</span><strong class="amount-negative">{{ format_amount($row->total, 2) }}</strong></div>@empty<div class="empty-inline">{{ __('dashboard.no_expenses') }}</div>@endforelse
        </section>
        <section class="panel finance-card closing-card">
            <div class="finance-head"><span class="finance-icon closing"><i class="bi bi-calendar2-check"></i></span><div><small>{{ $latestClosing?->closing_date?->format('M d, Y') ?? __('dashboard.no_closing') }}</small><h5>{{ __('dashboard.last_closing') }}</h5></div></div>
            <div class="closing-value" dir="ltr">{{ format_amount($latestClosing?->final_balance ?? 0, 2) }}</div>
            <div class="closing-breakdown"><span>{{ __('dashboard.cash') }} {{ format_amount($latestClosing?->cash_balance ?? 0, 2) }}</span><span>{{ __('dashboard.pnl') }} {{ format_amount(($latestClosing?->profit_balance ?? 0) - ($latestClosing?->loss_balance ?? 0), 2) }}</span></div>
        </section>
    </div>

    <div class="chart-grid">
        <section class="panel chart-panel chart-wide"><div class="chart-head"><div><small>{{ __('dashboard.transaction_activity') }}</small><h5>{{ __('dashboard.credit_debit_flow') }}</h5></div><span>{{ $selectedDate }}</span></div><div class="chart-wrap"><canvas id="dailyFlowChart"></canvas></div></section>
        <section class="panel chart-panel"><div class="chart-head"><div><small>{{ __('dashboard.period_performance') }}</small><h5>{{ __('dashboard.net_profit_currency') }}</h5></div></div><div class="chart-wrap"><canvas id="profitLossChart"></canvas></div></section>
        <section class="panel chart-panel"><div class="chart-head"><div><small>{{ __('dashboard.snapshot') }}</small><h5>{{ __('dashboard.account_balances') }}</h5></div></div><div class="chart-wrap"><canvas id="balanceChart"></canvas></div></section>
    </div>

    <section class="panel recent-panel">
        <div class="chart-head"><div><small>{{ __('dashboard.recent_activity') }}</small><h5>{{ __('dashboard.latest_transactions') }}</h5></div><a href="{{ url('admin/transactions') }}">{{ __('dashboard.view_all') }} <i class="bi bi-arrow-right"></i></a></div>
        <div class="table-responsive"><table class="activity-table"><thead><tr><th>{{ __('dashboard.account') }}</th><th>{{ __('dashboard.reference') }}</th><th>{{ __('dashboard.date') }}</th><th>{{ __('dashboard.type') }}</th><th class="text-end">{{ __('dashboard.amount') }}</th></tr></thead><tbody>
            @forelse($latestTransactions as $transaction)<tr><td><strong>{{ $transaction->account?->name ?? '—' }}</strong><small>{{ $transaction->account?->code }}</small></td><td>{{ $transaction->reference ?: '—' }}</td><td>{{ $transaction->created_at?->format('M d, Y · H:i') }}</td><td><span class="type-pill {{ $transaction->transaction_type }}">{{ __('dashboard.' . $transaction->transaction_type) }}</span></td><td class="text-end"><strong>{{ format_amount($transaction->amount, 2) }}</strong> <small>{{ $transaction->currency?->code }}</small></td></tr>
            @empty<tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i><span>{{ __('dashboard.no_transactions') }}</span></div></td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
