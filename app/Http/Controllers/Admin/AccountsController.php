<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\Currency;
use App\Models\Remittance;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Spatie\Browsershot\Browsershot;

class AccountsController extends Controller
{
    /**
     * The list of accounts, drawn on the server: search, type, currency and balance filters,
     * pages of 10 to 100, and the totals of the filtered accounts by currency. Balances come
     * from account_balances, which every posting keeps up to date, so a page needs a handful
     * of queries whatever the number of accounts.
     */
    public function index(Request $request)
    {
        $subCategories = AccountSubCategory::with('accountCategory')->orderBy('id')->get();
        $currencies = Currency::orderBy('id')->get()->keyBy('id');
        $cashCategoryId = AccountCategory::where('name', 'Cash')->value('id');

        $search = trim((string) $request->query('q', ''));
        $type = $request->integer('type') ?: null;
        $currencyId = $request->integer('currency') ?: null;
        $balance = in_array($request->query('balance'), ['creditors', 'debtors', 'mixed'], true) ? $request->query('balance') : null;
        $perPage = in_array($request->integer('per_page'), [10, 25, 50, 100], true) ? $request->integer('per_page') : 25;

        $filtered = function () use ($search, $type) {
            return Account::query()
                ->where('accounts.is_active', true)
                ->when($type, fn ($q) => $q->where('accounts.account_sub_category_id', $type))
                ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('accounts.name', 'like', "%{$search}%")
                    ->orWhere('accounts.code', 'like', "%{$search}%")
                    ->orWhere('accounts.contact', 'like', "%{$search}%")));
        };

        $query = $filtered()->select('accounts.*')->with(['subCategory.accountCategory', 'user:id,username']);

        if ($balance && $currencyId) {
            $query->join('account_balances as ab', fn ($j) => $j->on('ab.account_id', '=', 'accounts.id')->where('ab.currency_id', $currencyId));
            match ($balance) {
                'creditors' => $query->where('ab.balance', '>', 0)->orderByDesc('ab.balance'),
                'debtors' => $query->where('ab.balance', '<', 0)->orderBy('ab.balance'),
                'mixed' => $query->whereIn('accounts.id', $this->mixedAccountIds())->orderByDesc('ab.balance'),
            };
        } elseif ($balance) {
            // across currencies an account is judged by its largest balance, whichever side it is on
            $dominant = DB::table('account_balances')
                ->selectRaw('account_id,
                    MAX(CASE WHEN balance > 0 THEN balance ELSE 0 END) AS max_pos,
                    MIN(CASE WHEN balance < 0 THEN balance ELSE 0 END) AS min_neg,
                    SUM(CASE WHEN balance > 0 THEN 1 ELSE 0 END) AS pos_count,
                    SUM(CASE WHEN balance < 0 THEN 1 ELSE 0 END) AS neg_count')
                ->groupBy('account_id');
            $query->joinSub($dominant, 'dom', 'dom.account_id', '=', 'accounts.id');
            $value = DB::raw('CASE WHEN dom.max_pos >= -dom.min_neg THEN dom.max_pos ELSE dom.min_neg END');
            match ($balance) {
                'creditors' => $query->whereRaw('dom.max_pos >= -dom.min_neg AND dom.max_pos > 0')->orderByDesc($value),
                'debtors' => $query->whereRaw('-dom.min_neg > dom.max_pos')->orderBy($value),
                'mixed' => $query->where('dom.pos_count', '>', 0)->where('dom.neg_count', '>', 0)->orderByDesc($value),
            };
        } else {
            // customers and Sarafs first, the cash accounts (safe, profit and loss, expenses) after them
            if ($cashCategoryId && ! $type) {
                $query->orderByRaw(
                    '(SELECT CASE WHEN s.account_category_id = ? THEN 1 ELSE 0 END FROM account_sub_categories s WHERE s.id = accounts.account_sub_category_id)',
                    [$cashCategoryId]
                );
            }
            $query->orderByDesc('accounts.created_at')->orderByDesc('accounts.id');
        }

        $accounts = $query->paginate($perPage)->withQueryString();

        $balances = AccountBalance::query()
            ->whereIn('account_id', $accounts->pluck('id'))
            ->when($currencyId, fn ($q) => $q->where('currency_id', $currencyId))
            ->where('balance', '!=', 0)
            ->orderBy('currency_id')
            ->get(['account_id', 'currency_id', 'balance']);

        // one column for every currency that has a balance on this page (or the chosen currency)
        $columns = $currencyId
            ? $currencies->only([$currencyId])
            : $currencies->only($balances->pluck('currency_id')->unique()->all())->sortKeys();
        if ($columns->isEmpty()) {
            $columns = $currencies->where('is_active', true)->take(5);
        }
        $balances = $balances->groupBy('account_id')->map(fn ($rows) => $rows->pluck('balance', 'currency_id'));

        // totals of the filtered accounts; without a type, the cash accounts (safe, profit and
        // loss, expenses) are left out, because adding them to customers would mean nothing
        $statsQuery = $filtered()->when(! $type && $cashCategoryId, fn ($q) => $q->whereHas(
            'subCategory', fn ($s) => $s->where('account_category_id', '!=', $cashCategoryId)
        ));
        $accountCount = (clone $statsQuery)->count();
        $cashCount = $type || ! $cashCategoryId ? 0 : $filtered()->whereHas(
            'subCategory', fn ($s) => $s->where('account_category_id', $cashCategoryId)
        )->count();
        $totals = DB::table('account_balances as ab')
            ->join('currencies as c', 'c.id', '=', 'ab.currency_id')
            ->whereIn('ab.account_id', (clone $statsQuery)->select('accounts.id'))
            ->when($currencyId, fn ($q) => $q->where('ab.currency_id', $currencyId))
            ->groupBy('c.id', 'c.code')
            ->orderBy('c.id')
            ->selectRaw('c.code,
                SUM(CASE WHEN ab.balance > 0 THEN ab.balance ELSE 0 END) AS we_owe,
                SUM(CASE WHEN ab.balance < 0 THEN -ab.balance ELSE 0 END) AS owed_to_us,
                SUM(ab.balance) AS net,
                SUM(CASE WHEN ab.balance > 0 THEN 1 ELSE 0 END) AS creditor_count,
                SUM(CASE WHEN ab.balance < 0 THEN 1 ELSE 0 END) AS debtor_count')
            ->havingRaw('SUM(ABS(ab.balance)) > 0')
            ->get();

        return view('admin.accounts.index', [
            'accounts' => $accounts,
            'balances' => $balances,
            'columns' => $columns,
            'currencies' => $currencies,
            'subCategories' => $subCategories,
            'cashCategoryId' => $cashCategoryId,
            'accountCount' => $accountCount,
            'cashCount' => $cashCount,
            'groupCash' => ! $balance && ! $type && $cashCategoryId,
            'totals' => $totals,
            'filters' => ['q' => $search, 'type' => $type, 'currency' => $currencyId, 'balance' => $balance, 'per_page' => $perPage],
        ]);
    }

    /** Accounts with money on both sides: a positive balance in one currency and a negative one in another. */
    private function mixedAccountIds()
    {
        return DB::table('account_balances')
            ->select('account_id')
            ->groupBy('account_id')
            ->havingRaw('SUM(CASE WHEN balance > 0 THEN 1 ELSE 0 END) > 0 AND SUM(CASE WHEN balance < 0 THEN 1 ELSE 0 END) > 0');
    }

    public function downloadImportTemplate()
    {
        $currencies = Currency::active()->orderBy('code')->get(['code']);
        $headers = ['account_sub_category_id', 'name', 'code', 'phone', 'address', 'note'];
        foreach ($currencies as $currency) {
            $headers[] = 'opening_' . strtoupper($currency->code);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Accounts Import');
        foreach ($headers as $index => $header) {
            $cell = $sheet->getCell([$index + 1, 1]);
            $cell->setValueExplicit($header, DataType::TYPE_STRING);
        }
        $example = [1, 'Example Customer', '#1001', '0700000000', 'Kabul', 'Positive = credit; negative = debit'];
        foreach ($currencies as $currency) {
            $example[] = 0;
        }
        $sheet->fromArray($example, null, 'A2');
        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0D6EFD');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('C2:D2001')->getNumberFormat()->setFormatCode('@');
        $validation = $sheet->getCell('A2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(false);
        $validation->setErrorTitle('Invalid account type');
        $validation->setError('Enter 1 for Customer or 2 for Saraf.');
        $validation->setPromptTitle('Account type');
        $validation->setPrompt('1 = Customer, 2 = Saraf');
        $validation->setShowInputMessage(true);
        $validation->setFormula1('"1,2"');
        for ($row = 3; $row <= 2001; $row++) {
            $sheet->getCell("A{$row}")->setDataValidation(clone $validation);
        }

        $path = tempnam(sys_get_temp_dir(), 'accounts-import-');
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, 'accounts-opening-balances-template.xlsx')->deleteFileAfterSend(true);
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        try {
            $rows = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return back()->with('error', __('ui.accounts_import_unreadable'));
        }

        if (count($rows) < 2) {
            return back()->with('error', __('ui.accounts_import_empty'));
        }

        $headers = array_map(fn ($value) => Str::of((string) $value)->trim()->lower()->replace([' ', '-'], '_')->value(), array_shift($rows));
        $requiredHeaders = ['account_sub_category_id', 'name'];
        if (array_diff($requiredHeaders, $headers)) {
            return back()->with('error', __('ui.accounts_import_headers'));
        }

        $currencies = Currency::active()->get()->keyBy(fn ($currency) => strtoupper($currency->code));
        $subCategories = AccountSubCategory::whereIn('id', [1, 2])->get()->keyBy('id');
        $seenCodes = [];
        $prepared = [];
        $errors = [];
        $nextAutoNumber = Account::query()
            ->pluck('code')
            ->reduce(function (int $maximum, $code) {
                return preg_match('/^#(\d+)$/', (string) $code, $matches)
                    ? max($maximum, (int) $matches[1])
                    : $maximum;
            }, 0) + 1;

        foreach ($rows as $offset => $values) {
            $rowNumber = $offset + 2;
            $row = array_combine($headers, array_pad(array_slice($values, 0, count($headers)), count($headers), null));
            if (collect($row)->every(fn ($value) => $value === null || trim((string) $value) === '')) {
                continue;
            }
            $row['account_sub_category_id'] = trim((string) ($row['account_sub_category_id'] ?? ''));
            $row['code'] = strtoupper(trim((string) ($row['code'] ?? '')));
            $validator = Validator::make($row, [
                'account_sub_category_id' => ['required', 'in:1,2'],
                'name' => ['required', 'string', 'max:255'],
                'code' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string'],
                'note' => ['nullable', 'string'],
            ]);
            if ($validator->fails()) {
                $errors[] = __('ui.accounts_import_row_error', ['row' => $rowNumber, 'error' => $validator->errors()->first()]);
                continue;
            }
            $subCategoryId = (int) $row['account_sub_category_id'];
            if (!$subCategories->has($subCategoryId)) {
                $errors[] = __('ui.accounts_import_account_type_missing', ['row' => $rowNumber]);
                continue;
            }
            if ($row['code'] !== '' && (isset($seenCodes[$row['code']]) || Account::where('code', $row['code'])->exists())) {
                $errors[] = __('ui.accounts_import_duplicate_code', ['row' => $rowNumber, 'code' => $row['code']]);
                continue;
            }
            if ($row['code'] === '') {
                do {
                    $row['code'] = '#' . $nextAutoNumber++;
                } while (isset($seenCodes[$row['code']]) || Account::where('code', $row['code'])->exists());
            }
            $seenCodes[$row['code']] = true;
            $balances = [];
            foreach ($headers as $header) {
                if (!str_starts_with($header, 'opening_')) continue;
                $code = strtoupper(substr($header, 8));
                if (!$currencies->has($code)) {
                    $errors[] = __('ui.accounts_import_currency_error', ['row' => $rowNumber, 'currency' => $code]);
                    continue 2;
                }
                $raw = trim((string) ($row[$header] ?? ''));
                if ($raw !== '' && !is_numeric(str_replace(',', '', $raw))) {
                    $errors[] = __('ui.accounts_import_amount_error', ['row' => $rowNumber, 'currency' => $code]);
                    continue 2;
                }
                $balances[$code] = (float) str_replace(',', '', $raw ?: '0');
            }
            $prepared[] = compact('row', 'balances');
        }

        if ($errors) {
            return back()->with('error', implode(' ', array_slice($errors, 0, 10)));
        }
        if (!$prepared) {
            return back()->with('error', __('ui.accounts_import_empty'));
        }

        DB::transaction(function () use ($prepared, $subCategories, $currencies) {
            foreach ($prepared as $item) {
                $row = $item['row'];
                $account = Account::create([
                    'name' => trim((string) $row['name']),
                    'code' => $row['code'],
                    'contact' => trim((string) ($row['phone'] ?? '')) ?: null,
                    'address' => trim((string) ($row['address'] ?? '')) ?: null,
                    'company' => trim((string) ($row['note'] ?? '')) ?: null,
                    'account_sub_category_id' => (int) $row['account_sub_category_id'],
                    'is_active' => true,
                    'is_safe' => (int) $row['account_sub_category_id'] === 2,
                    'bi_icon' => (int) $row['account_sub_category_id'] === 2 ? 'bi-cash' : 'bi-person',
                    'bi_icon_color' => (int) $row['account_sub_category_id'] === 2 ? 'success' : 'primary',
                    'include_in_reports' => true,
                    'created_by' => Auth::id(),
                ]);
                foreach ($item['balances'] as $code => $signedAmount) {
                    if ($signedAmount == 0.0) continue;
                    $amount = abs($signedAmount);
                    $currencyId = $currencies[$code]->id;
                    Transaction::create([
                        'table_name' => 'accounts_opening_import',
                        'table_row_id' => $account->id,
                        'linked_id' => (string) Str::uuid(),
                        'account_id' => $account->id,
                        'currency_id' => $currencyId,
                        'payment_currency_id' => $currencyId,
                        'payment_amount' => $amount,
                        'actual_currency_id' => $currencyId,
                        'actual_amount' => $amount,
                        'amount' => $amount,
                        'transaction_type' => $signedAmount > 0 ? 'credit' : 'debit',
                        'note' => __('ui.imported_opening_balance'),
                        'reference' => 'OPENING-' . $account->code,
                        'status' => 'active',
                        'is_visible' => true,
                        'created_by' => Auth::id(),
                        'category' => 'opening_balance',
                    ]);
                }
            }
        });

        return back()->with('success', __('ui.accounts_import_success', ['count' => count($prepared)]));
    }

    public function exportPdf2(Account $account)
    {
        $setting = Setting::first(); // or your logic
        $summariesByCurrency = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.code as currency',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit")
            )
            ->where('account_id', $account->id)
            ->groupBy('currencies.code')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->currency => [
                    'credit' => $item->credit,
                    'debit' => $item->debit
                ]];
            });

        $pdf = Pdf::loadView('admin.accounts.export', compact('account', 'setting', 'summariesByCurrency'));
        return $pdf->download($account->code . '-summary.pdf');
        // return view('admin.accounts.export', compact('account', 'setting', 'summariesByCurrency'));
    }

    public function exportPdf(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $startDate = $request->start_date;
        $endDate   = $request->end_date;

        /*
    |--------------------------------------------------------------------------
    | Currencies Belonging To This Account
    |--------------------------------------------------------------------------
    | We include all currencies used by this account.
    | If currency_id filter is selected, only that currency will be included.
    */
        $accountCurrencyIdsQuery = Transaction::where('account_id', $id);

        if ($request->filled('currency_id')) {
            $accountCurrencyIdsQuery->where('currency_id', $request->currency_id);
        }

        $accountCurrencyIds = $accountCurrencyIdsQuery
            ->distinct()
            ->pluck('currency_id')
            ->filter()
            ->values();

        /*
    |--------------------------------------------------------------------------
    | Currencies Collection
    |--------------------------------------------------------------------------
    */
        $currencies = Currency::select('id', 'code')->get();

        /*
    |--------------------------------------------------------------------------
    | Opening Transactions
    |--------------------------------------------------------------------------
    | Opening Balance = all transactions before start_date.
    */
        $openingTransactionsQuery = Transaction::with('currency')
            ->where('account_id', $id)
            ->whereIn('currency_id', $accountCurrencyIds);

        if ($request->filled('start_date')) {
            $openingTransactionsQuery->where('created_at', '<', $startDate . ' 00:00:00');
        } else {
            $openingTransactionsQuery->whereRaw('1 = 0');
        }

        $openingTransactions = $openingTransactionsQuery->get();

        /*
    |--------------------------------------------------------------------------
    | Period Transactions
    |--------------------------------------------------------------------------
    | These are the actual report transactions shown between selected dates.
    */
        $periodTransactionsQuery = Transaction::with('currency')
            ->where('account_id', $id)
            ->whereIn('currency_id', $accountCurrencyIds);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $periodTransactionsQuery->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ]);
        }

        $periodTransactions = $periodTransactionsQuery
            ->orderBy('currency_id', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        /*
    |--------------------------------------------------------------------------
    | After Period Transactions
    |--------------------------------------------------------------------------
    | Used only for Current Balance.
    | Current Balance = closing balance + transactions after end_date until now.
    */
        $afterPeriodTransactionsQuery = Transaction::with('currency')
            ->where('account_id', $id)
            ->whereIn('currency_id', $accountCurrencyIds);

        if ($request->filled('end_date')) {
            $afterPeriodTransactionsQuery->where('created_at', '>', $endDate . ' 23:59:59');
        } else {
            $afterPeriodTransactionsQuery->whereRaw('1 = 0');
        }

        $afterPeriodTransactions = $afterPeriodTransactionsQuery->get();

        /*
    |--------------------------------------------------------------------------
    | Prepare Opening Balances
    |--------------------------------------------------------------------------
    */
        $openingBalances = [];

        foreach ($accountCurrencyIds as $currencyId) {
            $openingBalances[$currencyId] = 0;
        }

        foreach ($openingTransactions as $transaction) {
            $currencyId = $transaction->currency_id;

            if (!isset($openingBalances[$currencyId])) {
                $openingBalances[$currencyId] = 0;
            }

            if ($transaction->transaction_type === 'credit') {
                $openingBalances[$currencyId] += (float) $transaction->amount;
            }

            if ($transaction->transaction_type === 'debit') {
                $openingBalances[$currencyId] -= (float) $transaction->amount;
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Prepare After Period Balances
    |--------------------------------------------------------------------------
    */
        $afterPeriodBalances = [];

        foreach ($accountCurrencyIds as $currencyId) {
            $afterPeriodBalances[$currencyId] = 0;
        }

        foreach ($afterPeriodTransactions as $transaction) {
            $currencyId = $transaction->currency_id;

            if (!isset($afterPeriodBalances[$currencyId])) {
                $afterPeriodBalances[$currencyId] = 0;
            }

            if ($transaction->transaction_type === 'credit') {
                $afterPeriodBalances[$currencyId] += (float) $transaction->amount;
            }

            if ($transaction->transaction_type === 'debit') {
                $afterPeriodBalances[$currencyId] -= (float) $transaction->amount;
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Build Statement Rows
    |--------------------------------------------------------------------------
    | For each account currency:
    | 1. Opening Balance row
    | 2. Transactions inside selected date range
    | 3. Closing Balance row
    | 4. Current Balance row
    */
        $statementRows = collect();

        $transactionsByCurrency = $periodTransactions->groupBy('currency_id');

        $summariesByCurrency = collect();

        foreach ($accountCurrencyIds as $currencyId) {
            $currency = $currencies->firstWhere('id', $currencyId);
            $currencyCode = $currency->code ?? 'N/A';

            $openingBalance = (float) ($openingBalances[$currencyId] ?? 0);
            $runningBalance = $openingBalance;

            $currencyTransactions = $transactionsByCurrency->get($currencyId, collect());

            /*
        |--------------------------------------------------------------------------
        | Opening Balance Row
        |--------------------------------------------------------------------------
        */
            $statementRows->push((object) [
                'row_type'         => 'opening',
                'created_at'       => $request->filled('start_date') ? $startDate . ' 00:00:00' : null,
                'note'             => 'Opening Balance',
                'currency_id'      => $currencyId,
                'currency'         => $currency,
                'transaction_type' => null,
                'amount'           => 0,
                'running_balance'  => $runningBalance,
            ]);

            /*
        |--------------------------------------------------------------------------
        | Normal Period Transactions
        |--------------------------------------------------------------------------
        */
            foreach ($currencyTransactions as $transaction) {
                if ($transaction->transaction_type === 'credit') {
                    $runningBalance += (float) $transaction->amount;
                }

                if ($transaction->transaction_type === 'debit') {
                    $runningBalance -= (float) $transaction->amount;
                }

                $transaction->row_type = 'transaction';
                $transaction->running_balance = $runningBalance;

                $statementRows->push($transaction);
            }

            /*
        |--------------------------------------------------------------------------
        | Closing Balance Row
        |--------------------------------------------------------------------------
        | Closing Balance = opening + selected period transactions.
        */
            $closingBalance = $runningBalance;

            $statementRows->push((object) [
                'row_type'         => 'closing',
                'created_at'       => $request->filled('end_date') ? $endDate . ' 23:59:59' : null,
                'note'             => 'Closing Balance',
                'currency_id'      => $currencyId,
                'currency'         => $currency,
                'transaction_type' => null,
                'amount'           => 0,
                'running_balance'  => $closingBalance,
            ]);

            /*
        |--------------------------------------------------------------------------
        | Current Balance Row
        |--------------------------------------------------------------------------
        | Current Balance = closing balance + transactions after end_date until now.
        */
            $currentBalance = $closingBalance + (float) ($afterPeriodBalances[$currencyId] ?? 0);

            $statementRows->push((object) [
                'row_type'         => 'current',
                'created_at'       => now(),
                'note'             => 'Current Balance',
                'currency_id'      => $currencyId,
                'currency'         => $currency,
                'transaction_type' => null,
                'amount'           => 0,
                'running_balance'  => $currentBalance,
            ]);

            /*
        |--------------------------------------------------------------------------
        | Summary By Currency
        |--------------------------------------------------------------------------
        */
            $credit = (float) $currencyTransactions
                ->where('transaction_type', 'credit')
                ->sum('amount');

            $debit = (float) $currencyTransactions
                ->where('transaction_type', 'debit')
                ->sum('amount');

            $summariesByCurrency->put($currencyCode, [
                'opening_balance' => $openingBalance,
                'credit'          => $credit,
                'debit'           => $debit,
                'closing_balance' => $closingBalance,
                'current_balance' => $currentBalance,
            ]);
        }

        $transactions = $statementRows;

        $setting = Setting::first();

        return view('admin.accounts.exports.account_statement', compact(
            'account',
            'transactions',
            'summariesByCurrency',
            'setting',
            'currencies'
        ));
    }

    public function exportView(Account $account)
    {
        $setting = Setting::first();
        $summariesByCurrency = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.code as currency',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit")
            )
            ->where('account_id', $account->id)
            ->groupBy('currencies.code')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->currency => [
                    'credit' => $item->credit,
                    'debit' => $item->debit
                ]];
            });

        return view('admin.accounts.export', compact('account', 'setting', 'summariesByCurrency'));
    }

    public function exportImage(Account $account)
    {
        $url = route('admin.accounts.export.view', $account);
        $filename = storage_path("app/public/{$account->code}-summary.png");

        Browsershot::url($url)
            ->setNodeBinary('/usr/local/bin/node')
            ->setNpmBinary('/usr/local/bin/npm')
            ->setIncludePath('/usr/local/bin')
            ->windowSize(800, 1100)
            ->waitUntilNetworkIdle()
            ->save($filename);

        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'create_account_sub_category_id' => 'required|integer',
            'contact' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'company' => 'nullable|string|max:255',
            'include_in_reports' => 'nullable|boolean',
        ]);

        $data = new Account();
        $data->name = $request->name;
        $data->account_sub_category_id = $request->create_account_sub_category_id;

        // ✅ HANDLE CODE
        if (!empty($request->code)) {

            $inputCode = strtoupper($request->code);

            // check if exists
            $exists = Account::where('code', $inputCode)->exists();

            if (!$exists) {
                $data->code = $inputCode;
            } else {
                // extract number from input like #2 → 2
                preg_match('/\d+/', $inputCode, $matches);
                $start = isset($matches[0]) ? (int)$matches[0] : 1;

                do {
                    $start++;
                    $newCode = '#' . $start;
                } while (Account::where('code', $newCode)->exists());

                $data->code = $newCode;
            }
        } else {
            // ✅ AUTO GENERATE BASED ON EXISTING #numbers

            $lastNumber = Account::whereNotNull('code')
                ->whereRaw("code REGEXP '^#[0-9]+$'")
                ->selectRaw("MAX(CAST(SUBSTRING(code, 2) AS UNSIGNED)) as max_num")
                ->value('max_num');

            $next = $lastNumber ? $lastNumber + 1 : 1;

            $data->code = '#' . $next;
        }

        $data->contact = $request->contact;
        $data->address = $request->address;
        $data->company = $request->company;

        // ✅ Safe logic
        if ($request->create_account_sub_category_id == 2) {
            $data->is_safe = true;
            $data->bi_icon = 'bi-cash';
            $data->bi_icon_color = 'success';
        } else {
            $data->is_safe = false;
            $data->bi_icon = 'bi-person';
            $data->bi_icon_color = 'primary';
        }

        $data->created_by = Auth::id();
        $data->include_in_reports = $request->boolean('include_in_reports');
        $data->save();

        logActivity(
            $data,
            "اکانت ایجاد شد: {$data->name} ({$data->code})",
            [
                'account_id' => $data->id,
                'name' => $data->name,
                'code' => $data->code,
                'contact' => $data->contact,
            ],
            'created'
        );

        return redirect()->back()->with('success', __('accounts.created_successfully'));
    }

    public function show($id)
    {
        $account    = Account::with('subCategory')->findOrFail($id);
        $accounts   = Account::whereNot('id', $id)->get();
        $sarafs     = Account::where('account_sub_category_id', 2)->orderBy('name')->get();
        $currencies = Currency::where('is_active', 1)->get();
        $setting    = Setting::first();

        $summariesByCurrency = DB::table('transactions')
            ->join('currencies', 'transactions.payment_currency_id', '=', 'currencies.id')
            ->select(
                'currencies.code as currency',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN payment_amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN payment_amount ELSE 0 END) as debit")
            )
            ->where('account_id', $account->id)
            ->whereNotNull('transactions.payment_currency_id')
            ->groupBy('currencies.code')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->currency => [
                    'credit' => (float) $item->credit,
                    'debit'  => (float) $item->debit,
                ]];
            });

        return view('admin.accounts.show', compact(
            'account',
            'currencies',
            'summariesByCurrency',
            'setting',
            'accounts',
            'sarafs'
        ));
    }

    public function print(Account $account)
    {
        $summariesByCurrency = AccountBalance::where('account_id', $account->id)
            ->with('currency')
            ->get()
            ->groupBy('currency.code')
            ->map(function ($rows, $currencyCode) {
                $credit = $rows->sum('credit');
                $debit = $rows->sum('debit');

                return [
                    'credit' => $credit,
                    'debit' => $debit,
                    'currency' => $currencyCode,
                ];
            });

        return view('admin.accounts.print-preview', compact('account', 'summariesByCurrency'));
    }

    public function showAccount($id)
    {
        $account = Account::findOrFail($id);

        // Get all transactions grouped by currency
        $balances = DB::table('transactions')
            ->select('currency_id')
            ->selectRaw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END) as net_balance')
            ->where('account_id', $account->id)
            ->groupBy('currency_id')
            ->get();

        $currencyBalances = [];

        foreach ($balances as $item) {
            $currency = \App\Models\Currency::find($item->currency_id);
            $flag = $currency ? strtolower(substr($currency->code, 0, 2)) : 'xx';

            $currencyBalances[] = [
                'currency' => $currency->code ?? 'N/A',
                'amount' => format_amount($item->net_balance, 2),
                'flag' => "/assets/flags/{$flag}.svg",
            ];
        }

        return response()->json([
            'id' => $account->id,
            'name' => $account->name,
            'code' => $account->code,
            'balances' => $currencyBalances,
        ]);
    }

    public function getBalancesByAccount($id)
    {
        $account = Account::findOrFail($id);

        $balances = DB::table('transactions')
            ->select('currency_id')
            ->selectRaw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END) as net_balance')
            ->where('account_id', $account->id)
            ->groupBy('currency_id')
            ->get();

        $currencyBalances = [];

        foreach ($balances as $item) {
            $currency = \App\Models\Currency::find($item->currency_id);
            $flag = $currency ? strtolower(substr($currency->code, 0, 2)) : 'xx';

            $currencyBalances[] = [
                'currency' => $currency->code ?? 'N/A',
                'amount' => format_amount($item->net_balance, 2),
                'flag' => "/assets/flags/{$flag}.svg",
            ];
        }

        return response()->json([
            'id' => $account->id,
            'name' => $account->name,
            'code' => $account->code,
            'balances' => $currencyBalances,
        ]);
    }

    public function getCurrencyBalance($accountId, $currencyId)
    {
        $credit = Transaction::where('account_id', $accountId)
            ->where('currency_id', $currencyId)
            ->where('transaction_type', 'credit')
            ->sum('amount');

        $debit = Transaction::where('account_id', $accountId)
            ->where('currency_id', $currencyId)
            ->where('transaction_type', 'debit')
            ->sum('amount');

        $balance = $credit - $debit;

        $currency = Currency::find($currencyId);

        return response()->json([
            'amount' => format_amount($balance, 2),
            'raw_amount' => $balance,
            'currency' => $currency?->code ?? 'N/A',
            'symbol' => $currency?->symbol ?? '',
        ]);
    }

    public function transactionData($id, $type, Request $request)
    {
        $query = Transaction::with(['currency', 'paymentCurrency', 'actualCurrency'])
            ->select('transactions.*')
            ->addSelect([
                'remittance_paid_at' => Remittance::query()
                    ->select('paid_at')
                    ->whereColumn('remittances.id', 'transactions.table_row_id')
                    ->whereRaw("LOWER(transactions.table_name) = 'remittances'")
                    ->limit(1),
            ])
            ->selectRaw("
                SUM(
                    CASE
                        WHEN transaction_type = 'credit' THEN COALESCE(payment_amount, 0)
                        WHEN transaction_type = 'debit' THEN -COALESCE(payment_amount, 0)
                        ELSE 0
                    END
                ) OVER (
                    PARTITION BY payment_currency_id
                    ORDER BY created_at ASC, id ASC
                ) AS running_balance
                ")
            ->where('account_id', $id);

        if ($request->filled('currency_id')) {
            $query->where('payment_currency_id', $request->currency_id);
        }

        if ($request->filled('transaction_type') && in_array($request->transaction_type, ['credit', 'debit'], true)) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('color')) {
            $request->color === 'none'
                ? $query->whereNull('color')
                : $query->where('color', $request->color);
        }

        if ($request->filled('is_ok') && in_array((string) $request->is_ok, ['0', '1'], true)) {
            $query->where('is_ok', (bool) $request->integer('is_ok'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $query->orderByDesc('created_at')->orderByDesc('id');

        $setting = \App\Models\Setting::first();
        $account = \App\Models\Account::findOrFail($id);
        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                $search = trim((string) $request->input('search.value', ''));

                if ($search === '') {
                    return;
                }

                $transactionTypes = collect(['credit', 'debit'])
                    ->filter(function ($type) use ($search) {
                        $labels = [$type, (string) __("ui.{$type}")];

                        return collect($labels)->contains(
                            fn ($label) => mb_stripos($label, $search) !== false
                        );
                    })
                    ->values()
                    ->all();

                $query->where(function ($query) use ($search, $transactionTypes) {
                    $query->where('note', 'like', "%{$search}%")
                        ->orWhere('created_at', 'like', "%{$search}%");

                    if ($transactionTypes !== []) {
                        $query->orWhereIn('transaction_type', $transactionTypes);
                    }
                });
            })
            ->addIndexColumn()

            ->addColumn('select', fn ($row) => '<input type="checkbox" class="form-check-input transaction-select" value="' . $row->id . '" aria-label="' . e(__('accounts.select_transaction')) . '">')

            ->addColumn('color_display', function ($row) {
                return $row->color
                    ? '<span class="transaction-color-dot" style="background:' . e($row->color) . '" title="' . e($row->color) . '"></span>'
                    : '<span class="transaction-color-dot empty" title="' . e(__('accounts.no_color')) . '"></span>';
            })

            ->addColumn('ok_display', fn ($row) => '<input type="checkbox" class="form-check-input transaction-ok-toggle" data-id="' . $row->id . '" ' . ($row->is_ok ? 'checked' : '') . ' aria-label="' . e(__('accounts.transaction_ok')) . '">')

            ->addColumn('payment_display', function ($row) {
                if ($row->payment_amount === null) {
                    return '<span class="text-muted">-</span>';
                }

                $amount = format_amount($row->payment_amount, 2);
                $currency = $row->paymentCurrency->code ?? '';

                return '<span class="fw-bold mono">' .
                    e($amount) . ' ' . e($currency) .
                    '</span>';
            })

            ->addColumn('amount_display', function ($row) {
                $amount = format_amount($row->amount, 2);
                $currency = $row->currency->code ?? '';

                return '<span class="fw-bold mono">' .
                    e($amount) . ' ' . e($currency) .
                    '</span>';
            })

            ->addColumn('currency', function ($row) {
                return $row->currency->code ?? '-';
            })

            ->addColumn('payment_currency', function ($row) {
                return $row->paymentCurrency->code ?? '-';
            })

            ->addColumn('actual_currency', function ($row) {
                return $row->actualCurrency->code ?? '-';
            })

            ->addColumn('running_balance', function ($row) {
                $balance = (float) $row->running_balance;
                $class = $balance < 0 ? 'text-danger' : 'text-dark';
                $currency = $row->paymentCurrency->code ?? '';

                return '<span class="fw-bold mono ' . $class . '">' .
                    format_amount($balance, 2) . ' ' . e($currency) .
                    '</span>';
            })

            ->editColumn('transaction_type', function ($row) {
                return $row->transaction_type === 'credit'
                    ? '<span class="badge bg-success">' . e(__('ui.credit')) . '</span>'
                    : '<span class="badge bg-danger">' . e(__('ui.debit')) . '</span>';
            })

            ->addColumn('actions', function ($row) use ($account, $setting) {
                $buttons = '<div class="statement-actions">';

                $message = $this->buildTransactionMessage($row, $account, $setting);
                $contact = $account->contact ?? null;
                $whatsappUrl = $this->whatsappUrl($message, $contact);

                if (strcasecmp((string) $row->table_name, 'remittances') === 0 && $row->table_row_id) {
                    $printRoute = $row->remittance_paid_at && auth()->user()->can('update remittances')
                        ? 'admin.remittances.payment-receipt'
                        : 'admin.remittances.print';
                    $printUrl = route($printRoute, $row->table_row_id);

                    $buttons .= '
            <button type="button"
                    class="statement-action-btn print btn-remittance-direct-print"
                    data-print-url="' . e($printUrl) . '"
                    title="' . e(__('remittances.direct_print')) . '"
                    aria-label="' . e(__('remittances.direct_print')) . '">
                <i class="bi bi-printer-fill"></i>
            </button>
        ';
                }

                $buttons .= '
        <a href="' . e($whatsappUrl) . '"
           target="_blank"
           class="statement-action-btn whatsapp"
           title="' . e(__('accounts.send_whatsapp')) . '">
            <i class="bi bi-whatsapp"></i>
        </a>
    ';

                if (auth()->user()->can('update transactions')) {
                    $buttons .= '
            <button type="button"
                    class="statement-action-btn edit btn-edit"
                    data-id="' . e($row->id) . '"
                    title="' . e(__('accounts.edit')) . '">
                <i class="bi bi-pencil"></i>
            </button>
        ';
                }

                if (auth()->user()->can('delete transactions')) {
                    $buttons .= '
            <button type="button"
                    class="statement-action-btn delete btn-delete"
                    data-id="' . e($row->id) . '"
                    title="' . e(__('accounts.delete')) . '">
                <i class="bi bi-trash"></i>
            </button>
        ';
                }

                $buttons .= '</div>';

                return $buttons;
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at->format('Y-m-d H:i');
            })

            ->rawColumns([
                'transaction_type',
                'select',
                'color_display',
                'ok_display',
                'payment_display',
                'amount_display',
                'running_balance',
                'actions'
            ])
            ->make(true);
    }

    public function transactionActualData($id, $type, Request $request)
    {
        $query = Transaction::with(['paymentCurrency', 'actualCurrency'])
            ->where('account_id', $id)
            ->whereNotNull('actual_amount')
            ->orderByDesc('created_at');

        if ($request->filled('currency_id')) {
            $query->where('actual_currency_id', $request->currency_id);
        }

        if ($request->filled('transaction_type') && in_array($request->transaction_type, ['credit', 'debit'], true)) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()

            // Keep raw type for JS calculations
            ->addColumn('transaction_type_key', function ($row) {
                return $row->transaction_type;
            })

            // Raw numeric values for JS calculations
            ->addColumn('payment_amount_raw', function ($row) {
                return (float) $row->payment_amount;
            })

            ->addColumn('actual_amount_raw', function ($row) {
                return (float) $row->actual_amount;
            })

            // Display payment amount with danger for debit
            ->editColumn('payment_amount', function ($row) {
                $class = $row->transaction_type === 'debit' ? 'text-danger' : 'text-success';

                return '<span class="fw-bold mono ' . $class . '">' .
                    format_amount($row->payment_amount, 2) .
                    '</span>';
            })

            ->addColumn('payment_currency', function ($row) {
                return $row->paymentCurrency->code ?? '-';
            })

            // Display actual amount with danger for debit
            ->editColumn('actual_amount', function ($row) {
                $class = $row->transaction_type === 'debit' ? 'text-danger' : 'text-success';

                return '<span class="fw-bold mono ' . $class . '">' .
                    format_amount($row->actual_amount, 2) .
                    '</span>';
            })

            ->addColumn('actual_currency', function ($row) {
                return $row->actualCurrency->code ?? '-';
            })

            ->editColumn('transaction_type', function ($row) {
                return $row->transaction_type === 'credit'
                    ? '<span class="badge bg-success">' . e(__('ui.credit')) . '</span>'
                    : '<span class="badge bg-danger">' . e(__('ui.debit')) . '</span>';
            })

            ->editColumn('created_at', function ($row) {
                return $row->created_at->format('Y-m-d H:i');
            })

            ->rawColumns([
                'payment_amount',
                'actual_amount',
                'transaction_type',
            ])
            ->make(true);
    }

    public function filteredSummary($id, Request $request)
    {
        $account = Account::findOrFail($id);

        $base = DB::table('transactions')
            ->join('currencies', 'transactions.payment_currency_id', '=', 'currencies.id')
            ->where('transactions.account_id', $account->id)
            ->whereNotNull('transactions.payment_currency_id');

        // Filters
        if ($request->filled('currency_id')) {
            $base->where('transactions.payment_currency_id', $request->currency_id);
        }

        if ($request->filled('transaction_type') && in_array($request->transaction_type, ['credit', 'debit'], true)) {
            $base->where('transactions.transaction_type', $request->transaction_type);
        }

        if ($request->filled('date_from')) {
            $base->whereDate('transactions.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $base->whereDate('transactions.created_at', '<=', $request->date_to);
        }

        $perCurrencyRows = (clone $base)
            ->select(
                'currencies.code as currency',
                DB::raw("
                SUM(
                    CASE
                        WHEN transactions.transaction_type = 'credit'
                        THEN COALESCE(transactions.payment_amount, 0)
                        ELSE 0
                    END
                ) as credit
            "),
                DB::raw("
                SUM(
                    CASE
                        WHEN transactions.transaction_type = 'debit'
                        THEN COALESCE(transactions.payment_amount, 0)
                        ELSE 0
                    END
                ) as debit
            ")
            )
            ->groupBy('currencies.code')
            ->get();

        $perCurrency = [];
        $totalCredit = 0.0;
        $totalDebit  = 0.0;

        foreach ($perCurrencyRows as $row) {
            $credit  = (float) $row->credit;
            $debit   = (float) $row->debit;
            $balance = $credit - $debit;

            $perCurrency[$row->currency] = [
                'credit'  => $credit,
                'debit'   => $debit,
                'balance' => $balance,
            ];

            $totalCredit += $credit;
            $totalDebit  += $debit;
        }

        $totalBalance = $totalCredit - $totalDebit;

        return response()->json([
            'per_currency' => $perCurrency,
            'total' => [
                'credit'  => $totalCredit,
                'debit'   => $totalDebit,
                'balance' => $totalBalance,
            ],
        ]);
    }


    public function edit($id)
    {
        $account = Account::findOrFail($id);

        return response()->json([
            'id' => $account->id,
            'name' => $account->name,
            'code' => $account->code,
            'contact' => $account->contact,
            'address' => $account->address,
            'company' => $account->company,
            'account_sub_category_id' => $account->account_sub_category_id,
            'include_in_reports' => (int) $account->include_in_reports,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:accounts,code,' . $id,
            'create_account_sub_category_id' => 'required|integer|in:1,2,3,5',
            'contact' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'company' => 'nullable|string|max:255',
            'include_in_reports' => 'nullable|boolean',
        ]);

        $account = Account::findOrFail($id);

        $original = $account->getOriginal();

        $account->update([
            'name' => $request->name,
            'code' => $request->code,
            'account_sub_category_id' => $request->create_account_sub_category_id,
            'contact' => $request->contact,
            'address' => $request->address,
            'company' => $request->company,
            'include_in_reports' => $request->boolean('include_in_reports')
        ]);

        $changes = [];

        foreach ($account->getChanges() as $field => $newValue) {

            if ($field === 'updated_at') continue;

            $oldValue = $original[$field] ?? null;

            if ($oldValue != $newValue) {
                $changes[] = "{$field}: {$oldValue} → {$newValue}";
            }
        }

        if (!empty($changes)) {

            $description = "اکانت بروزرسانی شد (" . implode(' | ', $changes) . ")";

            logActivity(
                $account,
                $description,
                [
                    'account_id' => $account->id,
                    'changes' => $changes
                ],
                'updated'
            );
        }

        if (! $request->expectsJson()) {
            return back()->with('success', __('accounts.account_updated'));
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, string $id)
    {
        $account = Account::with('subCategory')->findOrFail($id);

        // the cash accounts (safe, profit and loss, expenses) are never deleted, as the list already showed
        $cashCategoryId = AccountCategory::where('name', 'Cash')->value('id');
        if ($cashCategoryId && (int) $account->subCategory?->account_category_id === (int) $cashCategoryId) {
            abort(403, __('accounts.delete_failed'));
        }

        try {

            $name = $account->name;
            $code = $account->code;

            $hasTransactions = Transaction::where('account_id', $account->id)->exists();

            if ($hasTransactions) {
                Transaction::where('account_id', $account->id)->delete();
            }

            $account->delete();

            logActivity(
                $account,
                "اکانت حذف شد: {$name} ({$code})",
                [
                    'account_id' => $account->id,
                    'name' => $name,
                    'code' => $code
                ],
                'deleted'
            );

            if (! $request->expectsJson()) {
                return back()->with('success', __('accounts.deleted_related'));
            }

            return response()->json([
                'success' => true,
                'message' => __('accounts.deleted_related')
            ]);
        } catch (Exception $e) {

            Log::error('Account deletion error: ' . $e->getMessage());

            if (! $request->expectsJson()) {
                return back()->with('error', __('accounts.delete_failed'));
            }

            return response()->json([
                'success' => false,
                'message' => __('accounts.delete_failed')
            ], 500);
        }
    }

    public function sendWhatsApp($id)
    {
        $account = Account::findOrFail($id);
        $setting = Setting::first();

        if (!$account->contact) {
            return response()->json(['error' => __('accounts.no_phone')], 422);
        }

        $storedBalances = AccountBalance::where('account_id', $id)
            ->get()
            ->keyBy('currency_id');

        $balances = Currency::active()
            ->orderBy('id')
            ->get()
            ->map(function (Currency $currency) use ($storedBalances) {
                return [
                    'currency' => $currency->name ?: $currency->code,
                    'balance' => (float) optional($storedBalances->get($currency->id))->balance,
                ];
            })
            ->all();

        $msg = $this->buildAccountBalanceMessage($account, $setting, $balances);

        return response()->json([
            'message' => __('accounts.whatsapp_prepared'),
            'url' => $this->whatsappUrl($msg, $account->contact),
        ]);
    }

    public function updateStats(Request $request)
    {
        $accountCategoryId = $request->input('account_category_id');
        $currencyId = $request->input('currency_id');

        // ✅ EXCLUDE SAFE ACCOUNT (id = 1)
        $accountsQuery = Account::where('is_active', true)
            ->where('id', '!=', 1);

        if ($accountCategoryId) {
            $accountsQuery->whereHas('subCategory', function ($q) use ($accountCategoryId) {
                $q->where('account_category_id', $accountCategoryId);
            });
        }

        $accountIds = $accountsQuery->pluck('id');

        $transactions = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->whereIn('transactions.account_id', $accountIds);

        // ✅ If currency selected → filter
        if ($currencyId) {
            $transactions->where('transactions.currency_id', $currencyId);
        }

        // ✅ GROUP BY CURRENCY
        $rows = $transactions
            ->select(
                'currencies.code as currency',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit")
            )
            ->groupBy('currencies.code')
            ->orderBy('currencies.id')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $credit = (float) $row->credit;
            $debit  = (float) $row->debit;

            $result[] = [
                'currency' => $row->currency,
                'credit'   => format_amount($credit, 2),
                'debit'    => format_amount($debit, 2),
                'balance'  => format_amount($credit - $debit, 2),
            ];
        }

        return response()->json([
            'total_accounts' => $accountIds->count(),
            'currencies' => $result
        ]);
    }

    public function getSubCategories($categoryId)
    {
        $subCategories = AccountSubCategory::where('account_category_id', $categoryId)->get();
        return response()->json($subCategories);
    }

    public function getCategoryPrefix($id)
    {
        $category = AccountCategory::findOrFail($id);
        return response()->json(['prefix' => strtoupper($category->code_prefix)]);
    }

    public function checkCode(Request $request)
    {
        $query = Account::where('code', $request->code);

        if ($request->filled('ignore_id')) {
            $query->where('id', '!=', $request->ignore_id);
        }

        $exists = $query->exists();

        return response()->json([
            'available' => !$exists
        ]);
    }

    private function perCurrencyNetSubquery(?int $currencyId = null)
    {
        $q = DB::table('transactions')
            ->select([
                'account_id',
                'currency_id',
                DB::raw("SUM(CASE WHEN transaction_type='credit' THEN amount ELSE -amount END) AS net_balance")
            ])
            ->where('status', 'active')
            ->groupBy('account_id', 'currency_id');

        if ($currencyId) {
            $q->where('currency_id', $currencyId);
        }
        return $q;
    }

    private function dominantBalanceSubquery()
    {
        // First layer: per-currency net (all currencies)
        $perCur = $this->perCurrencyNetSubquery(null);

        // Second layer: aggregate per account
        // - dominant_balance = either the largest positive or the most negative,
        //   whichever has greater absolute value.
        // - has_pos / has_neg to support "Mixed" filter
        $sql = DB::query()->fromSub($perCur, 't')
            ->selectRaw("
                account_id,
                MAX(CASE WHEN net_balance > 0 THEN net_balance ELSE 0 END) AS max_pos,
                MIN(CASE WHEN net_balance < 0 THEN net_balance ELSE 0 END) AS min_neg,
                CASE
                    WHEN ABS(COALESCE(MAX(CASE WHEN net_balance > 0 THEN net_balance END), 0))
                    >= ABS(COALESCE(MIN(CASE WHEN net_balance < 0 THEN net_balance END), 0))
                    THEN COALESCE(MAX(CASE WHEN net_balance > 0 THEN net_balance END), 0)
                    ELSE COALESCE(MIN(CASE WHEN net_balance < 0 THEN net_balance END), 0)
                END AS dominant_balance,
                CASE WHEN SUM(CASE WHEN net_balance > 0 THEN 1 ELSE 0 END) > 0 THEN 1 ELSE 0 END AS has_pos,
                CASE WHEN SUM(CASE WHEN net_balance < 0 THEN 1 ELSE 0 END) > 0 THEN 1 ELSE 0 END AS has_neg
            ")
            ->groupBy('account_id');

        return $sql;
    }

    public function fetchAccounts(Request $request)
    {
        if (!function_exists('color_mix')) {
            function color_mix($hex, $mix, $percent)
            {
                $hex = str_replace('#', '', $hex);
                $mix = str_replace('#', '', $mix);
                $r = (hexdec(substr($hex, 0, 2)) * (1 - $percent)) + (hexdec(substr($mix, 0, 2)) * $percent);
                $g = (hexdec(substr($hex, 2, 2)) * (1 - $percent)) + (hexdec(substr($mix, 2, 2)) * $percent);
                $b = (hexdec(substr($hex, 4, 2)) * (1 - $percent)) + (hexdec(substr($mix, 4, 2)) * $percent);
                return sprintf('#%02x%02x%02x', $r, $g, $b);
            }
        }

        $accounts = Account::query()->where('is_active', true)->orderBy('created_at', 'asc');

        // Filter by account type (main category)
        if ($request->filled('account_type_id')) {
            $accounts->whereHas('subCategory', function ($q) use ($request) {
                $q->where('account_sub_category_id', $request->account_type_id);
            });
        }

        // Filter by sub-category (only if provided)
        if ($request->filled('account_sub_category_id')) {
            $accounts->where('account_sub_category_id', $request->account_sub_category_id);
        }

        // Search
        if ($request->filled('search_term')) {
            $search = $request->search_term;
            $accounts->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // NEW: Balance filter & ordering
        $currencyId    = $request->integer('currency_id') ?: null;
        $balanceFilter = $request->input('balance_filter'); // '', 'creditors', 'debtors', 'mixed'

        if ($currencyId) {
            // Per selected currency ranking
            $perCurrency = $this->perCurrencyNetSubquery($currencyId);
            $accounts->leftJoinSub($perCurrency, 'bal', function ($join) {
                $join->on('bal.account_id', '=', 'accounts.id');
            });

            if ($balanceFilter === 'creditors') {
                $accounts->where('bal.net_balance', '>', 0)
                    ->orderByDesc('bal.net_balance');
            } elseif ($balanceFilter === 'debtors') {
                $accounts->where('bal.net_balance', '<', 0)
                    ->orderBy('bal.net_balance', 'asc');
            } elseif ($balanceFilter === 'mixed') {
                // With a specific currency selected, "mixed" doesn't apply meaningfully.
                // Show accounts that have both + and - across all currencies but still rank by selected currency.
                $dom = $this->dominantBalanceSubquery();
                $accounts->leftJoinSub($dom, 'dom', function ($join) {
                    $join->on('dom.account_id', '=', 'accounts.id');
                })->where('dom.has_pos', 1)->where('dom.has_neg', 1)
                    ->orderByDesc(DB::raw('COALESCE(bal.net_balance, 0)'));
            } else {
                // Default ordering if no explicit balance filter: keep your created_at desc
                $accounts->orderBy('created_at', 'desc');
            }

            // Make bal.* selectable
            $accounts->addSelect('accounts.*', DB::raw('COALESCE(bal.net_balance, 0) as _net_balance'));
        } else {
            // No specific currency: use dominant balance per account
            $dom = $this->dominantBalanceSubquery();
            $accounts->leftJoinSub($dom, 'dom', function ($join) {
                $join->on('dom.account_id', '=', 'accounts.id');
            });

            if ($balanceFilter === 'creditors') {
                $accounts->where('dom.dominant_balance', '>', 0)
                    ->orderByDesc('dom.dominant_balance');
            } elseif ($balanceFilter === 'debtors') {
                $accounts->where('dom.dominant_balance', '<', 0)
                    ->orderBy('dom.dominant_balance', 'asc');
            } elseif ($balanceFilter === 'mixed') {
                // Must have at least one positive and one negative currency balance
                $accounts->where('dom.has_pos', 1)->where('dom.has_neg', 1)
                    ->orderByDesc('dom.dominant_balance');
            } else {
                $accounts->orderBy('created_at', 'desc');
            }

            $accounts->addSelect(
                'accounts.*',
                DB::raw('COALESCE(dom.dominant_balance, 0) as _dominant_balance'),
                DB::raw('COALESCE(dom.has_pos, 0) as _has_pos'),
                DB::raw('COALESCE(dom.has_neg, 0) as _has_neg')
            );
        }

        // DataTables
        return DataTables::of($accounts)
            ->addColumn('name_with_profile', function ($account) {
                $bgColor = $account->profile_bg ?? '#4c6ef5';
                $accountUrl = route('admin.accounts.show', $account->id);
                $code = e($account->code);
                $shortCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $account->code), 0, 3));

                return '
                <div class="d-flex align-items-center gap-3">

                    <!-- Avatar -->
                    <a href="' . $accountUrl . '">
                        <div style="
                            width:48px;height:48px;border-radius:12px;
                            background:linear-gradient(145deg, ' . $bgColor . ', ' . color_mix($bgColor, '#000', 0.18) . ');
                            color:#fff;font-weight:700;font-size:15px;
                            display:flex;align-items:center;justify-content:center;
                            box-shadow:0 4px 12px rgba(0,0,0,.12);
                        ">
                            ' . $shortCode . '
                        </div>
                    </a>

                    <!-- Text -->
                    <div style="line-height:1.3">
                        <a href="' . $accountUrl . '"
                        style="font-size:15px;font-weight:600;color:#1d1f27;text-decoration:none;">
                            ' . e($account->name) . '
                        </a>
                        <div style="font-size:12px;color:#6c757d;">' . $code . '</div>

                        ' . ($account->user ? '
                        <span class="badge bg-light border mt-1"
                            style="font-size:11px;color:#212529;">
                            <i class="bi bi-person-circle text-primary"></i>
                            ' . e($account->user->username) . '
                        </span>' : '') . '
                    </div>
                </div>';
            })
            ->addColumn('account_type_with_icon', function ($account) {
                $cat = optional($account->subCategory)->accountCategory;

                return '
                <div class="d-flex flex-column" style="line-height:1.2;">
                    <div class="d-flex align-items-center">
                        ' . ($cat ? '<i class="bi ' . $cat->bi_icon . '"
                                style="font-size:20px;color:' . $cat->bi_icon_color . ';margin-right:6px;"></i>' : '') . '
                        <span style="font-size:15px;font-weight:600;">
                            ' . e(__('accounts.types')[$cat->name ?? ''] ?? ($cat->name ?? __('accounts.no_category'))) . '
                        </span>
                    </div>
                    <span style="font-size:12px;color:#6c757d;margin-left:26px;">
                        ' . e(__('accounts.types')[$account->subCategory->name ?? ''] ?? ($account->subCategory->name ?? __('accounts.no_subcategory'))) . '
                    </span>
                </div>';
            })
            ->addColumn('balance', function ($account) use ($currencyId) {
                $balances = DB::table('transactions')
                    ->select('currency_id', DB::raw("
                    SUM(CASE WHEN transaction_type='credit' THEN amount ELSE 0 END) -
                    SUM(CASE WHEN transaction_type='debit'  THEN amount ELSE 0 END) AS balance
                "))
                    ->where('account_id', $account->id)
                    ->where('status', 'active')
                    ->groupBy('currency_id')
                    ->orderBy('currency_id', 'asc')
                    ->when($currencyId, fn($q) => $q->havingRaw('currency_id = ?', [$currencyId]))
                    ->get();

                if ($balances->isEmpty()) return '—';

                $html = '<div class="d-flex flex-column gap-1">';

                foreach ($balances as $b) {
                    $currency = \App\Models\Currency::find($b->currency_id);

                    $flag = $currency
                        ? "<img src='/assets/flags/" . strtolower(substr($currency->code, 0, 2)) . ".svg'
                        height='14' width='18' style='border-radius:3px;'>"
                        : '';

                    $color = $b->balance < 0 ? '#c92a2a' : '#2f6ff7';

                    $html .= "
                    <div style='
                        padding:4px 10px;
                        background:#f8f9fa;border-radius:8px;
                        display:flex;align-items:center;gap:6px;
                        font-size:13px;font-weight:600;color:{$color};
                        border:1px solid #e4e6ef;
                    '>
                        <div style='flex:1'>{$flag}</div>
                        <div style='flex:1'>" . format_amount(abs($b->balance), 2) . "</div>
                        <div style='flex:1'>{$currency->code}</div>
                    </div>";
                }

                return $html . '</div>';
            })
            ->addColumn('contact_details', function ($account) {
                return '
                <div style="font-size:12px;line-height:1.4;color:#495057;">
                    <div><b>' . e(__('ui.contact')) . ':</b> ' . e($account->contact) . '</div>
                    <div><b>' . e(__('ui.address')) . ':</b> ' . e($account->address) . '</div>
                    <div><b>' . e(__('ui.note')) . ':</b> ' . e($account->company) . '</div>
                </div>';
            })
            ->addColumn('address', function ($account) {
                $address = trim((string) $account->address);
                $contact = trim((string) $account->contact);

                $html = '<div class="d-flex flex-column gap-1">'
                    . '<span>' . e($address !== '' ? $address : '—') . '</span>';

                if ($contact !== '') {
                    $html .= '<span class="small text-muted" dir="ltr">'
                        . '<i class="bi bi-telephone me-1"></i>' . e($contact)
                        . '</span>';
                }

                return $html . '</div>';
            })
            ->addColumn('actions', function ($account) {
                $show_url     = route('admin.accounts.show', $account->id);
                $whatsapp_url = route('admin.accounts.send-whatsapp', $account->id);

                $btn = '';

                // VIEW
                if (auth()->user()->can('view customers')) {
                    $btn .= '
                    <button class="btn btn-light btn-sm shadow-sm"
                            onclick="window.location=\'' . $show_url . '\'"
                            style="border:1px solid #dce0ef;border-radius:8px;">
                        <i class="bi bi-eye" style="font-size:18px;color:#4c6ef5;"></i>
                    </button>';
                }

                // PREVIEW + EDIT
                // if (auth()->user()->can('update customers')) {
                //     $btn .= '
                //     <button class="btn btn-light btn-sm shadow-sm btn-preview-account"
                //             data-id="' . $account->id . '"
                //             style="border:1px solid #dce0ef;border-radius:8px;">
                //         <i class="bi bi-printer" style="font-size:18px;color:#222;"></i>
                //     </button>';
                // }
                if (auth()->user()->can('update customers')) {
                    $btn .= '
                    <button class="btn btn-light btn-sm shadow-sm btn-edit-account"
                            data-id="' . $account->id . '"
                            style="border:1px solid #ffe8a1;border-radius:8px;">
                        <i class="bi bi-pencil" style="font-size:18px;color:#e0a800;"></i>
                    </button>';
                }

                // DELETE
                if (
                    auth()->user()->can('delete customers') &&
                    optional($account->subCategory?->accountCategory)->id != 2
                ) {
                    $btn .= '
                    <button class="btn btn-light btn-sm shadow-sm btn-delete-account"
                            data-url="' . route('admin.accounts.destroy', $account->id) . '"
                            style="border:1px solid #ffe8a1;border-radius:8px;">
                        <i class="bi bi-trash" style="font-size:18px;color:red;"></i>
                    </button>';
                }

                // WHATSAPP
                if (auth()->user()->can('send-whatsapp customers')) {
                    $btn .= '
                    <button class="btn btn-light btn-sm shadow-sm btn-send-whatsapp"
                            data-url="' . $whatsapp_url . '"
                            style="border:1px solid #c3e6cb;border-radius:8px;">
                        <i class="bi bi-whatsapp" style="font-size:18px;color:#28a745;"></i>
                    </button>';
                }

                return '<div class="d-flex gap-1">' . $btn . '</div>';
            })

            ->rawColumns(['name_with_profile', 'account_type_with_icon', 'address', 'balance', 'contact_details', 'actions'])
            ->make(true);
    }

    public function getAccountInfo($id)
    {
        $account = Account::select('id', 'name', 'code')->findOrFail($id);
        return response()->json($account);
    }

    public function sourceEdit(Transaction $transaction)
    {
        $tableName = strtolower(trim((string) $transaction->table_name));

        if ($tableName === 'journal') {

            $linkedId = $transaction->linked_id ?: $transaction->id;

            $transactions = Transaction::where('linked_id', $linkedId)->get();

            if ($transactions->isEmpty()) {
                $transactions = collect([$transaction]);
            }

            $cashId = Account::where('account_sub_category_id', 3)->value('id');

            $mode = 'no_track';
            $trackAccountId = null;

            $hasCashRow = $transactions->contains(function ($trx) use ($cashId) {
                return (int) $trx->account_id === (int) $cashId || (int) $trx->is_visible === 0;
            });

            $visibleRows = $transactions->where('is_visible', 1)->values();

            if ($hasCashRow) {
                $mode = 'treasure';
            } elseif ($visibleRows->count() >= 2) {
                $mode = 'track';

                $trackRow = $visibleRows->first(function ($trx) use ($transaction) {
                    return (int) $trx->account_id !== (int) $transaction->account_id;
                });

                $trackAccountId = $trackRow?->account_id;
            }

            return response()->json([
                'success' => true,
                'module' => 'journal',
                'transaction' => [
                    'id' => $transaction->id,
                    'linked_id' => $linkedId,
                    'account_id' => $transaction->account_id,

                    'date' => !empty($transaction->date)
                        ? \Carbon\Carbon::parse($transaction->date)->format('Y-m-d')
                        : optional($transaction->created_at)->format('Y-m-d'),

                    'created_at' => optional($transaction->created_at)->format('Y-m-d'),

                    'transaction_type' => $transaction->transaction_type,
                    'currency_id' => $transaction->currency_id,

                    'amount' => $transaction->amount !== null ? (string) $transaction->amount : '',
                    'amount_raw' => $transaction->getRawOriginal('amount') !== null
                        ? (string) $transaction->getRawOriginal('amount')
                        : '',

                    'payment_amount' => $transaction->payment_amount !== null
                        ? (string) $transaction->payment_amount
                        : '',

                    'note' => $transaction->note,
                    'reference' => $transaction->reference,

                    'transaction_mode' => $mode,
                    'track_account_id' => $trackAccountId,
                ],
            ]);
        }

        if ($tableName === 'remittances') {
            $remittance = DB::table('remittances')
                ->where('id', $transaction->table_row_id)
                ->first();

            if (!$remittance) {
                return response()->json([
                    'success' => false,
                    'message' => __('accounts.remittance_missing'),
                ], 404);
            }

            $remittanceArray = (array) $remittance;

            $remittanceArray['date'] = !empty($remittance->date)
                ? \Carbon\Carbon::parse($remittance->date)->format('Y-m-d')
                : (!empty($remittance->created_at)
                    ? \Carbon\Carbon::parse($remittance->created_at)->format('Y-m-d')
                    : null);

            $remittanceArray['created_at'] = !empty($remittance->created_at)
                ? \Carbon\Carbon::parse($remittance->created_at)->format('Y-m-d')
                : null;

            foreach (
                [
                    'amount',
                    'partner_amount',
                    'actual_amount',
                    'commission_kabul_amount',
                    'commission_partner_amount',
                ] as $field
            ) {
                $remittanceArray[$field] = isset($remittance->{$field})
                    ? (string) $remittance->{$field}
                    : '';
            }

            return response()->json([
                'success' => true,
                'module' => 'remittance',
                'remittance_type' => $remittance->type,
                'remittance' => $remittanceArray,
                'transaction' => [
                    'id' => $transaction->id,
                    'linked_id' => $transaction->linked_id,
                    'table_name' => $transaction->table_name,
                    'table_row_id' => $transaction->table_row_id,
                ],
            ]);
        }

        if ($tableName === 'exchanges') {
            $exchange = DB::table('exchanges')
                ->where('id', $transaction->table_row_id)
                ->first();

            if (!$exchange) {
                return response()->json([
                    'success' => false,
                    'message' => __('accounts.exchange_missing'),
                ], 404);
            }

            $exchangeArray = (array) $exchange;

            $exchangeArray['date'] = !empty($exchange->date)
                ? \Carbon\Carbon::parse($exchange->date)->format('Y-m-d')
                : (!empty($exchange->created_at)
                    ? \Carbon\Carbon::parse($exchange->created_at)->format('Y-m-d')
                    : null);

            $exchangeArray['created_at'] = !empty($exchange->created_at)
                ? \Carbon\Carbon::parse($exchange->created_at)->format('Y-m-d')
                : null;

            foreach (['from_amount', 'to_amount', 'rate', 'base_rate', 'usd_amount', 'profit'] as $field) {
                $exchangeArray[$field] = isset($exchange->{$field})
                    ? (string) $exchange->{$field}
                    : '';
            }

            return response()->json([
                'success' => true,
                'module' => 'exchange',
                'exchange' => $exchangeArray,
                'transaction' => [
                    'id' => $transaction->id,
                    'linked_id' => $transaction->linked_id,
                    'table_name' => $transaction->table_name,
                    'table_row_id' => $transaction->table_row_id,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('accounts.source_not_editable'),
            'table_name' => $transaction->table_name,
        ], 422);
    }

    private function whatsappUrl(string $message, ?string $phone = null): string
    {
        $phone = $phone ? preg_replace('/[^0-9]/', '', $phone) : '';
        $parameters = ['text' => $message];

        if ($phone) {
            $parameters = ['phone' => $phone] + $parameters;
        }

        return 'https://api.whatsapp.com/send/?' . http_build_query(
            $parameters,
            '',
            '&',
            PHP_QUERY_RFC3986
        );
    }

    private function formatWaAmount($amount): string
    {
        return number_format((float) $amount, 0);
    }

    private function buildAccountBalanceMessage($account, $setting, array $balances): string
    {
        $companyName = $setting?->company_name ?? __('accounts.company');
        $accountId = '0000' . $account->id;

        $message = "*{$companyName}*\n\n";
        $message .= "📅 تاریخ: " . $this->formatAccountWhatsAppDate(now()) . "\n";
        $message .= "\u{200F}🆔 آیدی حساب: *\u{2066}{$accountId}\u{2069}*\n";
        $message .= "👤 نام حساب: *{$account->name}*\n";
        $message .= "\n📊 *خلاصه بیلانس حساب*\n";
        $message .= "━━━━━━━━━━━━\n";

        if (empty($balances)) {
            $message .= "⚪ بیلانس موجود نیست\n";
        } else {
            foreach ($balances as $data) {
                $rawBalance = (float) ($data['balance'] ?? 0);
                $balance = $this->formatWaBalanceAmount($rawBalance);
                $currency = $data['currency'] ?? '-';
                $statusIcon = $rawBalance > 0
                    ? '🟢'
                    : ($rawBalance < 0 ? '🔴' : '⚪');

                $message .= "{$statusIcon} بیلانس {$currency}: *{$balance}*\n";
            }
        }

        $message .= "━━━━━━━━━━━━";

        return $message;
    }

    private function formatAccountWhatsAppDate($date): string
    {
        $jalali = Jalalian::fromCarbon(\Illuminate\Support\Carbon::parse($date));
        $months = [
            1 => 'حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله',
            'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت',
        ];

        return $this->toAccountPersianDigits($jalali->getDay())
            . ' - ' . $months[$jalali->getMonth()]
            . ' - ' . $this->toAccountPersianDigits($jalali->getYear());
    }

    private function formatWaBalanceAmount($amount): string
    {
        return rtrim(rtrim(number_format((float) $amount, 2, '.', ','), '0'), '.');
    }

    private function toAccountPersianDigits($value): string
    {
        return strtr((string) $value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }

    private function buildTransactionMessage($row, $account, $setting): string
    {
        $companyName = $setting->company_name ?? __('accounts.company');

        $amount = $this->formatWaAmount($row->amount ?? 0);
        $paymentAmount = $this->formatWaAmount($row->payment_amount ?? 0);

        $currencyCode = $row->currency_code
            ?? optional($row->currency)->code
            ?? '';

        $paymentCurrencyCode = $row->payment_currency_code
            ?? optional($row->paymentCurrency)->code
            ?? $currencyCode;

        $isCredit = ($row->transaction_type ?? '') === 'credit';

        $typeLine = $isCredit
            ? "📗 *" . __('accounts.credit_transaction') . "*"
            : "📕 *" . __('accounts.debit_transaction') . "*";

        $typeText = $isCredit
            ? __('accounts.credited', ['amount' => $amount, 'currency' => $currencyCode])
            : __('accounts.debited', ['amount' => $amount, 'currency' => $currencyCode]);

        $message  = "*{$companyName}*\n";
        $message .= "--------------------------------\n";
        $message .= "{$typeLine}\n\n";

        $message .= __('accounts.dear', ['name' => $account->name]) . "\n";
        $message .= "{$typeText}\n\n";

        $message .= "💰 *" . __('ui.amount') . ":* {$amount} {$currencyCode}\n";
        $message .= "💵 *" . __('accounts.payment_amount') . ":* {$paymentAmount} {$paymentCurrencyCode}\n";
        $message .= "🏷 *" . __('ui.account') . ":* {$account->code}\n";
        $message .= "🧾 *" . __('ui.blade_reference') . ":* TXN-{$row->id}\n";

        if (!empty($row->created_at)) {
            $message .= "📅 *" . __('ui.date') . ":* " . \Carbon\Carbon::parse($row->created_at)->format('Y-m-d h:i A') . "\n";
        }

        if (!empty($row->note)) {
            $message .= "📝 *" . __('ui.note') . ":* {$row->note}\n";
        }

        $message .= "--------------------------------\n";
        $message .= __('accounts.verify_transaction');

        if (!empty($setting->contact)) {
            $message .= "\n" . __('accounts.inquiries', ['contact' => $setting->contact]);
        }

        return $message;
    }

    public function whatsappBalance(Request $request, Account $account)
    {
        $setting = \App\Models\Setting::first();

        $query = \App\Models\Transaction::query()
            ->where('account_id', $account->id)
            ->where('status', 'active')
            ->where('is_visible', true)
            ->with('currency');

        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->get();

        $balances = [];

        foreach ($transactions as $transaction) {
            $currencyId = (int) $transaction->currency_id;

            if (!isset($balances[$currencyId])) {
                $balances[$currencyId] = [
                    'credit' => 0,
                    'debit' => 0,
                    'balance' => 0,
                ];
            }

            $amount = (float) $transaction->amount;

            if ($transaction->transaction_type === 'credit') {
                $balances[$currencyId]['credit'] += $amount;
                $balances[$currencyId]['balance'] += $amount;
            } else {
                $balances[$currencyId]['debit'] += $amount;
                $balances[$currencyId]['balance'] -= $amount;
            }
        }

        $balanceLines = Currency::active()
            ->when($request->filled('currency_id'), fn($query) => $query->whereKey($request->currency_id))
            ->orderBy('id')
            ->get()
            ->map(function (Currency $currency) use ($balances) {
                return [
                    'currency' => $currency->name ?: $currency->code,
                    'balance' => (float) ($balances[$currency->id]['balance'] ?? 0),
                ];
            })
            ->all();

        $message = $this->buildAccountBalanceMessage($account, $setting, $balanceLines);

        return redirect()->away(
            $this->whatsappUrl($message, $account->contact ?? null)
        );
    }
}
