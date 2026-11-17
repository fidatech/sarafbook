<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountSubCategory;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Models\AccountBalance;
use App\Models\Setting;
use App\Models\Transaction;
use Yajra\DataTables\Facades\DataTables;
use PDF;
use ArPHP\I18N\Arabic;
use Carbon\Carbon;
use App\Exports\TransactionsEnterpriseExport;
use App\Models\DailyClosing;
use App\Models\Exchange;
use App\Models\ExchangeRate;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;
use Illuminate\Support\Str;

class ReportsController extends Controller
{
    /** Rows a report table sends at most; its totals still count everything. */
    private const ROW_LIMIT = 1000;

    private function fixFarsi($text)
    {
        if (empty($text)) return $text;

        $arabic = new Arabic();

        return $arabic->utf8Glyphs($text);
    }

    public function recalculateBalances(Request $request)
    {
        try {
            Artisan::call('balances:recalculate');

            return response()->json([
                'success' => true,
                'message' => __('reports.recalculated'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('reports.recalculate_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function customers(Request $request)
    {
        $subCategories = AccountSubCategory::all();
        $currencies = Currency::where('is_active', 1)->get();
        $company = Setting::first();
        return view('admin.reports.customers', compact('subCategories', 'currencies', 'company'));
    }

    public function customersData(Request $request)
    {
        $currencies = Currency::where('is_active', 1)
            ->orderBy('id')
            ->get(['id', 'code', 'symbol']);

        $query = Account::query()
            ->where('accounts.is_active', true)
            ->where('accounts.include_in_reports', true)
            // REMOVED: hardcoded ID 1. We use the request instead.
            ->leftJoin('account_balances', 'accounts.id', '=', 'account_balances.account_id')
            ->leftJoin('currencies', 'account_balances.currency_id', '=', 'currencies.id')
            ->selectRaw("accounts.id, accounts.name, accounts.code");

        // Apply Sub-Category filter
        if ($request->filled('sub_category_id')) {
            $query->where('accounts.account_sub_category_id', $request->sub_category_id);
        } else {
            // Optional: If you want a default category when none is selected:
            // $query->where('accounts.account_sub_category_id', 1);
        }

        if ($request->filled('customer_id')) {
            $query->where('accounts.id', $request->customer_id);
        }

        foreach ($currencies as $currency) {
            $alias = strtolower($currency->code) . '_balance';
            $query->selectRaw("
            COALESCE(SUM(
                CASE WHEN currencies.id = {$currency->id} THEN account_balances.balance ELSE 0 END
            ), 0) AS {$alias}
        ");
        }

        $query->groupBy('accounts.id', 'accounts.name', 'accounts.code')
            ->orderBy('accounts.name', 'asc');

        // Calculate Grand Totals for the footer
        $totalsData = (clone $query)->get();
        $footerTotals = [];
        foreach ($currencies as $currency) {
            $alias = strtolower($currency->code) . '_balance';
            $footerTotals[$alias] = $totalsData->sum($alias);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('account_name', function ($row) {
                return $row->name . ($row->code ? ' (' . $row->code . ')' : '');
            })
            ->with([
                'footerTotals' => $footerTotals, // Raw numbers for JS formatting
            ])
            ->make(true);
    }

    public function getCustomersBySubCategory(Request $request)
    {
        $query = Account::query()->where('is_active', true);

        if ($request->filled('sub_category_id')) {
            $query->where('account_sub_category_id', $request->sub_category_id);
        }

        return $query->select('id', 'name', 'code')->orderBy('name')->get();
    }

    public function customersPrint(Request $request)
    {
        $currencies = Currency::where('is_active', 1)
            ->orderBy('id')
            ->get(['id', 'code', 'symbol']);

        $company = Setting::first();

        $query = Account::query()
            ->where('accounts.is_active', true)
            ->where('accounts.include_in_reports', true)
            ->leftJoin('account_balances', 'accounts.id', '=', 'account_balances.account_id')
            ->leftJoin('currencies', 'account_balances.currency_id', '=', 'currencies.id')
            ->selectRaw("
            accounts.id,
            accounts.name,
            accounts.code
        ");

        /*
        |--------------------------------------------------------------------------
        | Apply Filters
        |--------------------------------------------------------------------------
        | Important:
        | Do NOT hardcode account_sub_category_id = 1 here.
        | The print page must use the same filters as the DataTable.
        */

        if ($request->filled('sub_category_id')) {
            $query->where('accounts.account_sub_category_id', $request->sub_category_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('accounts.id', $request->customer_id);
        }

        foreach ($currencies as $currency) {
            $alias = strtolower($currency->code) . '_balance';

            $query->selectRaw("
            COALESCE(SUM(
                CASE
                    WHEN currencies.id = ? THEN account_balances.balance
                    ELSE 0
                END
            ), 0) AS {$alias}
        ", [$currency->id]);
        }

        $data = $query
            ->groupBy('accounts.id', 'accounts.name', 'accounts.code')
            ->orderBy('accounts.name', 'asc')
            ->get();

        $totals = [];

        foreach ($currencies as $currency) {
            $alias = strtolower($currency->code) . '_balance';

            $totals[$alias] = [
                'code' => $currency->code,
                'symbol' => $currency->symbol,
                'amount' => (float) $data->sum($alias),
            ];
        }

        $selectedSubCategory = null;
        $selectedAccount = null;

        if ($request->filled('sub_category_id')) {
            $selectedSubCategory = AccountSubCategory::find($request->sub_category_id);
        }

        if ($request->filled('customer_id')) {
            $selectedAccount = Account::find($request->customer_id);
        }

        return view('admin.reports.exports.customers_pdf', compact(
            'data',
            'currencies',
            'totals',
            'company',
            'selectedSubCategory',
            'selectedAccount'
        ));
    }

    public function creditors(Request $request)
    {
        $currencies = Currency::where('is_active', 1)->get();
        $subCategories = AccountSubCategory::all();
        return view('admin.reports.creditors', compact('currencies', 'subCategories'));
    }

    public function creditorsData(Request $request)
    {
        $query = AccountBalance::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('account_sub_category_id', '!=', 3)
                    ->where('is_active', true)
                    ->where('include_in_reports', true);
            })
            ->where('balance', '>', 0);

        if ($request->filled('sub_category_id')) {
            $query->whereHas('account', function ($q) use ($request) {
                $q->where('account_sub_category_id', $request->sub_category_id);
            });
        }

        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }

        $query->orderBy('balance', 'desc');
        $collection = $query->get();

        $summary = $collection->groupBy('currency.code')->map(function ($items) {
            return [
                'balance' => format_amount($items->sum('balance'), 2),
                'count'   => $items->count()
            ];
        });

        $data = [];
        foreach ($collection->groupBy('account_id') as $group) {
            $account = $group->first()->account;
            $balancesHtml = '';
            foreach ($group as $balance) {
                $balancesHtml .= $balance->currency->code . ': ' . format_amount($balance->balance, 2) . '<br>';
            }
            $data[] = [
                'account_id'    => $account->id,
                'account_name'  => $account->name,
                'account_code'  => $account->code,
                'balances_html' => $balancesHtml,
            ];
        }

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('account', fn($r) => $r['account_name'] . ' (' . $r['account_code'] . ')')
            ->addColumn('balances', fn($r) => $r['balances_html'])
            ->rawColumns(['balances'])
            ->with(['totals_by_currency' => $summary])
            ->make(true);
    }

    public function creditorsPdf(Request $request)
    {
        $balances = AccountBalance::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('account_sub_category_id', '!=', 4)->where('is_active', true)->where('include_in_reports', true);
            })
            ->where('balance', '>', 0);

        if ($request->filled('sub_category_id')) {
            $balances->whereHas('account', function ($q) use ($request) {
                $q->where('account_sub_category_id', $request->sub_category_id);
            });
        }

        if ($request->filled('currency_id')) {
            $balances->where('currency_id', $request->currency_id);
        }

        $collection = $balances->orderBy('balance', 'desc')->get();

        $summary = $collection->groupBy('currency.code')->map(function ($items) {
            return [
                'balance' => format_amount($items->sum('balance'), 2),
                'count' => $items->count()
            ];
        });

        $data = $collection->groupBy('account_id')->map(function ($group) {
            $account = $group->first()->account;
            // Reshape Name
            $account->name = $this->fixFarsi($account->name);
            $currencies = $group->mapWithKeys(fn($b) => [
                $this->fixFarsi($b->currency->code) => format_amount($b->balance, 2)
            ]);
            return ['account' => $account, 'balances' => $currencies];
        });

        return PDF::loadView('admin.reports.exports.creditors_pdf', compact('data', 'summary'))
            ->setPaper('a4', 'portrait')
            ->stream('creditors_report.pdf');
    }

    public function debtors(Request $request)
    {
        $currencies = Currency::where('is_active', 1)->get();
        $subCategories = AccountSubCategory::all();
        return view('admin.reports.debtors', compact('currencies', 'subCategories'));
    }

    public function debtorsData(Request $request)
    {
        $balances = AccountBalance::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('account_sub_category_id', '!=', 3)
                    ->where('is_active', true)
                    ->where('include_in_reports', true);
            })
            ->where('balance', '<', 0);

        if ($request->filled('sub_category_id')) {
            $balances->whereHas('account', function ($q) use ($request) {
                $q->where('account_sub_category_id', $request->sub_category_id);
            });
        }

        if ($request->filled('currency_id')) {
            $balances->where('currency_id', $request->currency_id);
        }

        $collection = $balances->orderBy('balance', 'asc')->get();

        $totals = $collection->groupBy('currency.code')->map(function ($items) {
            return [
                'balance' => format_amount($items->sum('balance'), 2),
                'count'   => $items->count(),
            ];
        });

        $data = [];
        foreach ($collection->groupBy('account_id') as $group) {
            $account = $group->first()->account;
            $balancesHtml = '';
            foreach ($group as $item) {
                $balancesHtml .= "<div><strong>{$item->currency->code}:</strong> " . format_amount($item->balance, 2) . "</div>";
            }
            $data[] = [
                'account_id'    => $account->id,
                'account_name'  => $account->name,
                'account_code'  => $account->code,
                'balances_html' => $balancesHtml,
            ];
        }

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('account', fn($r) => $r['account_name'] . ' (' . $r['account_code'] . ')')
            ->addColumn('balances', fn($r) => $r['balances_html'])
            ->rawColumns(['balances'])
            ->with(['totals_by_currency' => $totals])
            ->make(true);
    }

    public function debtorsPdf(Request $request)
    {
        $balances = AccountBalance::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('account_sub_category_id', '!=', 4)->where('is_active', true)->where('include_in_reports', true);
            })
            ->where('balance', '<', 0);

        if ($request->filled('sub_category_id')) {
            $balances->whereHas('account', function ($q) use ($request) {
                $q->where('account_sub_category_id', $request->sub_category_id);
            });
        }

        if ($request->filled('currency_id')) {
            $balances->where('currency_id', $request->currency_id);
        }

        $collection = $balances->orderBy('balance', 'asc')->get();

        $summary = $collection->groupBy('currency.code')->map(function ($items) {
            return [
                'balance' => format_amount($items->sum('balance'), 2),
                'count' => $items->count()
            ];
        });

        $data = $collection->groupBy('account_id')->map(function ($group) {
            $account = $group->first()->account;
            $account->name = $this->fixFarsi($account->name);
            $currencies = $group->mapWithKeys(fn($b) => [
                $this->fixFarsi($b->currency->code) => format_amount($b->balance, 2)
            ]);
            return ['account' => $account, 'balances' => $currencies];
        });

        return PDF::loadView('admin.reports.exports.debtors_pdf', compact('data', 'summary'))
            ->setPaper('a4', 'portrait')
            ->stream('debtors_report.pdf');
    }

    public function currency()
    {
        $currencies = Currency::where('is_active', 1)->get();
        return view('admin.reports.currency', compact('currencies'));
    }

    public function currencyData(Request $request)
    {
        if (!$request->filled('currency_id')) {
            return response()->json(['credits' => [], 'debits'  => []]);
        }

        $currencyId = $request->currency_id;

        $credits = AccountBalance::with('account')
            ->where('currency_id', $currencyId)
            ->whereHas('account', function ($q) {
                $q->where('include_in_reports', true);
            })
            ->selectRaw('account_id, SUM(credit) as total_credit')
            ->groupBy('account_id')
            ->havingRaw('SUM(credit) > 0')
            ->get()
            ->map(fn($row) => [
                'account_id' => $row->account->id,
                'name'       => $row->account->name,
                'code'       => $row->account->code,
                'amount'     => format_amount($row->total_credit, 2)
            ]);

        $debits = AccountBalance::with('account')
            ->where('currency_id', $currencyId)
            ->selectRaw('account_id, SUM(debit) as total_debit')
            ->groupBy('account_id')
            ->havingRaw('SUM(debit) > 0')
            ->get()
            ->map(fn($row) => [
                'account_id' => $row->account->id,
                'name'       => $row->account->name,
                'code'       => $row->account->code,
                'amount'     => format_amount($row->total_debit, 2)
            ]);

        return response()->json(['credits' => $credits, 'debits' => $debits]);
    }

    public function currencyPdf(Request $request)
    {
        if (!$request->filled('currency_id')) {
            abort(404);
        }

        $currency = Currency::findOrFail($request->currency_id);
        $currency->code = $this->fixFarsi($currency->code);

        $credits = AccountBalance::with('account')
            ->where('currency_id', $currency->id)
            ->selectRaw('account_id, SUM(credit) as total_credit')
            ->groupBy('account_id')
            ->havingRaw('SUM(credit) > 0')
            ->get()
            ->each(function ($row) {
                $row->account->name = $this->fixFarsi($row->account->name);
            });
        $debits = AccountBalance::with('account')
            ->where('currency_id', $currency->id)
            ->selectRaw('account_id, SUM(debit) as total_debit')
            ->groupBy('account_id')
            ->havingRaw('SUM(debit) > 0')
            ->get()->each(function ($row) {
                $row->account->name = $this->fixFarsi($row->account->name);
            });
        $totalCredit = $credits->sum('total_credit');
        $totalDebit  = $debits->sum('total_debit');
        $balance     = $totalCredit - $totalDebit;

        $labels = [
            'report_title'   => __('reports.currency_title'),
            'currency'       => __('ui.currency'),
            'credits'        => __('ui.credits'),
            'debits'         => __('ui.debits'),
            'customer'       => __('ui.customer'),
            'amount'         => __('ui.amount'),
            'total'          => __('ui.total'),
            'final_balance'  => __('reports.final_balance'),
        ];
        return PDF::loadView(
            'admin.reports.exports.currency_pdf',
            compact(
                'currency',
                'credits',
                'debits',
                'totalCredit',
                'totalDebit',
                'balance',
                'labels'
            )
        )
            ->setPaper('a4', 'portrait')
            ->stream('currency_report.pdf');
    }

    public function profitLoss()
    {
        $currencies = Currency::all();
        return view('admin.reports.profit-loss', compact('currencies'));
    }

    public function profitLossData(Request $request)
    {
        $fromMonth = $request->from_month;
        $toMonth   = $request->to_month;
        $currencyId = $request->currency_id;

        // ===== SETTINGS =====
        $companyName = Setting::first()->company_name ?? 'My Company';
        $companyLogo = Setting::first()->logo;

        // ===== DATE RANGE =====
        $from = $fromMonth
            ? Carbon::parse($fromMonth)->startOfMonth()
            : null;

        $to = $toMonth
            ? Carbon::parse($toMonth)->endOfMonth()
            : null;

        // ===== INCOME =====
        $incomeRows = Transaction::query()
            ->selectRaw('currency_id, category, SUM(amount) as total')
            ->whereIn('category', ['commission', 'exchange_profit'])
            ->where('transaction_type', 'credit')
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->when($currencyId, fn($q) => $q->where('currency_id', $currencyId))
            ->groupBy('currency_id', 'category')
            ->with('currency:id,code')
            ->get();

        // ===== EXPENSE =====
        $expenseRows = Transaction::query()
            ->join('expenses', function ($join) {
                $join->on('transactions.table_row_id', '=', 'expenses.id')
                    ->where('transactions.table_name', '=', 'expenses');
            })
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('transactions.currency_id, expense_categories.name as category_name, SUM(transactions.amount) as total')
            ->where('transactions.category', 'expense')
            ->where('transactions.transaction_type', 'debit')
            ->when($from, fn($q) => $q->whereDate('transactions.created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('transactions.created_at', '<=', $to))
            ->when($currencyId, fn($q) => $q->where('transactions.currency_id', $currencyId))
            ->groupBy('transactions.currency_id', 'expense_categories.name')
            ->get();

        // ===== BUILD REPORT =====
        $report = [];

        foreach ($incomeRows as $row) {
            $cid = $row->currency_id;

            if (!isset($report[$cid])) {
                $report[$cid] = [
                    'currency' => $row->currency->code ?? 'N/A',
                    'income' => [],
                    'expenses' => [],
                    'total_income' => 0,
                    'total_expense' => 0,
                    'net_profit' => 0,
                ];
            }

            $name = $row->category == 'commission'
                ? __('reports.commission_income')
                : __('reports.exchange_profit');

            $report[$cid]['income'][] = [
                'name' => $name,
                'amount' => (float)$row->total,
            ];

            $report[$cid]['total_income'] += (float)$row->total;
        }

        foreach ($expenseRows as $row) {
            $cid = $row->currency_id;

            if (!isset($report[$cid])) {
                $currency = Currency::find($cid);

                $report[$cid] = [
                    'currency' => $currency->code ?? 'N/A',
                    'income' => [],
                    'expenses' => [],
                    'total_income' => 0,
                    'total_expense' => 0,
                    'net_profit' => 0,
                ];
            }

            $report[$cid]['expenses'][] = [
                'name' => $row->category_name ?? __('reports.expense'),
                'amount' => (float)$row->total,
            ];

            $report[$cid]['total_expense'] += (float)$row->total;
        }

        foreach ($report as &$r) {
            $r['net_profit'] = $r['total_income'] - $r['total_expense'];
        }

        // ===== PERIOD TEXT =====
        $period = __('reports.overall');

        if ($fromMonth && $toMonth) {
            if ($fromMonth == $toMonth) {
                $period = Carbon::parse($fromMonth)->locale(app()->getLocale())->translatedFormat('F Y');
            } else {
                $period =
                    Carbon::parse($fromMonth)->locale(app()->getLocale())->translatedFormat('M Y') .
                    ' → ' .
                    Carbon::parse($toMonth)->locale(app()->getLocale())->translatedFormat('M Y');
            }
        }

        return response()->json([
            'company' => $companyName,
            'logo' => $companyLogo,
            'period' => $period,
            'generated_at' => now()->format('Y-m-d H:i'),
            'reports' => array_values($report),
        ]);
    }

    public function transactions()
    {
        $accounts = \App\Models\Account::where('is_active', 1)->get();
        $currencies = \App\Models\Currency::where('is_active', 1)->get();
        $company = Setting::first();

        return view('admin.reports.transactions', compact('accounts', 'currencies', 'company'));
    }

    public function transactionsData(Request $request)
    {
        // Use a table alias or full table name to prevent "Column id in field list is ambiguous"
        $query = \App\Models\Transaction::query()
            ->where('transactions.is_visible', 1);

        // ================= FILTERS =================
        if ($request->account_id) {
            $query->where('transactions.account_id', $request->account_id);
        }
        if ($request->currency_id) {
            $query->where('transactions.currency_id', $request->currency_id);
        }
        if ($request->transaction_type) {
            $query->where('transactions.transaction_type', $request->transaction_type);
        }
        if ($request->from_date) {
            $query->where('transactions.created_at', '>=', $request->from_date . " 00:00:00");
        }
        if ($request->to_date) {
            $query->where('transactions.created_at', '<=', $request->to_date . " 23:59:59");
        }

        // ================= DYNAMIC SUMMARY =================
        $summaries = (clone $query)
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->selectRaw("
            currencies.code as final_ccy_code,
            SUM(CASE WHEN transactions.transaction_type = 'credit' THEN transactions.amount ELSE 0 END) as total_credit,
            SUM(CASE WHEN transactions.transaction_type = 'debit' THEN transactions.amount ELSE 0 END) as total_debit
        ")
            ->groupBy('currencies.code')
            ->get();

        // DEBUG: Uncomment the next line, reload the page, and check the network tab response
        // return response()->json($summaries);

        $formattedSummaries = $summaries->map(function ($item) {
            return [
                'code' => $item->final_ccy_code ?? 'N/A',
                'credit' => number_format($item->total_credit, 2),
                'debit' => number_format($item->total_debit, 2),
                'balance' => number_format($item->total_credit - $item->total_debit, 2),
                'raw_balance' => $item->total_credit - $item->total_debit
            ];
        });

        return \Yajra\DataTables\Facades\DataTables::of($query->with(['account', 'currency']))
            ->addIndexColumn()
            ->addColumn('account', fn($r) => ($r->account->name ?? '-') . ' (' . ($r->account->code ?? '-') . ')')
            ->addColumn('currency', fn($r) => $r->currency->code ?? '-')
            ->addColumn('credit', fn($r) => $r->transaction_type === 'credit' ? number_format($r->amount, 2) : '-')
            ->addColumn('debit', fn($r) => $r->transaction_type === 'debit' ? number_format($r->amount, 2) : '-')
            ->addColumn('date', fn($r) => optional($r->created_at)->format('Y-m-d H:i:s A'))
            ->with(['summaries' => $formattedSummaries])
            ->make(true);
    }

    public function transactionsExcel(Request $request)
    {
        return Excel::download(
            new TransactionsEnterpriseExport($request),
            'enterprise_transactions_report_' . now()->format('Y_m_d_His') . '.xlsx'
        );
    }

    public function balance()
    {
        $currencies = Currency::query()
            ->orderBy('code')
            ->get([
                'id',
                'name',
                'code',
                'symbol',
            ]);

        $defaultCurrency = Currency::query()
            ->where('is_default', 1)
            ->first();

        /*
     * Fallback in case your currencies table does not have
     * an is_default column or no default currency is configured.
     */
        if (!$defaultCurrency) {
            $defaultCurrency = $currencies->first();
        }

        $accounts = Account::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
            ]);

        /*
     * Replace CompanySetting with your actual company/settings model
     * if your project uses another model name.
     */
        $company = Setting::query()->first();

        return view('admin.reports.balance', compact(
            'currencies',
            'defaultCurrency',
            'accounts',
            'company'
        ));
    }
    public function balanceRates(Request $request)
    {
        $request->validate([
            'target_currency_id' => ['required', 'exists:currencies,id'],
            'date' => ['nullable', 'date'],
            'type' => ['nullable', 'in:purchase,sell'],
        ]);

        $targetCurrencyId = (int) $request->target_currency_id;

        $date = $request->filled('date')
            ? Carbon::parse($request->date)->toDateString()
            : now()->toDateString();

        $type = $request->type;

        $currencies = Currency::where('is_active', 1)
            ->orderBy('id')
            ->get();

        $rates = [];

        foreach ($currencies as $currency) {
            if ((int) $currency->id === $targetCurrencyId) {
                $rates[$currency->id] = [
                    'rate' => 1,
                    'operator' => 'same',
                    'source' => 'same_currency',
                ];

                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | Direct Rate
        |--------------------------------------------------------------------------
        | Example:
        | from AFN to USD exists directly.
        */
            $directRate = ExchangeRate::query()
                ->where('from_currency_id', $currency->id)
                ->where('to_currency_id', $targetCurrencyId)
                ->when($type, fn($q) => $q->where('type', $type))
                ->whereDate('date', '<=', $date)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->first();

            if ($directRate) {
                $rates[$currency->id] = [
                    'rate' => (float) $directRate->rate,
                    'operator' => 'multiply',
                    'source' => 'direct',
                ];

                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | Reverse Rate
        |--------------------------------------------------------------------------
        | Example:
        | target USD to AFN exists.
        | To convert AFN to USD, divide by the rate.
        */
            $reverseRate = ExchangeRate::query()
                ->where('from_currency_id', $targetCurrencyId)
                ->where('to_currency_id', $currency->id)
                ->when($type, fn($q) => $q->where('type', $type))
                ->whereDate('date', '<=', $date)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->first();

            if ($reverseRate) {
                $rates[$currency->id] = [
                    'rate' => (float) $reverseRate->rate,
                    'operator' => 'divide',
                    'source' => 'reverse',
                ];

                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | Missing Rate
        |--------------------------------------------------------------------------
        */
            $rates[$currency->id] = [
                'rate' => 0,
                'operator' => 'missing',
                'source' => 'missing',
            ];
        }

        return response()->json([
            'rates' => $rates,
        ]);
    }

    public function balanceData(Request $request)
    {
        $validated = $request->validate([
            'target_currency_id' => [
                'required',
                'exists:currencies,id',
            ],
            'date' => [
                'nullable',
                'date',
            ],
            'type' => [
                'nullable',
                'in:purchase,sell',
            ],
            'rates' => [
                'required',
                'array',
            ],
            'rates.*.rate' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'rates.*.operator' => [
                'required',
                'in:multiply,divide,same,missing',
            ],
        ]);

        $targetCurrency = Currency::query()
            ->findOrFail($validated['target_currency_id']);

        $reportDate = $request->filled('date')
            ? Carbon::parse($validated['date'])->endOfDay()
            : now()->endOfDay();

        $targetCurrencyId = (int) $targetCurrency->id;
        $rates = $validated['rates'];

        /*
        |--------------------------------------------------------------------------
        | Reserved Accounts
        |--------------------------------------------------------------------------
        |
        | 1 = Safe/Cash account
        | 2 = Profit account
        | 3 = Loss account
        | 4 = Unpaid account
        |
        */
        $safeAccountId = 1;
        $profitAccountId = 2;
        $lossAccountId = 3;
        $unpaidAccountId = 4;

        $convertAmount = function (
            float $amount,
            int $currencyId
        ) use (
            $rates,
            $targetCurrencyId
        ): array {
            $rateInput = $rates[$currencyId] ?? [];

            $rate = isset($rateInput['rate'])
                ? (float) $rateInput['rate']
                : 0;

            $operator = $rateInput['operator'] ?? 'missing';

            if ($currencyId === $targetCurrencyId) {
                return [
                    'rate' => 1,
                    'operator' => 'same',
                    'amount' => $amount,
                ];
            }

            $convertedAmount = 0;

            if ($operator === 'multiply' && $rate > 0) {
                $convertedAmount = $amount * $rate;
            } elseif ($operator === 'divide' && $rate > 0) {
                $convertedAmount = $amount / $rate;
            } elseif ($operator === 'same') {
                $convertedAmount = $amount;
            }

            return [
                'rate' => $rate,
                'operator' => $operator,
                'amount' => $convertedAmount,
            ];
        };

        /*
        |--------------------------------------------------------------------------
        | Main Currency Balances
        |--------------------------------------------------------------------------
        */
        $mainBalances = Transaction::query()
            ->join(
                'currencies',
                'transactions.currency_id',
                '=',
                'currencies.id'
            )
            ->selectRaw("
                transactions.currency_id,
                currencies.code AS currency_code,
                currencies.symbol AS currency_symbol,

                SUM(
                    CASE
                        WHEN transactions.transaction_type = 'credit'
                        THEN transactions.amount
                        ELSE 0
                    END
                ) AS total_credit,

                SUM(
                    CASE
                        WHEN transactions.transaction_type = 'debit'
                        THEN transactions.amount
                        ELSE 0
                    END
                ) AS total_debit,

                SUM(
                    CASE
                        WHEN transactions.transaction_type = 'credit'
                        THEN transactions.amount
                        WHEN transactions.transaction_type = 'debit'
                        THEN -transactions.amount
                        ELSE 0
                    END
                ) AS balance
            ")
            ->where('transactions.status', 'active')
            ->where('transactions.is_visible', 1)
            ->whereNotIn('transactions.account_id', [
                $safeAccountId,
                $profitAccountId,
                $lossAccountId,
                $unpaidAccountId,
            ])
            ->where(
                'transactions.created_at',
                '<=',
                $reportDate
            )
            ->groupBy(
                'transactions.currency_id',
                'currencies.code',
                'currencies.symbol'
            )
            ->orderBy('currencies.id')
            ->get();

        $mainRows = [];
        $mainBalanceTotal = 0;

        foreach ($mainBalances as $row) {
            $currencyId = (int) $row->currency_id;
            $balance = (float) $row->balance;

            $conversion = $convertAmount(
                $balance,
                $currencyId
            );

            $mainBalanceTotal += $conversion['amount'];

            $mainRows[] = [
                'currency_id' => $currencyId,
                'currency_code' => $row->currency_code,
                'currency_symbol' => $row->currency_symbol,
                'total_credit' => (float) $row->total_credit,
                'total_debit' => (float) $row->total_debit,
                'balance' => $balance,
                'rate' => $conversion['rate'],
                'operator' => $conversion['operator'],
                'converted_amount' => $conversion['amount'],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Profit And Loss
        |--------------------------------------------------------------------------
        */
        $profitLossBalances = Transaction::query()
            ->join(
                'currencies',
                'transactions.currency_id',
                '=',
                'currencies.id'
            )
            ->selectRaw("
            transactions.currency_id,
            currencies.code AS currency_code,
            currencies.symbol AS currency_symbol,

            SUM(
                CASE
                    WHEN transactions.account_id = {$profitAccountId}
                        AND transactions.transaction_type = 'credit'
                    THEN transactions.amount

                    WHEN transactions.account_id = {$profitAccountId}
                        AND transactions.transaction_type = 'debit'
                    THEN -transactions.amount

                    ELSE 0
                END
            ) AS total_income,

            SUM(
                CASE
                    WHEN transactions.account_id = {$lossAccountId}
                        AND transactions.transaction_type = 'debit'
                    THEN transactions.amount

                    WHEN transactions.account_id = {$lossAccountId}
                        AND transactions.transaction_type = 'credit'
                    THEN -transactions.amount

                    ELSE 0
                END
            ) AS total_expense
        ")
            ->where('transactions.status', 'active')
            ->where('transactions.is_visible', 1)
            ->whereIn('transactions.account_id', [
                $profitAccountId,
                $lossAccountId,
            ])
            ->where(
                'transactions.created_at',
                '<=',
                $reportDate
            )
            ->groupBy(
                'transactions.currency_id',
                'currencies.code',
                'currencies.symbol'
            )
            ->orderBy('currencies.id')
            ->get();

        $profitLossRows = [];
        $profitLossTotal = 0;

        foreach ($profitLossBalances as $row) {
            $currencyId = (int) $row->currency_id;
            $income = (float) $row->total_income;
            $expense = (float) $row->total_expense;
            $profitLoss = $income - $expense;

            $conversion = $convertAmount(
                $profitLoss,
                $currencyId
            );

            $profitLossTotal += $conversion['amount'];

            $profitLossRows[] = [
                'currency_id' => $currencyId,
                'currency_code' => $row->currency_code,
                'currency_symbol' => $row->currency_symbol,
                'total_income' => $income,
                'total_expense' => $expense,
                'profit_loss' => $profitLoss,
                'rate' => $conversion['rate'],
                'operator' => $conversion['operator'],
                'converted_amount' => $conversion['amount'],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Unpaid Balances
        |--------------------------------------------------------------------------
        */
        $unpaidBalances = Transaction::query()
            ->join(
                'currencies',
                'transactions.currency_id',
                '=',
                'currencies.id'
            )
            ->selectRaw("
            transactions.currency_id,
            currencies.code AS currency_code,
            currencies.symbol AS currency_symbol,

            SUM(
                CASE
                    WHEN transactions.transaction_type = 'credit'
                    THEN transactions.amount
                    ELSE 0
                END
            ) AS total_credit,

            SUM(
                CASE
                    WHEN transactions.transaction_type = 'debit'
                    THEN transactions.amount
                    ELSE 0
                END
            ) AS total_debit,

            SUM(
                CASE
                    WHEN transactions.transaction_type = 'credit'
                    THEN transactions.amount
                    WHEN transactions.transaction_type = 'debit'
                    THEN -transactions.amount
                    ELSE 0
                END
            ) AS balance
        ")
            ->where(
                'transactions.account_id',
                $unpaidAccountId
            )
            ->where('transactions.status', 'active')
            ->where('transactions.is_visible', 1)
            ->where(
                'transactions.created_at',
                '<=',
                $reportDate
            )
            ->groupBy(
                'transactions.currency_id',
                'currencies.code',
                'currencies.symbol'
            )
            ->orderBy('currencies.id')
            ->get();

        $unpaidRows = [];
        $unpaidTotal = 0;

        foreach ($unpaidBalances as $row) {
            $currencyId = (int) $row->currency_id;
            $balance = (float) $row->balance;

            $conversion = $convertAmount(
                $balance,
                $currencyId
            );

            $unpaidTotal += $conversion['amount'];

            $unpaidRows[] = [
                'currency_id' => $currencyId,
                'currency_code' => $row->currency_code,
                'currency_symbol' => $row->currency_symbol,
                'total_credit' => (float) $row->total_credit,
                'total_debit' => (float) $row->total_debit,
                'balance' => $balance,
                'rate' => $conversion['rate'],
                'operator' => $conversion['operator'],
                'converted_amount' => $conversion['amount'],
            ];
        }

        /*
    |--------------------------------------------------------------------------
    | Final Total
    |--------------------------------------------------------------------------
    */
        $finalTotal =
            $mainBalanceTotal +
            $profitLossTotal +
            $unpaidTotal;

        /*
    |--------------------------------------------------------------------------
    | Current Day Closing
    |--------------------------------------------------------------------------
    */
        $currentClosing = DailyClosing::query()
            ->with('closedBy')
            ->whereDate(
                'closing_date',
                $reportDate->toDateString()
            )
            ->first();

        /*
    |--------------------------------------------------------------------------
    | Previous Closing
    |--------------------------------------------------------------------------
    */
        $previousClosingRecord = DailyClosing::query()
            ->whereDate(
                'closing_date',
                '<',
                $reportDate->toDateString()
            )
            ->latest('closing_date')
            ->first();

        $previousClosing = null;

        if ($previousClosingRecord) {
            $previousProfitLoss =
                (float) $previousClosingRecord->profit_balance -
                (float) $previousClosingRecord->loss_balance;

            $previousClosing = [
                'id' => $previousClosingRecord->id,
                'closing_date' => Carbon::parse(
                    $previousClosingRecord->closing_date
                )->format('Y-m-d'),
                'main_balance_total' => (float) $previousClosingRecord->cash_balance,
                'cash_balance' => (float) $previousClosingRecord->cash_balance,
                'profit_loss_total' => $previousProfitLoss,
                'profit_balance' => (float) $previousClosingRecord->profit_balance,
                'loss_balance' => (float) $previousClosingRecord->loss_balance,
                'unpaid_total' => (float) $previousClosingRecord->unpaid_balance,
                'unpaid_balance' => (float) $previousClosingRecord->unpaid_balance,
                'final_total' => (float) $previousClosingRecord->final_balance,
                'final_balance' => (float) $previousClosingRecord->final_balance,
            ];
        }

        return response()->json([
            'date' => $reportDate->format('Y-m-d'),

            'target_currency' => [
                'id' => $targetCurrency->id,
                'code' => $targetCurrency->code,
                'symbol' => $targetCurrency->symbol,
            ],

            'main_rows' => $mainRows,
            'profit_loss_rows' => $profitLossRows,
            'unpaid_rows' => $unpaidRows,

            'summary' => [
                'main_balance_total' => round(
                    $mainBalanceTotal,
                    6
                ),
                'profit_loss_total' => round(
                    $profitLossTotal,
                    6
                ),
                'unpaid_total' => round(
                    $unpaidTotal,
                    6
                ),
                'final_total' => round(
                    $finalTotal,
                    6
                ),
                'target_currency_code' => $targetCurrency->code,
            ],

            'previous_closing' => $previousClosing,

            'closing' => [
                'is_closed' => (bool) $currentClosing,
                'id' => $currentClosing?->id,
                'closed_at' => $currentClosing?->created_at
                    ? $currentClosing->created_at->format('Y-m-d H:i:s')
                    : null,
                'closed_by' => $currentClosing?->closedBy?->name,
            ],
        ]);
    }

    private function convertBalanceAmount(float $amount, float $rate, string $operator): float
    {
        if ($operator === 'same') {
            return $amount;
        }

        if ($operator === 'multiply') {
            return $amount * $rate;
        }

        if ($operator === 'divide') {
            if ($rate <= 0) {
                return 0;
            }

            return $amount / $rate;
        }

        return 0;
    }

    public function balancePrint(Request $request)
    {
        $request->validate([
            'target_currency_id' => ['required', 'exists:currencies,id'],
            'date' => ['nullable', 'date'],
            'rates' => ['required', 'array'],
            'rates.*.rate' => ['nullable', 'numeric', 'min:0'],
            'rates.*.operator' => ['required', 'in:multiply,divide,same,missing'],
        ]);

        $targetCurrency = Currency::findOrFail($request->target_currency_id);

        $date = $request->filled('date')
            ? Carbon::parse($request->date)->endOfDay()
            : now()->endOfDay();

        $company = Setting::first();

        $balances = Transaction::query()
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->selectRaw("
                transactions.currency_id,
                currencies.code as currency_code,
                currencies.symbol as currency_symbol,

                SUM(CASE
                    WHEN transactions.transaction_type = 'credit'
                    THEN transactions.amount
                    ELSE 0
                END) as total_credit,

                SUM(CASE
                    WHEN transactions.transaction_type = 'debit'
                    THEN transactions.amount
                    ELSE 0
                END) as total_debit,

                SUM(CASE
                    WHEN transactions.transaction_type = 'credit'
                    THEN transactions.amount
                    WHEN transactions.transaction_type = 'debit'
                    THEN -transactions.amount
                    ELSE 0
                END) as balance
            ")
            ->where('transactions.status', 'active')
            ->where('transactions.is_visible', 1)

            // Exclude safe account only
            ->whereNotIn('transactions.account_id', [1, 2, 3, 4])

            ->where('transactions.created_at', '<=', $date)
            ->groupBy(
                'transactions.currency_id',
                'currencies.code',
                'currencies.symbol'
            )
            ->orderBy('currencies.id')
            ->get();

        $rows = [];
        $finalTotal = 0;

        $creditRealByCurrency = [];
        $debitRealByCurrency = [];

        $totalCreditConverted = 0;
        $totalDebitConverted = 0;

        foreach ($balances as $row) {
            $currencyId = (int) $row->currency_id;

            $credit = (float) $row->total_credit;
            $debit = (float) $row->total_debit;
            $balance = (float) $row->balance;

            $rateInput = $request->rates[$currencyId] ?? null;

            $rate = isset($rateInput['rate'])
                ? (float) $rateInput['rate']
                : 0;

            $operator = $rateInput['operator'] ?? 'missing';

            if ($currencyId === (int) $targetCurrency->id) {
                $rate = 1;
                $operator = 'same';
            }

            $convertedCredit = $this->convertBalanceAmount($credit, $rate, $operator);
            $convertedDebit = $this->convertBalanceAmount($debit, $rate, $operator);
            $convertedBalance = $this->convertBalanceAmount($balance, $rate, $operator);

            $finalTotal += $convertedBalance;
            $totalCreditConverted += $convertedCredit;
            $totalDebitConverted += $convertedDebit;

            if ($credit != 0) {
                $creditRealByCurrency[$row->currency_code] = ($creditRealByCurrency[$row->currency_code] ?? 0) + $credit;
            }

            if ($debit != 0) {
                $debitRealByCurrency[$row->currency_code] = ($debitRealByCurrency[$row->currency_code] ?? 0) + $debit;
            }

            $rows[] = [
                'currency_id' => $currencyId,
                'currency_code' => $row->currency_code,
                'currency_symbol' => $row->currency_symbol,
                'total_credit' => $credit,
                'total_debit' => $debit,
                'balance' => $balance,
                'rate' => $rate,
                'operator' => $operator,
                'converted_credit' => $convertedCredit,
                'converted_debit' => $convertedDebit,
                'converted_amount' => $convertedBalance,
            ];
        }

        $activeCurrencies = collect($rows)
            ->filter(fn($row) => (float) $row['balance'] !== 0.0)
            ->count();

        $html = view('admin.reports.exports.balance_pdf', [
            'company' => $company,
            'rows' => $rows,
            'targetCurrency' => $targetCurrency,
            'date' => $date,
            'finalTotal' => $finalTotal,
            'creditRealByCurrency' => $creditRealByCurrency,
            'debitRealByCurrency' => $debitRealByCurrency,
            'totalCreditConverted' => $totalCreditConverted,
            'totalDebitConverted' => $totalDebitConverted,
            'activeCurrencies' => $activeCurrencies,
        ])->render();

        $tempDir = storage_path('app/mpdf-temp');

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'tempDir' => $tempDir,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'default_font' => 'dejavusans',
        ]);

        $mpdf->SetTitle(__('reports.balance_title') . ' - ' . $date->format('Y-m-d'));
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('balance_report_' . $date->format('Y_m_d') . '.pdf', 'I'))
            ->header('Content-Type', 'application/pdf');
    }

    public function closeDay(Request $request)
{
    $validated = $request->validate([
        'closing_date' => [
            'required',
            'date',
            'before_or_equal:today',
        ],
        'target_currency_id' => [
            'required',
            'exists:currencies,id',
        ],
        'carry_forward_account_id' => [
            'required',
            'exists:accounts,id',
        ],
        'profit_loss_carry_account_id' => [
            'required',
            'exists:accounts,id',
            'different:carry_forward_account_id',
        ],
        'remarks' => [
            'nullable',
            'string',
            'max:1000',
        ],
        'rates' => [
            'required',
            'array',
        ],
        'rates.*.rate' => [
            'nullable',
            'numeric',
            'min:0',
        ],
        'rates.*.operator' => [
            'required',
            'in:multiply,divide,same,missing',
        ],
    ]);

    $closingDate = Carbon::parse(
        $validated['closing_date']
    )->endOfDay();

    $targetCurrency = Currency::query()
        ->findOrFail($validated['target_currency_id']);

    $existingClosing = DailyClosing::query()
        ->whereDate(
            'closing_date',
            $closingDate->toDateString()
        )
        ->exists();

    if ($existingClosing) {
        return response()->json([
            'message' => __('reports.already_closed'),
        ], 422);
    }

    $safeAccountId = 1;
    $profitAccountId = 2;
    $lossAccountId = 3;
    $unpaidAccountId = 4;

    $rates = $validated['rates'];
    $targetCurrencyId = (int) $targetCurrency->id;

    $convertAmount = function (
        float $amount,
        int $currencyId
    ) use (
        $rates,
        $targetCurrencyId
    ): float {
        if ($currencyId === $targetCurrencyId) {
            return $amount;
        }

        $rateInput = $rates[$currencyId] ?? [];

        $rate = isset($rateInput['rate'])
            ? (float) $rateInput['rate']
            : 0;

        $operator = $rateInput['operator'] ?? 'missing';

        if ($operator === 'multiply' && $rate > 0) {
            return $amount * $rate;
        }

        if ($operator === 'divide' && $rate > 0) {
            return $amount / $rate;
        }

        if ($operator === 'same') {
            return $amount;
        }

        return 0;
    };

    return DB::transaction(function () use (
        $validated,
        $closingDate,
        $targetCurrency,
        $convertAmount,
        $safeAccountId,
        $profitAccountId,
        $lossAccountId,
        $unpaidAccountId
    ) {
        /*
        |--------------------------------------------------------------------------
        | Main Balance
        |--------------------------------------------------------------------------
        */
        $mainBalances = Transaction::query()
            ->selectRaw("
                currency_id,

                SUM(
                    CASE
                        WHEN transaction_type = 'credit'
                        THEN amount
                        WHEN transaction_type = 'debit'
                        THEN -amount
                        ELSE 0
                    END
                ) AS balance
            ")
            ->where('status', 'active')
            ->where('is_visible', 1)
            ->whereNotIn('account_id', [
                $profitAccountId,
                $lossAccountId,
                $unpaidAccountId,
            ])
            ->where(
                'created_at',
                '<=',
                $closingDate
            )
            ->groupBy('currency_id')
            ->get();

        $mainBalanceTotal = 0;
        $currencyBalances = [];

        foreach ($mainBalances as $row) {
            $currencyId = (int) $row->currency_id;
            $balance = (float) $row->balance;

            $convertedAmount = $convertAmount(
                $balance,
                $currencyId
            );

            $mainBalanceTotal += $convertedAmount;

            $currencyBalances[$currencyId] = [
                'currency_id' => $currencyId,
                'balance' => $balance,
                'converted_amount' => $convertedAmount,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Profit Balance
        |--------------------------------------------------------------------------
        */
        $profitBalances = Transaction::query()
            ->selectRaw("
                currency_id,

                SUM(
                    CASE
                        WHEN transaction_type = 'credit'
                        THEN amount
                        WHEN transaction_type = 'debit'
                        THEN -amount
                        ELSE 0
                    END
                ) AS balance
            ")
            ->where('account_id', $profitAccountId)
            ->where('status', 'active')
            ->where('is_visible', 1)
            ->where(
                'created_at',
                '<=',
                $closingDate
            )
            ->groupBy('currency_id')
            ->get();

        $profitBalanceTotal = 0;

        foreach ($profitBalances as $row) {
            $profitBalanceTotal += $convertAmount(
                (float) $row->balance,
                (int) $row->currency_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Loss Balance
        |--------------------------------------------------------------------------
        */
        $lossBalances = Transaction::query()
            ->selectRaw("
                currency_id,

                SUM(
                    CASE
                        WHEN transaction_type = 'debit'
                        THEN amount
                        WHEN transaction_type = 'credit'
                        THEN -amount
                        ELSE 0
                    END
                ) AS balance
            ")
            ->where('account_id', $lossAccountId)
            ->where('status', 'active')
            ->where('is_visible', 1)
            ->where(
                'created_at',
                '<=',
                $closingDate
            )
            ->groupBy('currency_id')
            ->get();

        $lossBalanceTotal = 0;

        foreach ($lossBalances as $row) {
            $lossBalanceTotal += $convertAmount(
                (float) $row->balance,
                (int) $row->currency_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Unpaid Balance
        |--------------------------------------------------------------------------
        */
        $unpaidBalances = Transaction::query()
            ->selectRaw("
                currency_id,

                SUM(
                    CASE
                        WHEN transaction_type = 'credit'
                        THEN amount
                        WHEN transaction_type = 'debit'
                        THEN -amount
                        ELSE 0
                    END
                ) AS balance
            ")
            ->where('account_id', $unpaidAccountId)
            ->where('status', 'active')
            ->where('is_visible', 1)
            ->where(
                'created_at',
                '<=',
                $closingDate
            )
            ->groupBy('currency_id')
            ->get();

        $unpaidBalanceTotal = 0;

        foreach ($unpaidBalances as $row) {
            $unpaidBalanceTotal += $convertAmount(
                (float) $row->balance,
                (int) $row->currency_id
            );
        }

        $profitLossTotal =
            $profitBalanceTotal -
            $lossBalanceTotal;

        $finalBalance =
            $mainBalanceTotal +
            $profitLossTotal +
            $unpaidBalanceTotal;

        /*
        |--------------------------------------------------------------------------
        | Save Closing Snapshot
        |--------------------------------------------------------------------------
        */
        $dailyClosing = DailyClosing::query()->create([
            'closing_date' => $closingDate->toDateString(),
            'closed_by' => auth()->id(),
            'cash_balance' => round($mainBalanceTotal, 6),
            'profit_balance' => round($profitBalanceTotal, 6),
            'loss_balance' => round($lossBalanceTotal, 6),
            'unpaid_balance' => round($unpaidBalanceTotal, 6),
            'final_balance' => round($finalBalance, 6),
            'currency_balances' => $currencyBalances,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Create Carry-Forward Transactions
        |--------------------------------------------------------------------------
        */
        $linkedId = (string) Str::uuid();

        if (abs($mainBalanceTotal) > 0.000001) {
            Transaction::query()->create([
                'linked_id' => $linkedId,
                'type' => 'journal',
                'account_id' => $validated['carry_forward_account_id'],
                'currency_id' => $targetCurrency->id,
                'amount' => abs($mainBalanceTotal),
                'transaction_type' => $mainBalanceTotal >= 0
                    ? 'credit'
                    : 'debit',
                'is_cash' => 0,
                'status' => 'active',
                'is_visible' => 1,
                'description' => 'Main balance carry-forward for ' .
                    $closingDate->format('Y-m-d'),
                'created_at' => $closingDate->copy()->addSecond(),
                'updated_at' => now(),
            ]);
        }

        if (abs($profitLossTotal) > 0.000001) {
            Transaction::query()->create([
                'linked_id' => $linkedId,
                'type' => 'journal',
                'account_id' => $validated['profit_loss_carry_account_id'],
                'currency_id' => $targetCurrency->id,
                'amount' => abs($profitLossTotal),
                'transaction_type' => $profitLossTotal >= 0
                    ? 'credit'
                    : 'debit',
                'is_cash' => 0,
                'status' => 'active',
                'is_visible' => 1,
                'description' => 'Profit/loss carry-forward for ' .
                    $closingDate->format('Y-m-d'),
                'created_at' => $closingDate->copy()->addSeconds(2),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => __('reports.closed_success'),
            'closing' => [
                'id' => $dailyClosing->id,
                'closing_date' => $dailyClosing->closing_date,
                'final_balance' => $dailyClosing->final_balance,
            ],
        ]);
    });
}

    public function customerExchangeReport()
    {
        $accounts = Account::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        $currencies = Currency::query()
            ->orderBy('id')
            ->get();

        return view('admin.reports.customer-exchange-report', compact(
            'accounts',
            'currencies'
        ));
    }

    public function customerExchangeReportData(Request $request)
    {
        // totals are counted by the database over every matching exchange; the table gets the
        // newest ROW_LIMIT rows, so the answer stays small and fast with years of exchanges
        $base = Exchange::query();
        $this->applyExchangeTableReportFilters($base, $request);

        $s = (clone $base)->toBase()->selectRaw("COUNT(*) AS total_records,
            SUM(CASE WHEN type = 'purchase' THEN 1 ELSE 0 END) AS purchase_count,
            SUM(CASE WHEN type = 'sell' THEN 1 ELSE 0 END) AS sell_count,
            SUM(CASE WHEN mode = 'cash' THEN 1 ELSE 0 END) AS cash_count,
            SUM(CASE WHEN mode = 'customer' THEN 1 ELSE 0 END) AS customer_count,
            COALESCE(SUM(usd_amount), 0) AS total_usd_amount,
            COALESCE(SUM(profit), 0) AS total_profit")->first();
        $summary = [
            'total_records' => (int) $s->total_records,
            'purchase_count' => (int) $s->purchase_count,
            'sell_count' => (int) $s->sell_count,
            'cash_count' => (int) $s->cash_count,
            'customer_count' => (int) $s->customer_count,
            'total_usd_amount' => (float) $s->total_usd_amount,
            'total_profit' => (float) $s->total_profit,
        ];

        $currencies = Currency::query()->get(['id', 'name', 'code'])->keyBy('id');
        $label = fn ($id) => $this->exchangeCurrencyLabel($currencies->get($id));
        $pairs = $this->exchangePairTotals(clone $base, $label);

        $exchanges = (clone $base)
            ->with(['account:id,name,code'])
            ->orderByDesc('date')->orderByDesc('created_at')->orderByDesc('id')
            ->limit(self::ROW_LIMIT)
            ->get();

        $rows = $exchanges->map(fn ($exchange) => [
            'id' => $exchange->id,
            'date_time' => optional($exchange->date)->format('Y-m-d') . ' ' . optional($exchange->created_at)->format('H:i'),
            'customer_name' => optional($exchange->account)->name ?? '-',
            'customer_code' => optional($exchange->account)->code ?? '-',
            'type' => $exchange->type,
            'from_currency' => $label($exchange->from_currency_id),
            'to_currency' => $label($exchange->to_currency_id),
            'from_amount' => (float) $exchange->from_amount,
            'to_amount' => (float) $exchange->to_amount,
            'rate' => (float) $exchange->rate,
        ])->all();

        return response()->json([
            'rows' => $rows,
            'rows_limited' => $summary['total_records'] > count($rows),
            'summary' => $summary,
            'currency_pair_totals' => array_values($pairs),
        ]);
    }

    /** From-currency to to-currency totals of the exchanges in $query, counted by the database. */
    private function exchangePairTotals($query, callable $label): array
    {
        return $query->toBase()
            ->groupBy('from_currency_id', 'to_currency_id')
            ->orderByRaw('MAX(date) DESC')
            ->selectRaw('from_currency_id, to_currency_id,
                COALESCE(SUM(from_amount), 0) AS from_total, COALESCE(SUM(to_amount), 0) AS to_total,
                COALESCE(SUM(rate), 0) AS rate_total, COUNT(*) AS records')
            ->get()
            ->map(fn ($p) => [
                'from_currency_id' => (int) $p->from_currency_id,
                'from_currency' => $label($p->from_currency_id),
                'to_currency' => $label($p->to_currency_id),
                'from_total' => (float) $p->from_total,
                'to_total' => (float) $p->to_total,
                'rate_total' => (float) $p->rate_total,
                'rate_count' => (int) $p->records,
                'average_rate' => $p->records > 0 ? (float) $p->rate_total / $p->records : 0,
                'records' => (int) $p->records,
            ])->all();
    }

    public function customerExchangeReportPrint(Request $request)
    {
        $query = Exchange::query()
            ->with([
                'account:id,name,code',
                'fromCurrency:id,name,code,symbol',
                'toCurrency:id,name,code,symbol',
            ]);

        $this->applyExchangeTableReportFilters($query, $request);

        $exchanges = $query
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $rows = [];

        $summary = [
            'total_records' => 0,
            'purchase_count' => 0,
            'sell_count' => 0,
            'cash_count' => 0,
            'customer_count' => 0,
            'total_profit' => 0,
        ];

        $currencyPairTotals = [];

        foreach ($exchanges as $exchange) {
            $fromCurrency = $this->exchangeCurrencyLabel($exchange->fromCurrency);
            $toCurrency = $this->exchangeCurrencyLabel($exchange->toCurrency);

            $pairKey = $exchange->from_currency_id . '_' . $exchange->to_currency_id;

            if (!isset($currencyPairTotals[$pairKey])) {
                $currencyPairTotals[$pairKey] = [
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'from_total' => 0,
                    'to_total' => 0,
                    'rate_total' => 0,
                    'rate_count' => 0,
                    'average_rate' => 0,
                    'records' => 0,
                ];
            }

            $currencyPairTotals[$pairKey]['from_total'] += (float) $exchange->from_amount;
            $currencyPairTotals[$pairKey]['to_total'] += (float) $exchange->to_amount;
            $currencyPairTotals[$pairKey]['rate_total'] += (float) $exchange->rate;
            $currencyPairTotals[$pairKey]['rate_count']++;
            $currencyPairTotals[$pairKey]['records']++;

            if ($exchange->type === 'purchase') {
                $summary['purchase_count']++;
            }

            if ($exchange->type === 'sell') {
                $summary['sell_count']++;
            }

            if ($exchange->mode === 'cash') {
                $summary['cash_count']++;
            }

            if ($exchange->mode === 'customer') {
                $summary['customer_count']++;
            }

            $summary['total_profit'] += (float) ($exchange->profit ?? 0);
            $summary['total_records']++;

            $rows[] = [
                'date_time' => optional($exchange->date)->format('Y-m-d') . ' ' . optional($exchange->created_at)->format('H:i'),
                'customer_name' => optional($exchange->account)->name ?? '-',
                'customer_code' => optional($exchange->account)->code ?? '-',
                'type' => $exchange->type,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'from_amount' => (float) $exchange->from_amount,
                'to_amount' => (float) $exchange->to_amount,
                'rate' => (float) $exchange->rate,
            ];
        }

        foreach ($currencyPairTotals as $key => $item) {
            $currencyPairTotals[$key]['average_rate'] = $item['rate_count'] > 0
                ? $item['rate_total'] / $item['rate_count']
                : 0;
        }

        $currencyPairTotals = array_values($currencyPairTotals);

        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'account_id' => $request->account_id,
            'currency_id' => $request->currency_id,
            'type' => $request->type,
            'mode' => $request->mode,
        ];

        $html = view('admin.reports.pdf.customer-exchange-report-pdf', compact(
            'rows',
            'summary',
            'currencyPairTotals',
            'filters'
        ))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => storage_path('app/mpdf-temp'),
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_left' => 8,
            'margin_right' => 8,
        ]);

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('customer-exchange-report.pdf', 'I'))
            ->header('Content-Type', 'application/pdf');
    }

    private function applyExchangeTableReportFilters($query, Request $request): void
    {
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('currency_id')) {
            $currencyId = $request->currency_id;

            $query->where(function ($q) use ($currencyId) {
                $q->where('from_currency_id', $currencyId)
                    ->orWhere('to_currency_id', $currencyId);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->mode);
        }
    }

    private function exchangeCurrencyLabel($currency): string
    {
        if (!$currency) {
            return '-';
        }

        return $currency->code ?: $currency->name;
    }

    public function currencyExchangeReport(Request $request)
    {
        $currencies = Currency::query()
            ->where('is_active', 1)
            ->orderByRaw("CASE WHEN code = 'USD' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();

        return view('admin.reports.currency-exchange-report', compact('currencies'));
    }

    public function currencyExchangeReportData(Request $request)
    {
        $currencies = Currency::query()
            ->where('is_active', 1)
            ->orderByRaw("CASE WHEN code = 'USD' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();
        $all = Currency::query()->get(['id', 'name', 'code'])->keyBy('id');
        $label = fn ($id) => $this->exchangeCurrencyLabel($all->get($id));

        $base = Exchange::query()->whereIn('from_currency_id', $currencies->pluck('id'));
        $this->applyCurrencyExchangeTabFilters($base, $request);

        // per From currency, counted by the database over every matching exchange
        $perTab = (clone $base)->toBase()->groupBy('from_currency_id')->selectRaw("from_currency_id, COUNT(*) AS records,
            SUM(CASE WHEN type = 'purchase' THEN 1 ELSE 0 END) AS purchase_count,
            SUM(CASE WHEN type = 'sell' THEN 1 ELSE 0 END) AS sell_count,
            SUM(CASE WHEN mode = 'cash' THEN 1 ELSE 0 END) AS cash_count,
            SUM(CASE WHEN mode = 'customer' THEN 1 ELSE 0 END) AS customer_count,
            COALESCE(SUM(profit), 0) AS profit")->get()->keyBy('from_currency_id');
        $pairs = collect($this->exchangePairTotals(clone $base, $label))->groupBy('from_currency_id');

        $tabs = [];
        $globalSummary = ['total_records' => 0, 'active_tabs' => 0, 'purchase_count' => 0, 'sell_count' => 0, 'cash_count' => 0, 'customer_count' => 0, 'total_profit' => 0];

        foreach ($currencies as $currency) {
            $t = $perTab->get($currency->id);
            $summary = [
                'records' => (int) ($t->records ?? 0),
                'purchase_count' => (int) ($t->purchase_count ?? 0),
                'sell_count' => (int) ($t->sell_count ?? 0),
                'cash_count' => (int) ($t->cash_count ?? 0),
                'customer_count' => (int) ($t->customer_count ?? 0),
                'profit' => (float) ($t->profit ?? 0),
            ];
            $rows = [];
            if ($summary['records'] > 0) {
                $globalSummary['active_tabs']++;
                $rows = (clone $base)->where('from_currency_id', $currency->id)
                    ->with(['account:id,name,code'])
                    ->orderByDesc('date')->orderByDesc('created_at')->orderByDesc('id')
                    ->limit(self::ROW_LIMIT)->get()
                    ->map(fn ($exchange) => [
                        'date_time' => optional($exchange->date)->format('Y-m-d') . ' ' . optional($exchange->created_at)->format('H:i'),
                        'customer_name' => optional($exchange->account)->name ?? '-',
                        'customer_code' => optional($exchange->account)->code ?? '-',
                        'type' => $exchange->type,
                        'mode' => $exchange->mode,
                        'from_currency' => $label($exchange->from_currency_id),
                        'to_currency' => $label($exchange->to_currency_id),
                        'from_amount' => (float) $exchange->from_amount,
                        'to_amount' => (float) $exchange->to_amount,
                        'rate' => (float) $exchange->rate,
                        'profit' => (float) ($exchange->profit ?? 0),
                    ])->all();
            }
            foreach (['purchase_count', 'sell_count', 'cash_count', 'customer_count'] as $k) {
                $globalSummary[$k] += $summary[$k];
            }
            $globalSummary['total_records'] += $summary['records'];
            $globalSummary['total_profit'] += $summary['profit'];

            $tabs[] = [
                'currency_id' => $currency->id,
                'currency_code' => $currency->code ?: $currency->name,
                'currency_name' => $currency->name,
                'rows' => $rows,
                'rows_limited' => $summary['records'] > count($rows),
                'totals' => collect($pairs->get($currency->id, []))->map(fn ($p) => collect($p)->except('from_currency_id')->all())->values()->all(),
                'summary' => $summary,
            ];
        }

        return response()->json([
            'tabs' => $tabs,
            'summary' => $globalSummary,
        ]);
    }

    public function currencyExchangeReportPrint(Request $request)
    {
        $currencies = Currency::query()
            ->where('is_active', 1)
            ->orderByRaw("CASE WHEN code = 'USD' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();

        $query = Exchange::query()
            ->with([
                'account:id,name,code',
                'fromCurrency:id,name,code,symbol',
                'toCurrency:id,name,code,symbol',
            ]);

        $this->applyCurrencyExchangeTabFilters($query, $request);

        $exchanges = $query
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $tabs = [];

        foreach ($currencies as $currency) {
            $tabs[$currency->id] = [
                'currency_id' => $currency->id,
                'currency_code' => $currency->code ?: $currency->name,
                'currency_name' => $currency->name,
                'rows' => [],
                'totals' => [],
                'summary' => [
                    'records' => 0,
                    'purchase_count' => 0,
                    'sell_count' => 0,
                    'cash_count' => 0,
                    'customer_count' => 0,
                    'profit' => 0,
                ],
            ];
        }

        $globalSummary = [
            'total_records' => 0,
            'active_tabs' => 0,
            'purchase_count' => 0,
            'sell_count' => 0,
            'cash_count' => 0,
            'customer_count' => 0,
            'total_profit' => 0,
        ];

        foreach ($exchanges as $exchange) {
            $fromCurrencyId = $exchange->from_currency_id;

            if (!isset($tabs[$fromCurrencyId])) {
                continue;
            }

            $fromCurrency = $this->exchangeCurrencyLabel($exchange->fromCurrency);
            $toCurrency = $this->exchangeCurrencyLabel($exchange->toCurrency);

            $pairKey = $exchange->from_currency_id . '_' . $exchange->to_currency_id;

            if (!isset($tabs[$fromCurrencyId]['totals'][$pairKey])) {
                $tabs[$fromCurrencyId]['totals'][$pairKey] = [
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'from_total' => 0,
                    'to_total' => 0,
                    'rate_total' => 0,
                    'rate_count' => 0,
                    'average_rate' => 0,
                    'records' => 0,
                ];
            }

            $tabs[$fromCurrencyId]['totals'][$pairKey]['from_total'] += (float) $exchange->from_amount;
            $tabs[$fromCurrencyId]['totals'][$pairKey]['to_total'] += (float) $exchange->to_amount;
            $tabs[$fromCurrencyId]['totals'][$pairKey]['rate_total'] += (float) $exchange->rate;
            $tabs[$fromCurrencyId]['totals'][$pairKey]['rate_count']++;
            $tabs[$fromCurrencyId]['totals'][$pairKey]['records']++;

            $tabs[$fromCurrencyId]['rows'][] = [
                'date_time' => optional($exchange->date)->format('Y-m-d') . ' ' . optional($exchange->created_at)->format('H:i'),
                'customer_name' => optional($exchange->account)->name ?? '-',
                'customer_code' => optional($exchange->account)->code ?? '-',
                'type' => $exchange->type,
                'mode' => $exchange->mode,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'from_amount' => (float) $exchange->from_amount,
                'to_amount' => (float) $exchange->to_amount,
                'rate' => (float) $exchange->rate,
                'profit' => (float) ($exchange->profit ?? 0),
            ];

            $tabs[$fromCurrencyId]['summary']['records']++;
            $tabs[$fromCurrencyId]['summary']['profit'] += (float) ($exchange->profit ?? 0);

            if ($exchange->type === 'purchase') {
                $tabs[$fromCurrencyId]['summary']['purchase_count']++;
                $globalSummary['purchase_count']++;
            }

            if ($exchange->type === 'sell') {
                $tabs[$fromCurrencyId]['summary']['sell_count']++;
                $globalSummary['sell_count']++;
            }

            if ($exchange->mode === 'cash') {
                $tabs[$fromCurrencyId]['summary']['cash_count']++;
                $globalSummary['cash_count']++;
            }

            if ($exchange->mode === 'customer') {
                $tabs[$fromCurrencyId]['summary']['customer_count']++;
                $globalSummary['customer_count']++;
            }

            $globalSummary['total_records']++;
            $globalSummary['total_profit'] += (float) ($exchange->profit ?? 0);
        }

        foreach ($tabs as $currencyId => $tab) {
            foreach ($tab['totals'] as $pairKey => $total) {
                $tabs[$currencyId]['totals'][$pairKey]['average_rate'] = $total['rate_count'] > 0
                    ? $total['rate_total'] / $total['rate_count']
                    : 0;
            }

            $tabs[$currencyId]['totals'] = array_values($tabs[$currencyId]['totals']);

            if ($tabs[$currencyId]['summary']['records'] > 0) {
                $globalSummary['active_tabs']++;
            }
        }

        $tabs = array_values($tabs);

        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'currency_id' => $request->currency_id,
            'type' => $request->type,
            'mode' => $request->mode,
        ];

        $html = view('admin.reports.pdf.currency-exchange-report-pdf', compact(
            'tabs',
            'globalSummary',
            'filters'
        ))->render();

        $tempDir = storage_path('app/mpdf-temp');

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => $tempDir,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_left' => 8,
            'margin_right' => 8,
            'default_font' => 'dejavusans',
        ]);

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('currency-exchange-report.pdf', 'I'))
            ->header('Content-Type', 'application/pdf');
    }

    private function applyCurrencyExchangeTabFilters($query, Request $request): void
    {
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        /*
    |--------------------------------------------------------------------------
    | Currency filter
    |--------------------------------------------------------------------------
    | Since this report is tab-wise by From Currency,
    | currency_id filters the From Currency tab.
    */
        if ($request->filled('currency_id')) {
            $query->where('from_currency_id', $request->currency_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->mode);
        }
    }

    /**
     * Shared report helpers
     */
    private function applyExchangeTransactionScope($query): void
    {
        $query->where(function ($q) {
            $q->whereIn('table_name', ['exchange', 'exchanges'])
                ->orWhere('category', 'exchange')
                ->orWhere(function ($qq) {
                    $qq->whereNotNull('payment_currency_id')
                        ->whereNotNull('actual_currency_id')
                        ->whereColumn('payment_currency_id', '!=', 'actual_currency_id');
                });
        });
    }

    private function applyExchangeReportFilters($query, Request $request): void
    {
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('currency_id')) {
            $currencyId = $request->currency_id;

            $query->where(function ($q) use ($currencyId) {
                $q->where('payment_currency_id', $currencyId)
                    ->orWhere('actual_currency_id', $currencyId)
                    ->orWhere('currency_id', $currencyId);
            });
        }

        if ($request->filled('exchange_type')) {
            if ($request->exchange_type === 'purchase') {
                $query->where('transaction_type', 'debit');
            } elseif ($request->exchange_type === 'sell') {
                $query->where('transaction_type', 'credit');
            }
        }
    }

    private function getDefaultReportCurrency($currencies)
    {
        $collection = collect($currencies);

        return $collection->first(function ($currency) {
            return strtoupper($currency->code ?? '') === 'USD'
                || strtoupper($currency->name ?? '') === 'USD'
                || strtoupper($currency->name ?? '') === 'DOLLAR'
                || str_contains(strtoupper($currency->name ?? ''), 'USD');
        }) ?: $collection->first();
    }

    private function getLatestRatesMatrix(): array
    {
        $latestRateIds = ExchangeRate::query()
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('from_currency_id', 'to_currency_id')
            ->pluck('id');

        $rates = ExchangeRate::whereIn('id', $latestRateIds)->get();

        $matrix = [];

        foreach ($rates as $rate) {
            $matrix[$rate->from_currency_id][$rate->to_currency_id] = (float) $rate->rate;
        }

        return $matrix;
    }

    private function convertAmountToTargetCurrency($amount, $fromCurrencyId, $targetCurrencyId, array $manualRates = []): float
    {
        $amount = (float) $amount;

        if (!$fromCurrencyId || !$targetCurrencyId || (int) $fromCurrencyId === (int) $targetCurrencyId) {
            return $amount;
        }

        if (isset($manualRates[$fromCurrencyId]) && (float) $manualRates[$fromCurrencyId] > 0) {
            return $amount * (float) $manualRates[$fromCurrencyId];
        }

        $directRate = ExchangeRate::query()
            ->where('from_currency_id', $fromCurrencyId)
            ->where('to_currency_id', $targetCurrencyId)
            ->latest('date')
            ->latest('id')
            ->value('rate');

        if ($directRate && (float) $directRate > 0) {
            return $amount * (float) $directRate;
        }

        $reverseRate = ExchangeRate::query()
            ->where('from_currency_id', $targetCurrencyId)
            ->where('to_currency_id', $fromCurrencyId)
            ->latest('date')
            ->latest('id')
            ->value('rate');

        if ($reverseRate && (float) $reverseRate > 0) {
            return $amount / (float) $reverseRate;
        }

        return 0;
    }

    private function currencyLabel($currency): string
    {
        if (!$currency) {
            return '-';
        }

        return $currency->code ?: $currency->name;
    }

    private function resolveExchangeType(?string $transactionType): string
    {
        return $transactionType === 'debit' ? 'Purchase' : 'Sell';
    }

    private function safeRateDisplay($transaction): string
    {
        $paymentAmount = (float) ($transaction->payment_amount ?? 0);
        $actualAmount = (float) ($transaction->actual_amount ?? 0);

        if ($paymentAmount > 0 && $actualAmount > 0) {
            return number_format($actualAmount / $paymentAmount, 6);
        }

        return '-';
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
