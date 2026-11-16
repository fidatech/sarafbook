<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use App\Models\Setting;


class JournalController extends Controller
{
    public function index()
    {
        $accounts   = DB::table('accounts')->select('id', 'name', 'code', 'account_sub_category_id')->get();
        $currencies = DB::table('currencies')->select('id', 'code', 'symbol')->get();

        return view('admin.journal.index', compact('accounts', 'currencies'));
    }

    // --------------------------------------------------
    // DATATABLE
    // --------------------------------------------------
    public function data(Request $request)
    {
        $query = DB::table('transactions as t')
            ->join('accounts as a', 'a.id', '=', 't.account_id')
            ->join('currencies as c', 'c.id', '=', 't.currency_id')
            ->select([
                't.id',
                't.created_at',
                't.note',
                't.transaction_type',
                't.amount',
                't.table_name',
                't.attachment',
                't.color',
                't.is_ok',

                'a.id as account_id',
                'a.name as account_name',
                'a.account_sub_category_id',

                'c.code as currency_code',
                'c.symbol as currency_symbol',
            ])

            ->when(
                $request->account_id,
                fn($q) =>
                $q->where('t.account_id', $request->account_id)
            )
            ->when(
                $request->currency_id,
                fn($q) =>
                $q->where('t.currency_id', $request->currency_id)
            )
            ->when($request->filled('color'), function ($query) use ($request) {
                $request->color === 'none'
                    ? $query->whereNull('t.color')
                    : $query->where('t.color', $request->color);
            })
            ->when(
                $request->filled('is_ok') && in_array((string) $request->is_ok, ['0', '1'], true),
                fn ($query) => $query->where('t.is_ok', (bool) $request->integer('is_ok'))
            )
            ->when($request->start_date && $request->end_date, function ($q) use ($request) {

                $q->whereBetween('t.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date   . ' 23:59:59'
                ]);
            })
            ->orderByDesc('t.created_at');

