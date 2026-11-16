{{-- The part of the dashboard that is loaded again when the currency or the period changes. --}}
@php
    $pending = (int) ($remittanceStats->pending_count ?? 0);
    $mixed = ! $selectedCurrency;
    $entryCount = format_amount($totals['transactions'], 0);
    $exchangeCount = format_amount($exchangeStats->total_count ?? 0, 0);
    $exchangeNote = __('dashboard.purchase').' '.(int) ($exchangeStats->purchase_count ?? 0).' · '.__('dashboard.sale').' '.(int) ($exchangeStats->sell_count ?? 0);
    $remittanceCount = format_amount($remittanceStats->total_count ?? 0, 0);
    $remittanceNote = __('ui.outgoing').' '.(int) ($remittanceStats->outgoing_count ?? 0).' · '.__('ui.incoming').' '.(int) ($remittanceStats->incoming_count ?? 0);
    $pendingNote = $pending > 0 ? __('dashboard.needs_payment') : __('dashboard.nothing_waiting');
    $pendingTone = $pending > 0 ? 'amber' : 'green';
    $linkEntries = url('admin/transactions');
    $linkExchanges = route('admin.exchanges.index');
    $linkRemittances = route('admin.remittances.outgoing');
    $linkPending = route('admin.remittances.pendingIncoming');
    $panels = [
        [__('dashboard.customer_balances'), __('dashboard.customer_position'), $customerBalances, 'users', 'violet'],
        [__('dashboard.saraf_balances'), __('dashboard.saraf_position'), $sarafBalances, 'building-columns', 'amber'],
    ];
