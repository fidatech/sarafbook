<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Currency;
use App\Models\DailyClosing;
use App\Models\Exchange;
use App\Models\Expense;
use App\Models\Remittance;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DashboardController extends Controller
{
    public function refreshBalances(Request $request)
    {
        abort_unless($request->user()->can('update settings'), 403);
        try {
            if (Artisan::call('optimize:clear') !== 0) {
                throw new \RuntimeException('Cache clearing failed.');
            }
            if (Artisan::call('balances:recalculate') !== 0) {
                throw new \RuntimeException('Balance recalculation failed.');
            }
            return response()->json(['message' => __('ui.dashboard_refresh.success')]);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['message' => __('ui.dashboard_refresh.failed')], 500);
        }
    }

    public function index(Request $request)
    {
        $currencies = Currency::active()->orderByDesc('is_default')->orderBy('code')->get();
        $selectedCurrencyId = $request->integer('currency_id') ?: null;
        $selectedCurrency = $selectedCurrencyId ? $currencies->firstWhere('id', $selectedCurrencyId) : null;
        $datePreset = $request->string('preset')->toString() ?: 'this_month';
        [$startDate, $endDate, $selectedDate] = $this->resolveDateRange(
            $datePreset,
            $request->input('date_from'),
            $request->input('date_to')
        );
        $dateFrom = $startDate->toDateString();
        $dateTo = $endDate->toDateString();

        $accountCount = fn (string $type) => Account::active()
            ->whereHas('subCategory', fn (Builder $query) => $query->where('name', $type))
            ->count();

        $totals = [
            'customers' => $accountCount('Customer'),
            'cash_accounts' => Account::active()->where(function ($query) {
                $query->where('is_safe', true)
                    ->orWhereHas('subCategory', fn (Builder $sub) => $sub->where('name', 'Safe'));
            })->count(),
            'sarafs' => $accountCount('Saraf'),
            'remittances' => Remittance::count(),
            'exchanges' => Exchange::count(),
            'users' => User::count(),
            'accounts' => Account::active()->count(),
        ];

        $periodTransactions = Transaction::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'active')->where('is_visible', true)
            ->when($selectedCurrencyId, fn ($query) => $query->where('currency_id', $selectedCurrencyId));
        $totals['transactions'] = (clone $periodTransactions)->count();

        $cashBalances = $this->balancesForTypes(['Safe'], $selectedCurrencyId, true);
        $customerBalances = $this->balancesForTypes(['Customer'], $selectedCurrencyId);
        $sarafBalances = $this->balancesForTypes(['Saraf'], $selectedCurrencyId);
        $pnlBalances = $this->balancesForTypes(['PnL'], $selectedCurrencyId);
        $expenseLedgerBalances = $this->balancesForTypes(['EXP'], $selectedCurrencyId);
        $creditorBalances = $this->positionBalances('>', $selectedCurrencyId);
        $debtorBalances = $this->positionBalances('<', $selectedCurrencyId);

        $expensesByCurrency = Expense::query()
            ->join('currencies', 'expenses.currency_id', '=', 'currencies.id')
            ->whereBetween('expenses.expense_date', [$dateFrom, $dateTo])
            ->where('expenses.status', 'approved')
            ->when($selectedCurrencyId, fn ($query) => $query->where('expenses.currency_id', $selectedCurrencyId))
            ->groupBy('expenses.currency_id', 'currencies.code', 'currencies.symbol')
            ->selectRaw('currencies.code as currency, currencies.symbol, SUM(expenses.amount) as total')
            ->orderBy('currencies.code')->get();

        $exchangeStats = Exchange::query()
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->when($selectedCurrencyId, fn ($query) => $query->where(function ($nested) use ($selectedCurrencyId) {
                $nested->where('from_currency_id', $selectedCurrencyId)->orWhere('to_currency_id', $selectedCurrencyId);
            }))
            ->selectRaw("COUNT(*) total_count, SUM(CASE WHEN type = 'purchase' THEN 1 ELSE 0 END) purchase_count, SUM(CASE WHEN type = 'sell' THEN 1 ELSE 0 END) sell_count")
            ->first();

        $remittanceStats = Remittance::query()
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->when($selectedCurrencyId, fn ($query) => $query->where('currency_id', $selectedCurrencyId))
            ->selectRaw("COUNT(*) total_count, SUM(CASE WHEN type = 'incoming' THEN 1 ELSE 0 END) incoming_count, SUM(CASE WHEN type = 'outgoing' THEN 1 ELSE 0 END) outgoing_count, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) pending_count")
            ->first();

        $profitLossByCurrency = $this->profitLossByCurrency($dateFrom, $dateTo, $selectedCurrencyId, $expensesByCurrency);
        $dailyFlow = $this->dailyFlow($startDate, $endDate, $selectedCurrencyId);
        $latestClosing = DailyClosing::query()->latest('closing_date')->first();
        $latestTransactions = (clone $periodTransactions)->with(['account:id,name,code', 'currency:id,code'])
            ->latest()->limit(8)->get();

        $viewData = compact(
            'currencies', 'selectedCurrencyId', 'selectedCurrency', 'datePreset', 'dateFrom', 'dateTo', 'selectedDate',
            'totals', 'cashBalances', 'customerBalances', 'sarafBalances', 'pnlBalances', 'expenseLedgerBalances',
            'creditorBalances', 'debtorBalances', 'expensesByCurrency', 'exchangeStats', 'remittanceStats',
            'profitLossByCurrency', 'dailyFlow', 'latestClosing', 'latestTransactions'
        );

        if ($request->ajax()) {
            return response()->json(['html' => view('admin.dashboard-content', $viewData)->render()]);
        }

        return view('admin.dashboard', $viewData);
    }

    private function resolveDateRange(string $preset, ?string $from, ?string $to): array
    {
        return match ($preset) {
            'today' => [today()->startOfDay(), today()->endOfDay(), __('ui.today')],
            'yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay(), __('ui.yesterday')],
            'this_week' => [now()->startOfWeek(Carbon::SATURDAY), now()->endOfWeek(Carbon::FRIDAY), __('ui.this_week')],
            'last_30_days' => [today()->subDays(29)->startOfDay(), today()->endOfDay(), __('dashboard.last_30_days')],
            'this_year' => [now()->startOfYear(), now()->endOfYear(), __('ui.this_year')],
            'custom' => $this->customDateRange($from, $to),
            default => [now()->startOfMonth(), now()->endOfMonth(), __('ui.this_month')],
        };
    }

    private function customDateRange(?string $from, ?string $to): array
    {
        $start = $from ? Carbon::parse($from)->startOfDay() : today()->startOfDay();
        $end = $to ? Carbon::parse($to)->endOfDay() : $start->copy()->endOfDay();
        if ($end->lt($start)) [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        return [$start, $end, $start->format('M d') . ' – ' . $end->format('M d, Y')];
    }

    private function balancesForTypes(array $types, ?int $currencyId, bool $includeSafeFlag = false)
    {
        return AccountBalance::query()
            ->join('accounts', 'account_balances.account_id', '=', 'accounts.id')
            ->join('account_sub_categories', 'accounts.account_sub_category_id', '=', 'account_sub_categories.id')
            ->join('currencies', 'account_balances.currency_id', '=', 'currencies.id')
            ->where('accounts.is_active', true)
            ->where(function ($query) use ($types, $includeSafeFlag) {
                $query->whereIn('account_sub_categories.name', $types);
                if ($includeSafeFlag) $query->orWhere('accounts.is_safe', true);
            })
            ->when($currencyId, fn ($query) => $query->where('account_balances.currency_id', $currencyId))
            ->groupBy('account_balances.currency_id', 'currencies.code', 'currencies.symbol')
            ->selectRaw('currencies.code as currency, currencies.symbol, SUM(account_balances.balance) as balance, COUNT(DISTINCT accounts.id) as account_count')
            ->orderBy('currencies.code')->get();
    }

    private function positionBalances(string $operator, ?int $currencyId)
    {
        return AccountBalance::query()
            ->join('accounts', 'account_balances.account_id', '=', 'accounts.id')
            ->join('account_sub_categories', 'accounts.account_sub_category_id', '=', 'account_sub_categories.id')
            ->join('currencies', 'account_balances.currency_id', '=', 'currencies.id')
            ->where('accounts.is_active', true)->where('accounts.include_in_reports', true)
            ->whereIn('account_sub_categories.name', ['Customer', 'Saraf'])
            ->where('account_balances.balance', $operator, 0)
            ->when($currencyId, fn ($query) => $query->where('account_balances.currency_id', $currencyId))
            ->groupBy('account_balances.currency_id', 'currencies.code', 'currencies.symbol')
            ->selectRaw('currencies.code as currency, currencies.symbol, SUM(ABS(account_balances.balance)) as balance, COUNT(DISTINCT accounts.id) as account_count')
            ->orderBy('currencies.code')->get();
    }

    private function profitLossByCurrency(string $from, string $to, ?int $currencyId, $expenses)
    {
        $exchangeProfit = Exchange::query()
            ->join('currencies', DB::raw('COALESCE(exchanges.profit_currency_id, exchanges.from_currency_id)'), '=', 'currencies.id')
            ->whereBetween('exchanges.date', [$from, $to])
            ->when($currencyId, fn ($query) => $query->whereRaw('COALESCE(exchanges.profit_currency_id, exchanges.from_currency_id) = ?', [$currencyId]))
            ->groupBy('currencies.code')->selectRaw('currencies.code as currency, SUM(COALESCE(exchanges.profit, 0)) as exchange_profit')
            ->get()->keyBy('currency');

        $commission = Remittance::query()
            ->join('currencies', 'remittances.commission_currency_id', '=', 'currencies.id')
            ->whereBetween('remittances.date', [$from, $to])
            ->when($currencyId, fn ($query) => $query->where('commission_currency_id', $currencyId))
            ->groupBy('currencies.code')->selectRaw('currencies.code as currency, SUM(COALESCE(commission_kabul_amount, 0)) as commission')
            ->get()->keyBy('currency');

        $expenseMap = $expenses->keyBy('currency');
        return collect($exchangeProfit->keys())->merge($commission->keys())->merge($expenseMap->keys())->unique()->sort()->values()
            ->map(function ($code) use ($exchangeProfit, $commission, $expenseMap) {
                $exchange = (float) ($exchangeProfit->get($code)->exchange_profit ?? 0);
                $fee = (float) ($commission->get($code)->commission ?? 0);
                $expense = (float) ($expenseMap->get($code)->total ?? 0);
                return (object) ['currency' => $code, 'income' => $exchange + $fee, 'expense' => $expense, 'net_profit' => $exchange + $fee - $expense];
            });
    }

    private function dailyFlow(Carbon $start, Carbon $end, ?int $currencyId)
    {
        return Transaction::query()->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->whereBetween('transactions.created_at', [$start, $end])->where('transactions.status', 'active')->where('transactions.is_visible', true)
            ->when($currencyId, fn ($query) => $query->where('transactions.currency_id', $currencyId))
            ->groupBy(DB::raw('DATE(transactions.created_at)'))
            ->selectRaw("DATE(transactions.created_at) date, SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) credit, SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) debit")
            ->orderBy('date')->get();
    }
}