        return datatables()->of($query)
            ->addIndexColumn()
            ->addColumn('select', fn ($row) => '<input type="checkbox" class="form-check-input transaction-select" value="' . $row->id . '" aria-label="' . e(__('journal.select_transaction')) . '">')
            ->addColumn('color_display', function ($row) {
                return $row->color
                    ? '<span class="transaction-color-dot" style="background:' . e($row->color) . '" title="' . e($row->color) . '"></span>'
                    : '<span class="transaction-color-dot empty" title="' . e(__('journal.no_color')) . '"></span>';
            })
            ->addColumn('ok_display', fn ($row) => '<input type="checkbox" class="form-check-input transaction-ok-toggle" data-id="' . $row->id . '" ' . ($row->is_ok ? 'checked' : '') . ' aria-label="' . e(__('journal.transaction_ok')) . '">')
            ->addColumn('attachment', function ($row) {

                if (!$row->attachment) {
                    return '-';
                }

                $url = asset($row->attachment);

                return '
                    <a href="' . $url . '" target="_blank" class="btn btn-sm btn-light">
                        <i class="bi bi-paperclip"></i>
                    </a>
                ';
            })
            ->rawColumns(['attachment', 'select', 'color_display', 'ok_display'])
            ->make(true);
    }

    // --------------------------------------------------
    // SUMMARY
    // --------------------------------------------------
    public function summary(Request $request)
    {
        $rows = DB::table('transactions as t')
            ->join('accounts as a', 'a.id', '=', 't.account_id')
            ->join('currencies as c', 'c.id', '=', 't.currency_id')
            ->select(
                'c.code as currency',

                // Core totals
                DB::raw("SUM(CASE WHEN t.transaction_type='credit' THEN t.amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN t.transaction_type='debit'  THEN t.amount ELSE 0 END) as debit"),

                // Customer
                DB::raw("SUM(CASE WHEN a.account_sub_category_id = 1
                    THEN CASE WHEN t.transaction_type='credit' THEN t.amount ELSE -t.amount END
                ELSE 0 END) as customers"),

                // Saraf
                DB::raw("SUM(CASE WHEN a.account_sub_category_id = 2
                    THEN CASE WHEN t.transaction_type='credit' THEN t.amount ELSE -t.amount END
                ELSE 0 END) as saraf"),

                // Expenses (IMPORTANT FIX)
                DB::raw("SUM(CASE WHEN t.table_name='expenses'
                    AND t.transaction_type='debit'
                THEN t.amount ELSE 0 END) as expense"),

                // Cash
                DB::raw("SUM(CASE WHEN a.account_sub_category_id = 3 AND t.transaction_type='credit' THEN t.amount ELSE 0 END) as cash_credit"),
                DB::raw("SUM(CASE WHEN a.account_sub_category_id = 3 AND t.transaction_type='debit'  THEN t.amount ELSE 0 END) as cash_debit")
            )
            ->groupBy('c.code')
            ->get();

        $summary = [];

        foreach ($rows as $r) {
            $summary[$r->currency] = [
                'credit'    => (float)$r->credit,
                'debit'     => (float)$r->debit,
                'balance'   => (float)($r->credit - $r->debit),

                'customers' => (float)$r->customers,
                'saraf'     => (float)$r->saraf,
                'expense'   => (float)$r->expense,
                'cash'      => (float)($r->cash_credit - $r->cash_debit),
            ];
        }

        return response()->json(['summary' => $summary]);
    }

    public function sendWhatsApp(Transaction $transaction)
    {
        $transaction->load(['account', 'currency']);

        $account = $transaction->account;

        // ❌ Block cash account
        if ($account->account_sub_category_id == 3) {
            abort(403, 'Cash account cannot receive WhatsApp messages.');
        }

        // Phone check
        if (empty($account->contact)) {
            return back()->with('error', 'No phone number found for this account.');
        }

        $companyName = config('app.name');
        $currency    = $transaction->currency->code;
        $amount      = format_amount($transaction->amount, 0);
        $date        = $transaction->created_at->format('Y-m-d h:i A');

        $accountName = $account->name;
        $category    = optional($account->subCategory)->name ?? 'N/A';

        // Entry type
        if ($transaction->transaction_type === 'credit') {
            $entryType = '➕ Credit';
        } else {
            $entryType = '➖ Debit';
        }

        // Description
        $description = $transaction->note ?: '—';

        // 🔢 Calculate current balance (per account + currency)
        $balance = DB::table('transactions')
            ->where('account_id', $account->id)
            ->where('currency_id', $transaction->currency_id)
            ->selectRaw("
            SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END)
          - SUM(CASE WHEN transaction_type = 'debit'  THEN amount ELSE 0 END)
          AS balance
        ")
            ->value('balance');

        $balanceFormatted = format_amount($balance ?? 0, 2);

        // WhatsApp formatting
        $br = '%0A';

        $message =
            "*🏢 {$companyName}*{$br}{$br}" .
            "*🆕 New Transaction*{$br}" .
            "👤 *Account:* {$accountName}{$br}" .
            "📂 *Category:* {$category}{$br}" .
            "🔁 *Entry Type:* {$entryType}{$br}" .
            "💰 *Amount:* {$amount} {$currency}{$br}" .
            "📝 *Description:* {$description}{$br}" .
            "🕒 *Date:* {$date}{$br}" .
            "💼 *Balance:* {$balanceFormatted} {$currency}";

        $phone = preg_replace('/[^0-9]/', '', $account->contact);

        return redirect(
            "https://api.whatsapp.com/send?phone={$phone}&text={$message}"
        );
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {

            $t = Transaction::findOrFail($id);

            $currency = optional($t->currency)->code ?? '';
            $amount   = $t->amount;
            $type     = $t->transaction_type;

            if ($t->linked_id) {

                $transactions = Transaction::where('linked_id', $t->linked_id)->get();

                foreach ($transactions as $trx) {

                    logActivity(
                        $trx,
                        "سند ژورنال حذف شد: {$amount} {$currency} ({$type})",
                        [
                            'transaction_id' => $trx->id,
                            'linked_id' => $trx->linked_id,
                            'amount' => $trx->amount,
                            'currency' => $currency,
                            'transaction_type' => $trx->transaction_type
                        ],
                        'deleted'
                    );

                    $trx->delete();
                }
            } else {

                logActivity(
                    $t,
                    "سند ژورنال حذف شد: {$amount} {$currency} ({$type})",
                    [
                        'transaction_id' => $t->id,
                        'amount' => $t->amount,
                        'currency' => $currency,
                        'transaction_type' => $t->transaction_type
                    ],
                    'deleted'
                );

                $t->delete();
            }
        });

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }


    public function print(Request $request)
    {
        ini_set('memory_limit', '512M');
        $query = DB::table('transactions as t')
            ->join('accounts as a', 'a.id', '=', 't.account_id')
            ->join('currencies as c', 'c.id', '=', 't.currency_id')
            ->select(
                't.*',
                'a.name as account_name',
                'c.code as currency_code',
                'c.symbol as currency_symbol'
            );

        if ($request->filled('account_id')) {
            $query->where('t.account_id', $request->account_id);
        }

        if ($request->filled('currency_id')) {
            $query->where('t.currency_id', $request->currency_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('t.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $transactions = $query->orderBy('t.created_at')->get();

        // ✅ Running balance per currency
        $balances = [];

        foreach ($transactions as $t) {
            $cid = $t->currency_id;

            if (!isset($balances[$cid])) {
                $balances[$cid] = 0;
            }

            if ($t->transaction_type === 'credit') {
                $balances[$cid] += $t->amount;
            } else {
                $balances[$cid] -= $t->amount;
            }

            $t->running_balance = $balances[$cid];
        }

        // ✅ Summary per currency
        $summary = [];

        foreach ($transactions as $t) {
            $cid = $t->currency_id;

            if (!isset($summary[$cid])) {
                $summary[$cid] = [
                    'currency' => $t->currency_code,
                    'credit' => 0,
                    'debit' => 0
                ];
            }

            if ($t->transaction_type === 'credit') {
                $summary[$cid]['credit'] += $t->amount;
            } else {
                $summary[$cid]['debit'] += $t->amount;
            }
        }

        $setting = Setting::first();

        // Detect single currency
        $currencyInfo = null;
        if ($transactions->pluck('currency_id')->unique()->count() === 1) {
            $currencyInfo = $transactions->first()->currency_code ?? null;
        }

        // Detect single account
        $accountInfo = null;
        if ($transactions->pluck('account_id')->unique()->count() === 1) {
            $accountInfo = $transactions->first()->account_name ?? null;
        }

        // Date range
        $dateFrom = $request->start_date;
        $dateTo   = $request->end_date;
        return view('admin.journal.print', compact(
            'transactions',
            'summary',
            'setting',
            'currencyInfo',
            'accountInfo',
            'dateFrom',
            'dateTo'
        ));
    }
}