@endphp
<div id="dashboardContent" class="db-content">
    <script type="application/json" id="dashboardChartData">{!! json_encode([
        'dailyFlow' => $dailyFlow->values(),
        'profitLoss' => $profitLossByCurrency->values(),
        'balanceLabels' => $cashBalances->pluck('currency')->merge($customerBalances->pluck('currency'))->merge($sarafBalances->pluck('currency'))->unique()->values(),
        'cashBalances' => $cashBalances->pluck('balance', 'currency'),
        'customerBalances' => $customerBalances->pluck('balance', 'currency'),
        'sarafBalances' => $sarafBalances->pluck('balance', 'currency'),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    {{-- 1. cash in the safe: the first thing a Saraf wants to know --}}
    <section class="db-section" aria-labelledby="db-cash-title">
        <div class="db-head">
            <h2 id="db-cash-title"><x-icon name="vault" /> {{ __('dashboard.cash_accounts') }}</h2>
            <span>{{ __('dashboard.cash_available') }}</span>
        </div>
        <div class="db-cash">
            @forelse ($cashBalances as $row)
                <div class="db-cash-card {{ $row->balance < 0 ? 'is-negative' : '' }}">
                    <span class="db-cash-code">{{ $row->currency }}</span>
                    <strong dir="ltr">{{ format_amount($row->balance, 2) }}</strong>
                    <small>{{ $row->account_count }} {{ __('dashboard.accounts') }}</small>
                </div>
            @empty
                <div class="db-empty"><x-icon name="inbox" /> {{ __('dashboard.no_balance') }}</div>
            @endforelse
        </div>
    </section>

    {{-- 2. the period in numbers --}}
    <section class="db-section" aria-labelledby="db-period-title">
        <div class="db-head">
            <h2 id="db-period-title"><x-icon name="calendar-check" /> {{ $selectedDate }}</h2>
            <span>{{ $selectedCurrency?->code ?? __('dashboard.all_currencies') }}</span>
        </div>
        <div class="row g-3">
            <div class="col-6 col-xl-3"><x-stat :label="__('dashboard.period_entries')" :value="$entryCount" icon="right-left" tone="blue" :href="$linkEntries" /></div>
            <div class="col-6 col-xl-3"><x-stat :label="__('dashboard.exchanges')" :value="$exchangeCount" icon="coins" tone="green" :href="$linkExchanges" :note="$exchangeNote" /></div>
            <div class="col-6 col-xl-3"><x-stat :label="__('dashboard.remittances')" :value="$remittanceCount" icon="paper-plane" tone="violet" :href="$linkRemittances" :note="$remittanceNote" /></div>
            <div class="col-6 col-xl-3"><x-stat :label="__('ui.pending_remittances')" :value="$pending" icon="hourglass-half" :tone="$pendingTone" :href="$linkPending" :note="$pendingNote" /></div>
        </div>
    </section>

    {{-- 3. movement of the period, and who owes whom --}}
    <div class="row g-3 db-section">
        <div class="col-xl-8">
            <section class="card h-100" aria-labelledby="db-flow-title">
                <div class="card-header"><h2 class="card-title" id="db-flow-title">{{ __('dashboard.credit_debit_flow') }}</h2><span class="db-muted">{{ $selectedDate }}</span></div>
                <div class="card-body">
                    @if ($mixed && $dailyFlow->isNotEmpty())
                        <p class="db-note"><x-icon name="circle-info" /> {{ __('dashboard.mixed_note') }}</p>
                    @endif
                    @if ($dailyFlow->isEmpty())
                        <div class="db-empty is-tall"><x-icon name="chart-line" /> {{ __('dashboard.no_transactions') }}</div>
                    @else
                        <div class="db-chart"><canvas id="dailyFlowChart" role="img" aria-label="{{ __('dashboard.credit_debit_flow') }}"></canvas></div>
                    @endif
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="card h-100" aria-labelledby="db-position-title">
                <div class="card-header"><h2 class="card-title" id="db-position-title">{{ __('dashboard.total_position') }}</h2><a class="db-link" href="{{ url('admin/reports/balance') }}">{{ __('dashboard.view_all') }}</a></div>
                <div class="card-body">
                    <h3 class="db-sub is-credit"><x-icon name="arrow-down" /> {{ __('dashboard.creditors') }} <small>{{ __('dashboard.we_owe') }}</small></h3>
                    @forelse ($creditorBalances as $row)
                        <div class="db-line"><span><b>{{ $row->currency }}</b> <small>{{ $row->account_count }} {{ __('dashboard.accounts') }}</small></span><strong dir="ltr" class="db-pos">{{ format_amount($row->balance, 2) }}</strong></div>
                    @empty
                        <div class="db-none">{{ __('dashboard.no_creditors') }}</div>
                    @endforelse
                    <h3 class="db-sub is-debit mt-3"><x-icon name="arrow-up" /> {{ __('dashboard.debtors') }} <small>{{ __('dashboard.owed_to_us') }}</small></h3>
                    @forelse ($debtorBalances as $row)
                        <div class="db-line"><span><b>{{ $row->currency }}</b> <small>{{ $row->account_count }} {{ __('dashboard.accounts') }}</small></span><strong dir="ltr" class="db-neg">{{ format_amount($row->balance, 2) }}</strong></div>
                    @empty
                        <div class="db-none">{{ __('dashboard.no_debtors') }}</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    {{-- 4. profit of the period, and balances of customers and Sarafs --}}
    <div class="row g-3 db-section">
        <div class="col-lg-6 col-xl-4">
            <section class="card h-100" aria-labelledby="db-pnl-title">
                <div class="card-header"><h2 class="card-title" id="db-pnl-title">{{ __('dashboard.profit_loss') }}</h2><span class="db-muted">{{ $selectedDate }}</span></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 keep-table db-table">
                        <thead><tr><th scope="col">{{ __('dashboard.currency') }}</th><th scope="col" class="text-end">{{ __('dashboard.income') }}</th><th scope="col" class="text-end">{{ __('dashboard.expense') }}</th><th scope="col" class="text-end">{{ __('dashboard.net_pnl') }}</th></tr></thead>
                        <tbody>
                        @forelse ($profitLossByCurrency as $row)
                            <tr><th scope="row">{{ $row->currency }}</th><td class="text-end" dir="ltr">{{ format_amount($row->income, 2) }}</td><td class="text-end" dir="ltr">{{ format_amount($row->expense, 2) }}</td><td class="text-end" dir="ltr"><strong class="{{ $row->net_profit < 0 ? 'db-neg' : 'db-pos' }}">{{ format_amount($row->net_profit, 2) }}</strong></td></tr>
                        @empty
                            <tr><td colspan="4"><div class="db-none text-center">{{ __('dashboard.no_profit_loss') }}</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($profitLossByCurrency->isNotEmpty())
                    <div class="card-body pt-2"><div class="db-chart is-small"><canvas id="profitLossChart" role="img" aria-label="{{ __('dashboard.net_profit_currency') }}"></canvas></div></div>
                @endif
            </section>
        </div>
        @foreach ($panels as [$title, $subtitle, $rows, $icon, $tone])
            <div class="col-lg-6 col-xl-4">
                <section class="card h-100">
                    <div class="card-header"><h2 class="card-title"><span class="db-dot kpi-{{ $tone }}"><span class="kpi-icon"><x-icon :name="$icon" /></span></span> {{ $title }}</h2></div>
                    <div class="card-body">
                        <p class="db-muted mb-2">{{ $subtitle }}</p>
                        @forelse ($rows as $row)
                            <div class="db-line"><span><b>{{ $row->currency }}</b> <small>{{ $row->account_count }} {{ __('dashboard.accounts') }}</small></span><span class="text-end"><strong dir="ltr" class="{{ $row->balance < 0 ? 'db-neg' : 'db-pos' }}">{{ format_amount(abs($row->balance), 2) }}</strong> <small class="db-side">{{ $row->balance < 0 ? __('dashboard.debit') : __('dashboard.credit') }}</small></span></div>
                        @empty
                            <div class="db-none">{{ __('dashboard.no_balance') }}</div>
                        @endforelse
                    </div>
                </section>
            </div>
        @endforeach
    </div>

    {{-- 5. balances side by side, expenses and the last closing --}}
    <div class="row g-3 db-section">
        <div class="col-xl-8">
            <section class="card h-100" aria-labelledby="db-bal-title">
                <div class="card-header"><h2 class="card-title" id="db-bal-title">{{ __('dashboard.balances_by_currency') }}</h2><span class="db-muted">{{ __('dashboard.live_balances') }}</span></div>
                <div class="card-body">
                    @if ($cashBalances->isEmpty() && $customerBalances->isEmpty() && $sarafBalances->isEmpty())
                        <div class="db-empty is-tall"><x-icon name="chart-column" /> {{ __('dashboard.no_balance') }}</div>
                    @else
                        <div class="db-chart"><canvas id="balanceChart" role="img" aria-label="{{ __('dashboard.balances_by_currency') }}"></canvas></div>
                    @endif
                </div>
            </section>
        </div>
        <div class="col-xl-4 d-flex flex-column gap-3">
            <section class="card" aria-labelledby="db-exp-title">
                <div class="card-header"><h2 class="card-title" id="db-exp-title">{{ __('dashboard.approved_expenses') }}</h2><a class="db-link" href="{{ url('admin/expenses') }}">{{ __('dashboard.view_all') }}</a></div>
                <div class="card-body">
                    @forelse ($expensesByCurrency as $row)
                        <div class="db-line"><span><b>{{ $row->currency }}</b></span><strong dir="ltr" class="db-neg">{{ format_amount($row->total, 2) }}</strong></div>
                    @empty
                        <div class="db-none">{{ __('dashboard.no_expenses') }}</div>
                    @endforelse
                </div>
            </section>
            <section class="card flex-fill" aria-labelledby="db-close-title">
                <div class="card-header"><h2 class="card-title" id="db-close-title">{{ __('dashboard.last_closing') }}</h2><span class="db-muted" dir="ltr">{{ $latestClosing?->closing_date?->format('Y-m-d') ?? '' }}</span></div>
                <div class="card-body">
                    @if ($latestClosing)
                        <div class="db-big" dir="ltr">{{ format_amount($latestClosing->final_balance ?? 0, 2) }}</div>
                        <div class="db-line"><span>{{ __('dashboard.cash') }}</span><strong dir="ltr">{{ format_amount($latestClosing->cash_balance ?? 0, 2) }}</strong></div>
                        <div class="db-line"><span>{{ __('dashboard.pnl') }}</span><strong dir="ltr">{{ format_amount(($latestClosing->profit_balance ?? 0) - ($latestClosing->loss_balance ?? 0), 2) }}</strong></div>
                    @else
                        <div class="db-none">{{ __('dashboard.no_closing') }}</div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    {{-- 6. the newest entries --}}
    <section class="card db-section" aria-labelledby="db-latest-title">
        <div class="card-header"><h2 class="card-title" id="db-latest-title">{{ __('dashboard.latest_transactions') }}</h2><a class="db-link" href="{{ url('admin/transactions') }}">{{ __('dashboard.view_all') }} <x-icon name="arrow-right" /></a></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 db-table">
                <thead><tr><th scope="col">{{ __('dashboard.account') }}</th><th scope="col">{{ __('dashboard.reference') }}</th><th scope="col">{{ __('dashboard.date') }}</th><th scope="col">{{ __('dashboard.type') }}</th><th scope="col" class="text-end">{{ __('dashboard.amount') }}</th></tr></thead>
                <tbody>
                @forelse ($latestTransactions as $transaction)
                    <tr>
                        <td><strong>{{ $transaction->account?->name ?? '—' }}</strong><br><small class="db-muted" dir="ltr">{{ $transaction->account?->code }}</small></td>
                        <td>{{ $transaction->reference ?: '—' }}</td>
                        <td dir="ltr">{{ $transaction->created_at?->format('Y-m-d H:i') }}</td>
                        <td><span class="badge-soft {{ $transaction->transaction_type === 'credit' ? 'success' : 'danger' }}">{{ __('dashboard.'.$transaction->transaction_type) }}</span></td>
                        <td class="text-end" dir="ltr"><strong>{{ format_amount($transaction->amount, 2) }}</strong> <small class="db-muted">{{ $transaction->currency?->code }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="db-empty"><x-icon name="inbox" /> {{ __('dashboard.no_transactions') }}</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- 7. the size of the books --}}
    <section class="db-section" aria-label="{{ __('dashboard.overview') }}">
        <div class="db-counts">
            <a href="{{ url('admin/accounts') }}"><x-icon name="users" /> <strong>{{ format_amount($totals['customers'], 0) }}</strong> {{ __('dashboard.customers') }}</a>
            <a href="{{ url('admin/accounts') }}"><x-icon name="building-columns" /> <strong>{{ format_amount($totals['sarafs'], 0) }}</strong> {{ __('dashboard.sarafs') }}</a>
            <a href="{{ url('admin/safe') }}"><x-icon name="vault" /> <strong>{{ format_amount($totals['cash_accounts'], 0) }}</strong> {{ __('dashboard.cash_accounts') }}</a>
            <a href="{{ url('admin/accounts') }}"><x-icon name="book" /> <strong>{{ format_amount($totals['accounts'], 0) }}</strong> {{ __('dashboard.active_accounts') }}</a>
            <a href="{{ route('admin.exchanges.index') }}"><x-icon name="coins" /> <strong>{{ format_amount($totals['exchanges'], 0) }}</strong> {{ __('dashboard.exchanges') }}</a>
            <a href="{{ route('admin.remittances.outgoing') }}"><x-icon name="paper-plane" /> <strong>{{ format_amount($totals['remittances'], 0) }}</strong> {{ __('dashboard.remittances') }}</a>
            <a href="{{ url('admin/users') }}"><x-icon name="users-gear" /> <strong>{{ format_amount($totals['users'], 0) }}</strong> {{ __('dashboard.users') }}</a>
        </div>
    </section>
</div>
