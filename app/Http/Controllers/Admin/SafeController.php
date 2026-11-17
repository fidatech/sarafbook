<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SafeController extends Controller
{
    /** The safe (cash) account: the first account of the Safe type. */
    private function cashAccountId(): ?int
    {
        $id = Account::where('account_sub_category_id', 3)->orderBy('id')->value('id');

        return $id ? (int) $id : (Account::whereKey(1)->exists() ? 1 : null);
    }

    /** The chosen period: [start, end, key]; start and end are null for "everything". */
    private function period(Request $request): array
    {
        $now = now();
        $key = (string) $request->query('range', 'today');
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('from')) ? $request->query('from') : null;
        $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('to')) ? $request->query('to') : null;
        if ($from || $to) {
            return [\Carbon\Carbon::parse($from ?? '2000-01-01')->startOfDay(), \Carbon\Carbon::parse($to ?? $now->toDateString())->endOfDay(), 'custom'];
        }

        return match ($key) {
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), $key],
            'this_week' => [$now->copy()->startOfWeek(\Carbon\Carbon::SATURDAY)->startOfDay(), $now->copy()->endOfDay(), $key],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay(), $key],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth(), $key],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay(), $key],
            'all' => [null, null, $key],
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'today'],
        };
    }

    /**
     * The safe: what was in it at the start of the period, what came in and went out, what is in
     * it now, and its ledger newest first with the balance after each line. Paged on the server;
     * the balances of a page come from two small sums, not from reading the whole history.
     */
    public function index(Request $request)
    {
        $cashId = $this->cashAccountId();
        [$start, $end, $range] = $this->period($request);
        $type = in_array($request->query('type'), ['credit', 'debit'], true) ? $request->query('type') : '';
        $currencyId = $request->integer('currency_id') ?: null;
        $currencies = Currency::orderBy('id')->get()->keyBy('id');

        $cash = fn () => Transaction::query()->where('account_id', $cashId)->where('status', 'active');
        $inPeriod = fn ($q) => $q->when($start, fn ($w) => $w->where('created_at', '>=', $start))->when($end, fn ($w) => $w->where('created_at', '<=', $end));
        $signed = "SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END)";

        $opening = $start ? $cash()->where('created_at', '<', $start)->groupBy('currency_id')->selectRaw("currency_id, {$signed} AS v")->pluck('v', 'currency_id') : collect();
        $moves = $inPeriod($cash())->groupBy('currency_id')
            ->selectRaw("currency_id, SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) AS cin, SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) AS cout, COUNT(*) AS n")
            ->get()->keyBy('currency_id');
        $cards = [];
        foreach ($currencies as $cid => $currency) {
            $o = (float) ($opening[$cid] ?? 0);
            $in = (float) ($moves[$cid]->cin ?? 0);
            $out = (float) ($moves[$cid]->cout ?? 0);
            if (abs($o) < 0.005 && ! isset($moves[$cid])) {
                continue;
            }
            $cards[$cid] = ['code' => $currency->code, 'opening' => $o, 'in' => $in, 'out' => $out, 'closing' => $o + $in - $out, 'n' => (int) ($moves[$cid]->n ?? 0)];
        }

        $rows = $inPeriod($cash())->with('currency:id,code')
            ->when($type, fn ($q) => $q->where('transaction_type', $type))
            ->when($currencyId, fn ($q) => $q->where('currency_id', $currencyId))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(50)->withQueryString();

        // balance after each line: the closing balance less every line newer than it
        $after = [];
        if (! $type && $rows->isNotEmpty()) {
            $first = $rows->first();
            $newer = $inPeriod($cash())
                ->where(fn ($q) => $q->where('created_at', '>', $first->created_at)->orWhere(fn ($w) => $w->where('created_at', $first->created_at)->where('id', '>', $first->id)))
                ->groupBy('currency_id')->selectRaw("currency_id, {$signed} AS v")->pluck('v', 'currency_id');
            $running = [];
            foreach ($cards as $cid => $card) {
                $running[$cid] = $card['closing'] - (float) ($newer[$cid] ?? 0);
            }
            foreach ($rows as $row) {
                $cid = $row->currency_id;
                $running[$cid] = $running[$cid] ?? 0.0;
                $after[$row->id] = $running[$cid];
                $running[$cid] -= $row->transaction_type === 'credit' ? (float) $row->amount : -(float) $row->amount;
            }
        }

        // who the money came from or went to: the other side of each line
        $other = $rows->pluck('linked_id')->filter()->unique()->values();
        $counter = $other->isEmpty() ? collect() : Transaction::query()->with('account:id,name,code')
            ->whereIn('linked_id', $other)->where('account_id', '!=', $cashId)->orderBy('id')->get()->unique('linked_id')->keyBy('linked_id');

        return page('admin.safe.index', [
            'cards' => $cards, 'rows' => $rows, 'after' => $after, 'counter' => $counter,
            'currencies' => $currencies, 'range' => $range, 'start' => $start, 'end' => $end,
            'filters' => ['range' => $range, 'from' => $request->query('from'), 'to' => $request->query('to'), 'type' => $type, 'currency_id' => $currencyId],
            'cashAccount' => $cashId ? Account::find($cashId) : null,
        ], ['list', 'cards']);
    }

    public function ledger(Request $request)
    {
        $setting = Setting::first();
        $roundEnabled = $setting?->round_amount ?? false;

        /*
    |--------------------------------------------------------------------------
    | CASH / SAFE ACCOUNT
    |--------------------------------------------------------------------------
    | Current system logic shows account ID 1 was excluded from all-account report,
    | so we treat account ID 1 as the cash/safe account.
    */
        $cashAccountId = $this->cashAccountId() ?? 1;
        // a print of "everything" would read the whole history: the last 30 days instead
        if (! $request->filled('range_type') || $request->range_type === 'all') {
            if (! $request->filled('date_range')) {
                $request->merge(['range_type' => 'custom', 'date_range' => now()->subDays(30)->toDateString().' - '.now()->toDateString()]);
            }
        }

        $cashAccount = Account::find($cashAccountId);

        if (!$cashAccount) {
            return response()->json([
                'message' => 'Cash account not found.',
                'currencies' => [],
                'opening_balances' => [],
                'rows' => [],
                'closing_balances' => [],
                'round_enabled' => $roundEnabled,
            ], 404);
        }

        /*
    |--------------------------------------------------------------------------
    | BASE FILTER
    |--------------------------------------------------------------------------
    | Safe ledger must only display transactions of the cash account.
    | Account filter is removed completely.
    */
        $baseFilter = function ($query) use ($request, $cashAccountId) {
            $query->where('account_id', $cashAccountId);

            if ($request->filled('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }
        };

        /*
    |--------------------------------------------------------------------------
    | DATE LOGIC
    |--------------------------------------------------------------------------
    */
        $start = null;
        $end   = null;

        $today = now();

        if ($request->filled('date_range') && str_contains($request->date_range, ' - ')) {
            $request->merge(['range_type' => 'custom']);
        }

        switch ($request->range_type) {
            case 'today':
                $start = $today->copy()->startOfDay();
                $end   = $today->copy()->endOfDay();
                break;

            case 'yesterday':
                $start = $today->copy()->subDay()->startOfDay();
                $end   = $today->copy()->subDay()->endOfDay();
                break;

            case 'this_week':
                $start = $today->copy()->startOfWeek(\Carbon\Carbon::SATURDAY)->startOfDay();
                $end   = $today->copy()->endOfDay();
                break;

            case 'last_week':
                $start = $today->copy()->subWeek()->startOfWeek(\Carbon\Carbon::SATURDAY)->startOfDay();
                $end   = $today->copy()->subWeek()->endOfWeek(\Carbon\Carbon::FRIDAY)->endOfDay();
                break;

            case 'this_month':
                $start = $today->copy()->startOfMonth()->startOfDay();
                $end   = $today->copy()->endOfDay();
                break;

            case 'last_month':
                $start = $today->copy()->subMonth()->startOfMonth()->startOfDay();
                $end   = $today->copy()->subMonth()->endOfMonth()->endOfDay();
                break;

            case 'this_year':
                $start = $today->copy()->startOfYear()->startOfDay();
                $end   = $today->copy()->endOfDay();
                break;

            case 'last_year':
                $start = $today->copy()->subYear()->startOfYear()->startOfDay();
                $end   = $today->copy()->subYear()->endOfYear()->endOfDay();
                break;

            case 'custom':
                if ($request->filled('date_range') && str_contains($request->date_range, ' - ')) {
                    [$s, $e] = explode(' - ', $request->date_range);

                    $start = \Carbon\Carbon::parse(trim($s))->startOfDay();
                    $end   = \Carbon\Carbon::parse(trim($e))->endOfDay();
                }
                break;

            case 'all':
            default:
                break;
        }

        /*
    |--------------------------------------------------------------------------
    | CURRENCIES
    |--------------------------------------------------------------------------
    */
        $currencies = Currency::orderBy('id')->get();

        /*
    |--------------------------------------------------------------------------
    | OPENING BALANCE
    |--------------------------------------------------------------------------
    | Only cash account balance before selected start date.
    */
        $openingQuery = Transaction::query();

        $baseFilter($openingQuery);

        if ($start) {
            $openingQuery->where('created_at', '<', $start);
        }

        $openingRaw = $openingQuery
            ->select(
                'currency_id',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_debit")
            )
            ->groupBy('currency_id')
            ->get()
            ->keyBy('currency_id');

        $openingBalances = [];

        foreach ($currencies as $currency) {
            $cid = $currency->id;

            $openingBalances[$cid] =
                (float) ($openingRaw[$cid]->total_credit ?? 0)
                - (float) ($openingRaw[$cid]->total_debit ?? 0);
        }

        /*
    |--------------------------------------------------------------------------
    | CASH ACCOUNT TRANSACTIONS ONLY
    |--------------------------------------------------------------------------
    */
        $transactionsQuery = Transaction::with(['currency', 'account']);

        $baseFilter($transactionsQuery);

        if ($start && $end) {
            $transactionsQuery->whereBetween('created_at', [$start, $end]);
        }

        $transactions = $transactionsQuery
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        /*
    |--------------------------------------------------------------------------
    | LINKED ACCOUNT NAMES
    |--------------------------------------------------------------------------
    | For every cash transaction, find the opposite linked transaction.
    | Then display that linked account name in the ledger.
    */
        $linkedIds = $transactions
            ->pluck('linked_id')
            ->filter()
            ->unique()
            ->values();

        $linkedTransactions = collect();

        if ($linkedIds->isNotEmpty()) {
            $linkedTransactions = Transaction::with('account')
                ->whereIn('linked_id', $linkedIds)
                ->where('account_id', '!=', $cashAccountId)
                ->orderBy('id')
                ->get()
                ->groupBy('linked_id');
        }

        /*
    |--------------------------------------------------------------------------
    | BUILD LEDGER ROWS
    |--------------------------------------------------------------------------
    */
        $runningBalances = $openingBalances;
        $rows = [];

        foreach ($transactions as $index => $t) {
            $linkedAccountName = null;
            $linkedAccountCode = null;

            if ($t->linked_id && $linkedTransactions->has($t->linked_id)) {
                $linkedTransaction = $linkedTransactions->get($t->linked_id)->first();

                $linkedAccountName = optional($linkedTransaction->account)->name;
                $linkedAccountCode = optional($linkedTransaction->account)->code;
            }

            $row = [
                'sr' => $index + 1,

                // Here we show the opposite/linked account.
                // If no linked account exists, fallback to cash account.
                'account_name' => $linkedAccountName ?: $cashAccount->name,
                'account_code' => $linkedAccountCode ?: $cashAccount->code,

                'date' => optional($t->created_at)->format('Y-m-d'),
                'note' => $t->note,
                'type' => $t->transaction_type,
                'data' => [],
            ];

            foreach ($currencies as $currency) {
                $row['data'][$currency->code] = [
                    'amount' => null,
                    'balance' => null,
                ];
            }

            $cid = $t->currency_id;
            $code = optional($t->currency)->code;

            if ($code) {
                $amount = (float) $t->amount;

                if ($t->transaction_type === 'credit') {
                    $runningBalances[$cid] += $amount;
                    $signed = $amount;
                } else {
                    $runningBalances[$cid] -= $amount;
                    $signed = -$amount;
                }

                $row['data'][$code] = [
                    'amount' => $roundEnabled ? round($signed) : $signed,
                    'balance' => $roundEnabled ? round($runningBalances[$cid]) : $runningBalances[$cid],
                ];
            }

            $rows[] = $row;
        }

        $closingBalances = $runningBalances;

        foreach ($openingBalances as $cid => $val) {
            $openingBalances[$cid] = $roundEnabled ? round($val) : $val;
        }

        foreach ($closingBalances as $cid => $val) {
            $closingBalances[$cid] = $roundEnabled ? round($val) : $val;
        }

        return response()->json([
            'filters' => [
                'start' => $start,
                'end' => $end,
                'cash_account_id' => $cashAccountId,
                'cash_account_name' => $cashAccount->name,
            ],
            'currencies' => $currencies->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->code,
            ]),
            'opening_balances' => $openingBalances,
            'rows' => $rows,
            'closing_balances' => $closingBalances,
            'round_enabled' => $roundEnabled,
        ]);
    }

    public function printLedger(Request $request)
    {
        $data = $this->ledger($request)->getData(true);

        $setting = Setting::first();

        $settings = [
            'company_name' => $setting->company_name ?? 'FIDA TECHNOLOGIES',
            'address'      => $setting->address ?? 'Kabul, Afghanistan',
            'phone'        => $setting->contact ?? '+93 78 400 6200',
            'logo'         => $setting?->logo ? asset('images/' . $setting->logo) : null,
        ];

        return view('admin.safe.print-ledger', [
            'data' => $data,
            'settings' => $settings
        ]);
    }
}
