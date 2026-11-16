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
    public function index()
    {
        // return view('admin.safe.index', [
        //     'accounts' => Account::whereIn('account_sub_category_id', [3])
        //         ->orderBy('name')
        //         ->get(),

        //     'currencies' => Currency::orderBy('code')->get()
        // ]);

        return view('admin.safe.index', [
            'currencies' => Currency::orderBy('id')->get()
        ]);
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
        $cashAccountId = 1;

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
