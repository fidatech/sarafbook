<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{
    Transaction,
    Account,
    Currency,
    Setting
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class TransactionsController extends Controller
{
    public function bulkMeta(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:transactions,id'],
            'color' => ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_ok' => ['sometimes', 'boolean'],
        ]);

        if (!array_key_exists('color', $validated) && !array_key_exists('is_ok', $validated)) {
            return response()->json(['message' => __('transactions.choose_meta')], 422);
        }

        $updates = [];
        if (array_key_exists('color', $validated)) {
            $updates['color'] = $validated['color'] ?: null;
        }
        if (array_key_exists('is_ok', $validated)) {
            $updates['is_ok'] = $validated['is_ok'];
        }

        $updated = Transaction::query()->whereIn('id', $validated['ids'])->update($updates);

        return response()->json([
            'message' => __('transactions.updated_count', ['count' => $updated]),
            'updated' => $updated,
        ]);
    }

    /**
     * The transactions page: the form for a new entry (or changing one) and the list of every
     * visible transaction, filtered and paged on the server, with the totals of what is shown.
     */
    public function index(Request $request)
    {
        $currencies = Currency::orderBy('id')->get();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'account_id' => $request->integer('account_id') ?: null,
            'currency_id' => $request->integer('currency_id') ?: null,
            'type' => in_array($request->query('type'), ['credit', 'debit'], true) ? $request->query('type') : '',
            'source' => in_array($request->query('source'), ['journal', 'exchanges', 'remittances', 'expenses'], true) ? $request->query('source') : '',
            'from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('from')) ? $request->query('from') : '',
            'to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('to')) ? $request->query('to') : '',
        ];
        $filtered = collect($filters)->filter(fn ($v) => $v !== '' && $v !== null)->isNotEmpty();

        $base = function () use ($filters) {
            return Transaction::query()
                ->where('transactions.is_visible', true)
                ->when($filters['account_id'], fn ($q, $v) => $q->where('transactions.account_id', $v))
                ->when($filters['currency_id'], fn ($q, $v) => $q->where('transactions.currency_id', $v))
                ->when($filters['type'] !== '', fn ($q) => $q->where('transactions.transaction_type', $filters['type']))
                ->when($filters['source'] === 'journal', fn ($q) => $q->where(fn ($w) => $w->where('transactions.table_name', 'Journal')->orWhereNull('transactions.table_name')))
                ->when(in_array($filters['source'], ['exchanges', 'remittances', 'expenses'], true), fn ($q) => $q->where('transactions.table_name', $filters['source']))
                ->when($filters['from'] !== '', fn ($q) => $q->where('transactions.created_at', '>=', $filters['from'].' 00:00:00'))
                ->when($filters['to'] !== '', fn ($q) => $q->where('transactions.created_at', '<=', $filters['to'].' 23:59:59'))
                ->when($filters['q'] !== '', function ($q) use ($filters) {
                    $term = '%'.$filters['q'].'%';
                    $q->where(fn ($w) => $w->where('transactions.note', 'like', $term)->orWhere('transactions.reference', 'like', $term)
                        ->orWhereIn('transactions.account_id', Account::query()->where('name', 'like', $term)->orWhere('code', 'like', $term)->select('id')));
                });
        };

        $rows = $base()->with(['account:id,name,code,account_sub_category_id', 'currency:id,code'])
            ->orderByDesc('transactions.created_at')->orderByDesc('transactions.id')
            ->paginate(25)->withQueryString();

        // totals of what the filters show; without filters, of today, so the page stays fast
        $totalsQuery = $filtered ? $base() : $base()->where('transactions.created_at', '>=', now()->startOfDay());
        $totals = $totalsQuery->join('currencies as c', 'c.id', '=', 'transactions.currency_id')
            ->groupBy('c.id', 'c.code')->orderBy('c.id')
            ->selectRaw("c.code, SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) AS credit,
                SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) AS debit, COUNT(*) AS n")
            ->get();

        $editing = null;
        if ($request->filled('edit')) {
            $editing = $this->fetch((int) $request->query('edit'))->getData(true)['transaction'] ?? null;
        }

        return page('admin.transactions.index', [
            'accounts' => Account::query()->where('is_active', true)->orderBy('code')->get(['id', 'name', 'code', 'account_sub_category_id']),
            'currencies' => $currencies,
            'rows' => $rows,
            'totals' => $totals,
            'filters' => $filters,
            'filtered' => $filtered,
            'editing' => $editing,
            'cashId' => Account::where('account_sub_category_id', 3)->value('id'),
        ], ['list', 'totals']);
    }

    public function data(Request $request)
    {
        $query = Transaction::with(['account', 'currency'])
            ->where('is_visible', true)
            ->when($request->account_id, fn($q) => $q->where('account_id', $request->account_id))
            ->when($request->currency_id, fn($q) => $q->where('currency_id', $request->currency_id))
            ->when($request->transaction_type, fn($q) => $q->where('transaction_type', $request->transaction_type))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('date_range')) {
            [$start, $end] = explode(' - ', $request->date_range);

            $query->whereBetween('created_at', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ]);
        }

        $summary = $this->buildSummary($request);

        return datatables()->of($query)
            ->addIndexColumn()
            ->addColumn('account_code', fn($row) => $row->account->code ?? '')
            ->addColumn('account_name', fn($row) => $row->account->name ?? '-')
            ->addColumn('currency_code', fn($row) => $row->currency->code ?? '-')
            ->addColumn('amount', function ($row) {
                $color = $row->transaction_type === 'credit' ? 'text-success' : 'text-danger';
                $prefix = $row->transaction_type === 'credit' ? '+' : '-';

                return '<span class="' . $color . '">' . $prefix . format_amount($row->amount, 0) . '</span>';
            })
            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)->format('Y-m-d H:i:s');
            })
            ->addColumn('account', fn($row) => [
                'code'       => $row->account_code,
                'profile_bg' => $row->account_bg,
                'name'       => $row->account->name ?? ''
            ])
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
            ->addColumn('actions', function ($row) {
                return view('admin.transactions.partials.actions', compact('row'))->render();
            })
            ->with(['summary' => $summary])
            ->rawColumns(['actions', 'amount', 'attachment'])
            ->make(true);
    }

    public function print(Transaction $transaction)
    {
        abort_unless(strcasecmp((string) $transaction->table_name, 'Journal') === 0, 404);

        $transaction->load(['account', 'currency']);

        $balance = Transaction::query()
            ->where('account_id', $transaction->account_id)
            ->where('currency_id', $transaction->currency_id)
            ->where(function ($query) use ($transaction) {
                $query->where('created_at', '<', $transaction->created_at)
                    ->orWhere(function ($query) use ($transaction) {
                        $query->where('created_at', $transaction->created_at)
                            ->where('id', '<=', $transaction->id);
                    });
            })
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END), 0) AS balance")
            ->value('balance');

        $settings = Setting::query()->first();

        return view('admin.transactions.print', compact('transaction', 'balance', 'settings'));
    }

    public function getAccountBalances(Request $request)
    {
        $balances = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.code as currency',
                DB::raw("SUM(CASE WHEN transaction_type='credit' THEN amount ELSE 0 END) AS credit"),
                DB::raw("SUM(CASE WHEN transaction_type='debit'  THEN amount ELSE 0 END) AS debit")
            )
            ->where('account_id', $request->account_id)
            ->groupBy('currencies.code')
            ->get();

        $result = [];
        foreach ($balances as $b) {
            $result[$b->currency] = [
                'credit'  => $b->credit,
                'debit'   => $b->debit,
                'balance' => $b->credit - $b->debit
            ];
        }

        return response()->json(['balances' => $result]);
    }

    public function fetch($id)
    {
        $t = Transaction::findOrFail($id);

        $linkedId = $t->linked_id ?: $t->id;

        $transactions = Transaction::where('linked_id', $linkedId)->get();

        if ($transactions->isEmpty()) {
            $transactions = collect([$t]);
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

            $trackRow = $visibleRows->first(function ($trx) use ($t) {
                return (int) $trx->account_id !== (int) $t->account_id;
            });

            $trackAccountId = $trackRow?->account_id;
        }

        return response()->json([
            'transaction' => [
                'id'                => $t->id,
                'linked_id'         => $linkedId,
                'account_id'        => $t->account_id,
                'currency_id'       => $t->currency_id,
                'amount'            => $t->amount,
                'amount_raw'        => $t->amount,
                'transaction_type'  => $t->transaction_type,
                'note'              => $t->note,
                'reference'         => $t->reference,
                'date'              => optional($t->created_at)->format('Y-m-d'),
                'created_at'        => optional($t->created_at)->format('Y-m-d H:i:s'),
                'transaction_mode'  => $mode,
                'track_account_id'  => $trackAccountId,
            ],
            'linked_id' => $linkedId,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'account_id'            => 'required|exists:accounts,id',
            'date'                  => 'required|date',
            'entries'               => 'required|array|min:1',
            'entries.*.currency_id' => 'required|exists:currencies,id',
            'entries.*.amount'      => 'required|numeric|min:0.01',
            'entries.*.type'        => 'required|in:credit,debit',
            'entries.*.attachment'  => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,pdf|max:5120',
            'transaction_mode'      => 'required|in:treasure,no_track,track',
            'track_account_id'      => 'nullable|required_if:transaction_mode,track|exists:accounts,id|different:account_id',
            'reference'             => 'nullable|string|max:255',
        ], [
            'track_account_id.required_if' => __('Choose the other account.'),
            'track_account_id.different' => __('The other account must be a different account.'),
            'entries.*.attachment.mimes' => __('Only pictures (JPG, PNG, WebP, GIF) and PDF files can be attached.'),
        ]);

        $accountId = $request->account_id;
        $mode      = $request->transaction_mode;
        $trackId   = $request->track_account_id;
        $date      = $request->date;
        $reference = $request->reference;

        $cashId = Account::where('account_sub_category_id', 3)->value('id');

        $account = Account::findOrFail($accountId);
        $trackAccount = $trackId ? Account::find($trackId) : null;

        DB::transaction(function () use ($request, $accountId, $mode, $trackId, $cashId, $account, $trackAccount, $date, $reference) {

            foreach ($request->entries as $index => $entry) {

                $linkedId = (string) Str::uuid();

                $amount   = $entry['amount'];
                $currency = $entry['currency_id'];
                $type     = $entry['type'];

                $currencyObj = Currency::find($currency);

                $customNote = trim($entry['note'] ?? '');

                if ($customNote !== '') {
                    $note = $customNote;
                } else {
                    $note = $this->transactionNote(
                        $mode,
                        $type,
                        $amount,
                        $currencyObj->name ?: $currencyObj->code,
                        $account,
                        $trackAccount
                    );
                }

                $attachmentPath = null;

                if (isset($entry['attachment']) && $entry['attachment'] instanceof \Illuminate\Http\UploadedFile) {
                    $file = $entry['attachment'];

                    $folder = 'attachments/' . Carbon::parse($date)->format('Y/m') . '/account_' . $accountId;

                    if (!file_exists(tenant_public_path($folder))) {
                        mkdir(tenant_public_path($folder), 0755, true);
                    }

                    $name = time() . '_' . Str::random(8) . '.' . upload_extension($file);

                    $file->move(tenant_public_path($folder), $name);

                    $attachmentPath = $folder . '/' . $name;
                }

                if ($mode === 'treasure') {

                    if ($accountId == $cashId) {
                        $this->postTransaction($linkedId, $accountId, $currency, $amount, $type, $note, 1, 1, $attachmentPath, $date, $reference);
                    } else {
                        $this->postTransaction($linkedId, $accountId, $currency, $amount, $type, $note, 1, 1, $attachmentPath, $date, $reference);
                        $this->postTransaction($linkedId, $cashId, $currency, $amount, $type, $note, 1, 0, $attachmentPath, $date, $reference);
                    }
                } elseif ($mode === 'no_track') {

                    $this->postTransaction($linkedId, $accountId, $currency, $amount, $type, $note, 1, 1, $attachmentPath, $date, $reference);
                } elseif ($mode === 'track') {

                    if (!$trackId) {
                        throw new \Exception('Track account required');
                    }

                    if ($type === 'credit') {
                        $this->postTransaction($linkedId, $accountId, $currency, $amount, 'credit', $note, 1, 1, $attachmentPath, $date, $reference);
                        $this->postTransaction($linkedId, $trackId, $currency, $amount, 'debit', $note, 1, 1, $attachmentPath, $date, $reference);
                    } else {
                        $this->postTransaction($linkedId, $accountId, $currency, $amount, 'debit', $note, 1, 1, $attachmentPath, $date, $reference);
                        $this->postTransaction($linkedId, $trackId, $currency, $amount, 'credit', $note, 1, 1, $attachmentPath, $date, $reference);
                    }
                }
            }
        });

        return done(__('transactions.saved_transactions'), route('admin.transactions.index'));
    }

    public function update(Request $request, $linkedId)
    {
        $request->validate([
            'account_id'            => 'required|exists:accounts,id',
            'date'                  => 'required|date',
            'entries'               => 'required|array|min:1',
            'entries.*.currency_id' => 'required|exists:currencies,id',
            'entries.*.amount'      => 'required|numeric|min:0.01',
            'entries.*.type'        => 'required|in:credit,debit',
            'entries.*.attachment'  => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,pdf|max:5120',
            'transaction_mode'      => 'nullable|in:treasure,no_track,track',
            'track_account_id'      => 'nullable|required_if:transaction_mode,track|exists:accounts,id|different:account_id',
            'reference'             => 'nullable|string|max:255',
        ], [
            'track_account_id.required_if' => __('Choose the other account.'),
            'track_account_id.different' => __('The other account must be a different account.'),
            'entries.*.attachment.mimes' => __('Only pictures (JPG, PNG, WebP, GIF) and PDF files can be attached.'),
        ]);

        $accountId = $request->account_id;
        $date      = $request->date;
        $reference = $request->reference;

        $cashId = Account::where('account_sub_category_id', 3)->value('id');

        $oldTransactions = Transaction::where('linked_id', $linkedId)->get();

        if ($oldTransactions->isEmpty()) {
            $oldTransactions = Transaction::where('id', $linkedId)->get();
        }

        if ($oldTransactions->isEmpty()) {
            return done(__('transactions.original_missing'), route('admin.transactions.index'), false, 404);
        }

        $oldMeta = $this->detectTransactionModeFromRows($oldTransactions, $accountId, $cashId);

        $mode = $request->filled('transaction_mode')
            ? $request->transaction_mode
            : $oldMeta['mode'];

        $trackId = $request->filled('track_account_id')
            ? $request->track_account_id
            : $oldMeta['track_account_id'];

        if ($mode === 'track' && !$trackId) {
            return done(__('transactions.track_required'), null, false, 422);
        }

        $account      = Account::findOrFail($accountId);
        $trackAccount = $trackId ? Account::find($trackId) : null;

        $originalCreatedAt = $oldTransactions
            ->sortBy('created_at')
            ->first()
            ->created_at;

        DB::transaction(function () use (
            $request,
            $linkedId,
            $accountId,
            $mode,
            $trackId,
            $cashId,
            $account,
            $trackAccount,
            $date,
            $oldTransactions,
            $reference,
            $originalCreatedAt
        ) {

            Transaction::where('linked_id', $linkedId)->get()->each->delete(); // one by one, so the balances follow

            foreach ($request->entries as $entry) {

                $amount   = $entry['amount'];
                $currency = $entry['currency_id'];
                $type     = $entry['type'];

                $currencyObj = Currency::find($currency);

                $oldNote = $oldTransactions
                    ->first(function ($t) use ($type, $accountId) {
                        return $t->transaction_type == $type &&
                            (int) $t->account_id === (int) $accountId;
                    })?->note;

                $generatedNote = $this->transactionNote(
                    $mode,
                    $type,
                    $amount,
                    $currencyObj->name ?: $currencyObj->code,
                    $account,
                    $trackAccount
                );

                $customNote = trim($entry['note'] ?? '');

                $note = $customNote !== ''
                    ? $customNote
                    : ($oldNote ?: $generatedNote);

                $attachmentPath = null;

                if (isset($entry['attachment']) && $entry['attachment'] instanceof \Illuminate\Http\UploadedFile) {
                    $file = $entry['attachment'];

                    $folder = 'attachments/' . Carbon::parse($date)->format('Y/m') . '/account_' . $accountId;

                    if (!file_exists(tenant_public_path($folder))) {
                        mkdir(tenant_public_path($folder), 0755, true);
                    }

                    $name = time() . '_' . Str::random(8) . '.' . upload_extension($file);

                    $file->move(tenant_public_path($folder), $name);

                    $attachmentPath = $folder . '/' . $name;
                }

                /*
            |--------------------------------------------------------------------------
            | IMPORTANT:
            |--------------------------------------------------------------------------
            | Repost using the original created_at.
            | This prevents update rows from getting today's current time.
            */
                $createdAt = Carbon::parse($originalCreatedAt);

                if ($mode === 'treasure') {

                    if ($accountId == $cashId) {
                        $this->postTransaction($linkedId, $accountId, $currency, $amount, $type, $note, 1, 1, $attachmentPath, $date, $reference, $createdAt);
                    } else {
                        $this->postTransaction($linkedId, $accountId, $currency, $amount, $type, $note, 1, 1, $attachmentPath, $date, $reference, $createdAt);
                        $this->postTransaction($linkedId, $cashId, $currency, $amount, $type, $note, 1, 0, $attachmentPath, $date, $reference, $createdAt);
                    }
                } elseif ($mode === 'no_track') {

                    $this->postTransaction($linkedId, $accountId, $currency, $amount, $type, $note, 1, 1, $attachmentPath, $date, $reference, $createdAt);
                } elseif ($mode === 'track') {

                    if ($type === 'credit') {
                        $this->postTransaction($linkedId, $accountId, $currency, $amount, 'credit', $note, 1, 1, $attachmentPath, $date, $reference, $createdAt);
                        $this->postTransaction($linkedId, $trackId, $currency, $amount, 'debit', $note, 1, 1, $attachmentPath, $date, $reference, $createdAt);
                    } else {
                        $this->postTransaction($linkedId, $accountId, $currency, $amount, 'debit', $note, 1, 1, $attachmentPath, $date, $reference, $createdAt);
                        $this->postTransaction($linkedId, $trackId, $currency, $amount, 'credit', $note, 1, 1, $attachmentPath, $date, $reference, $createdAt);
                    }
                }
            }
        });

        return done(__('transactions.updated_transaction'), route('admin.transactions.index'));
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {

            $t = Transaction::with('currency')->findOrFail($id);

            /*
        |--------------------------------------------------------------------------
        | Detect row ID column
        |--------------------------------------------------------------------------
        | Your system may use table_row_id or table_id.
        */
            $rowColumn = null;

            if (Schema::hasColumn('transactions', 'table_row_id')) {
                $rowColumn = 'table_row_id';
            } elseif (Schema::hasColumn('transactions', 'table_id')) {
                $rowColumn = 'table_id';
            }

            /*
        |--------------------------------------------------------------------------
        | Get related transactions
        |--------------------------------------------------------------------------
        */
            $transactionsQuery = Transaction::with('currency');

            if (!empty($t->linked_id)) {
                $transactionsQuery->where('linked_id', $t->linked_id);
            } elseif ($rowColumn && !empty($t->table_name) && !empty($t->{$rowColumn})) {
                $transactionsQuery
                    ->where('table_name', $t->table_name)
                    ->where($rowColumn, $t->{$rowColumn});
            } else {
                $transactionsQuery->where('id', $t->id);
            }

            $transactions = $transactionsQuery->get();

            /*
        |--------------------------------------------------------------------------
        | Collect parent records before deleting transactions
        |--------------------------------------------------------------------------
        */
            $parentRecords = collect();

            foreach ($transactions as $trx) {
                $tableName = $trx->table_name;
                $tableId   = $rowColumn ? $trx->{$rowColumn} : null;

                if ($tableName && $tableId) {
                    $parentRecords->push([
                        'table_name' => $this->normalizeTransactionTableName($tableName),
                        'id'         => $tableId,
                    ]);
                }
            }

            $parentRecords = $parentRecords
                ->unique(fn($item) => $item['table_name'] . '-' . $item['id'])
                ->values();

            /*
        |--------------------------------------------------------------------------
        | Delete transactions
        |--------------------------------------------------------------------------
        */
            foreach ($transactions as $trx) {

                $currency = $trx->currency->code ?? '';

                logActivity(
                    $trx,
                    "Transaction deleted: {$trx->amount} {$currency}. Type: {$trx->transaction_type}",
                    [
                        'transaction_id' => $trx->id,
                        'linked_id'      => $trx->linked_id,
                        'table_name'     => $trx->table_name,
                        'table_id'       => $rowColumn ? $trx->{$rowColumn} : null,
                        'account_id'     => $trx->account_id,
                        'currency'       => $currency,
                        'amount'         => $trx->amount,
                        'type'           => $trx->transaction_type,
                        'note'           => $trx->note,
                    ],
                    'deleted'
                );

                $trx->delete();
            }

            /*
        |--------------------------------------------------------------------------
        | Delete related parent records
        |--------------------------------------------------------------------------
        */
            foreach ($parentRecords as $record) {
                $this->deleteRelatedParentRecord($record['table_name'], $record['id']);
            }
        });

        return done(__('transactions.deleted_successfully'));
    }

    private function normalizeTransactionTableName(?string $tableName): ?string
    {
        if (!$tableName) {
            return null;
        }

        $tableName = trim($tableName);

        return match ($tableName) {
            'Journal', 'journal'       => 'journal',
            'expenses', 'Expense'      => 'expenses',
            'remittances', 'Remittance' => 'remittances',
            'exchanges', 'Exchange'    => 'exchanges',
            default                    => strtolower($tableName),
        };
    }

    private function deleteRelatedParentRecord(?string $tableName, $id): void
    {
        if (!$tableName || !$id) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Journal has no separate parent table
        |--------------------------------------------------------------------------
        */
        if ($tableName === 'Journal') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Only allowed parent tables
        |--------------------------------------------------------------------------
        */
        $allowedTables = [
            'expenses',
            'remittances',
            'exchanges',
        ];

        if (!in_array($tableName, $allowedTables, true)) {
            return;
        }

        if (!Schema::hasTable($tableName)) {
            return;
        }

        $record = DB::table($tableName)->where('id', $id)->first();

        if (!$record) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Delete parent record
        |--------------------------------------------------------------------------
        | Do not call logActivity(null, ...)
        | because Spatie requires an Eloquent model.
        */
        DB::table($tableName)->where('id', $id)->delete();
    }

    public function summary(Request $request)
    {
        return response()->json([
            'summary' => $this->buildSummary($request)
        ]);
    }

    private function buildSummary(Request $request)
    {
        $dateRange = null;

        if ($request->filled('date_range')) {
            [$start, $end] = explode(' - ', $request->date_range);

            $dateRange = [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ];
        }

        $today = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->select(
                'currencies.code as currency',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit'  THEN amount ELSE 0 END) as debit"),
                DB::raw("SUM(CASE WHEN accounts.account_sub_category_id = 1 AND transaction_type = 'credit' THEN amount ELSE 0 END) as customer_credit"),
                DB::raw("SUM(CASE WHEN accounts.account_sub_category_id = 1 AND transaction_type = 'debit'  THEN amount ELSE 0 END) as customer_debit"),
                DB::raw("SUM(CASE WHEN accounts.account_sub_category_id = 2 AND transaction_type = 'credit' THEN amount ELSE 0 END) as sarafi_credit"),
                DB::raw("SUM(CASE WHEN accounts.account_sub_category_id = 2 AND transaction_type = 'debit'  THEN amount ELSE 0 END) as sarafi_debit"),
                DB::raw("SUM(CASE WHEN table_name = 'expenses' AND transaction_type = 'debit' THEN amount ELSE 0 END) as expense_total")
            )
            ->when($request->account_id, fn($q) => $q->where('transactions.account_id', $request->account_id))
            ->when($request->currency_id, fn($q) => $q->where('transactions.currency_id', $request->currency_id))
            ->when($request->transaction_type, fn($q) => $q->where('transactions.transaction_type', $request->transaction_type))
            ->when($dateRange, fn($q) => $q->whereBetween('transactions.created_at', $dateRange))
            ->groupBy('currencies.code')
            ->get()
            ->keyBy('currency');

        $cash = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->select(
                'currencies.code as currency',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as cash_credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit'  THEN amount ELSE 0 END) as cash_debit")
            )
            ->where('accounts.account_sub_category_id', 3)
            ->when($request->account_id, fn($q) => $q->where('transactions.account_id', $request->account_id))
            ->when($request->currency_id, fn($q) => $q->where('transactions.currency_id', $request->currency_id))
            ->when($request->transaction_type, fn($q) => $q->where('transactions.transaction_type', $request->transaction_type))
            ->when($dateRange, fn($q) => $q->whereBetween('transactions.created_at', $dateRange))
            ->groupBy('currencies.code')
            ->get()
            ->keyBy('currency');

        $summary = [];

        $currencies = collect()
            ->merge($today->keys())
            ->merge($cash->keys())
            ->unique();

        foreach ($currencies as $currency) {
            $t = $today[$currency] ?? null;
            $c = $cash[$currency] ?? null;

            $summary[$currency] = [
                'customers_total' => (float) (($t->customer_credit ?? 0) - ($t->customer_debit ?? 0)),
                'sarafi_total'    => (float) (($t->sarafi_credit ?? 0) - ($t->sarafi_debit ?? 0)),
                'expense_total'   => (float) ($t->expense_total ?? 0),
                'cash_in_hand'    => (float) (($c->cash_credit ?? 0) - ($c->cash_debit ?? 0)),
                'credit'          => (float) ($t->credit ?? 0),
                'debit'           => (float) ($t->debit ?? 0),
                'balance'         => (float) (($t->credit ?? 0) - ($t->debit ?? 0)),
            ];
        }

        return $summary;
    }

    public function export(Request $request)
    {
        $query = Transaction::with(['account', 'currency'])
            ->when($request->account_id, fn($q) => $q->where('account_id', $request->account_id))
            ->when($request->currency_id, fn($q) => $q->where('currency_id', $request->currency_id))
            ->when($request->transaction_type, fn($q) => $q->where('transaction_type', $request->transaction_type))
            ->latest();

        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            return __('transactions.no_export_data');
        }

        $pdf = Pdf::loadView('admin.transactions.export', compact('transactions'))
            ->setPaper('A4', 'portrait');

        return $pdf->stream('transactions.pdf');
    }

    public function getAccountById(Request $request)
    {
        return response()->json([
            'accounts' => Account::where('account_sub_category_id', $request->account_sub_category_id)->get()
        ]);
    }

    public function modalStore(Request $request)
    {
        $request->validate([
            'account_id'            => 'required|exists:accounts,id',
            'date'                  => 'nullable|date',
            'entries'               => 'required|array|min:1',
            'entries.*.currency_id' => 'required|exists:currencies,id',
            'entries.*.amount'      => 'required|numeric|min:0.01',
            'entries.*.type'        => 'required|in:credit,debit',
            'transaction_mode'       => 'required|in:treasure,no_track,track',
            'reference'             => 'nullable|string|max:255',
        ], [
            'track_account_id.required_if' => __('Choose the other account.'),
            'track_account_id.different' => __('The other account must be a different account.'),
            'entries.*.attachment.mimes' => __('Only pictures (JPG, PNG, WebP, GIF) and PDF files can be attached.'),
        ]);

        $accountId = $request->account_id;
        $mode      = $request->transaction_mode;
        $trackId   = $request->track_account_id;
        $date      = $request->date;
        $reference = $request->reference;

        $cashId = Account::where('account_sub_category_id', 3)->value('id');

        DB::transaction(function () use ($request, $accountId, $mode, $trackId, $cashId, $date, $reference) {
            $trackAccount = Account::find($trackId);

            $account = Account::find($accountId);

            foreach ($request->entries as $index => $entry) {

                $linkedId = (string) Str::uuid();

                $amount   = $entry['amount'];
                $currency = $entry['currency_id'];
                $type     = $entry['type'];

                $currencyObj = Currency::find($currency);

                $customNote = trim($entry['note'] ?? '');

                if ($customNote !== '') {
                    $note = $customNote;
                } else {
                    $note = $this->transactionNote(
                        $mode,
                        $type,
                        $amount,
                        $currencyObj->name ?: $currencyObj->code,
                        $account,
                        $trackAccount
                    );
                }

                $attachmentPath = null;

                if (isset($entry['attachment']) && $entry['attachment'] instanceof \Illuminate\Http\UploadedFile) {
                    $file = $entry['attachment'];

                    $folder = 'attachments/' . date('Y/m') . '/account_' . $accountId;

                    if (!file_exists(tenant_public_path($folder))) {
                        mkdir(tenant_public_path($folder), 0755, true);
                    }

                    $name = time() . '_' . Str::random(8) . '.' . upload_extension($file);

                    $file->move(tenant_public_path($folder), $name);

                    $attachmentPath = $folder . '/' . $name;
                }

                if ($mode === 'treasure') {

                    if ($accountId == $cashId) {
                        $this->postTransaction($linkedId, $accountId, $currency, $amount, $type, $note, 1, 1, $attachmentPath, $date, $reference);
                    } else {
                        $this->postTransaction($linkedId, $accountId, $currency, $amount, $type, $note, 1, 1, $attachmentPath, $date, $reference);
                        $this->postTransaction($linkedId, $cashId, $currency, $amount, $type, $note, 1, 0, $attachmentPath, $date, $reference);
                    }
                } elseif ($mode === 'no_track') {

                    $this->postTransaction($linkedId, $accountId, $currency, $amount, $type, $note, 1, 1, $attachmentPath, $date, $reference);
                } elseif ($mode === 'track') {

                    if (!$trackId) {
                        throw new \Exception('Track account required');
                    }

                    if ($type === 'credit') {
                        $this->postTransaction($linkedId, $accountId, $currency, $amount, 'credit', $note, 1, 1, $attachmentPath, $date, $reference);
                        $this->postTransaction($linkedId, $trackId, $currency, $amount, 'debit', $note, 1, 1, $attachmentPath, $date, $reference);
                    } else {
                        $this->postTransaction($linkedId, $accountId, $currency, $amount, 'debit', $note, 1, 1, $attachmentPath, $date, $reference);
                        $this->postTransaction($linkedId, $trackId, $currency, $amount, 'credit', $note, 1, 1, $attachmentPath, $date, $reference);
                    }
                }
            }
        });

        return response()->json(['message' => __('transactions.saved_transactions')]);
    }

    private function transactionNote(
        string $mode,
        string $type,
        $amount,
        string $currencyName,
        Account $account,
        ?Account $trackAccount
    ): string {
        if ($mode === 'track' && $trackAccount) {
            $fromAccount = $type === 'credit' ? $trackAccount->name : $account->name;
            $toAccount = $type === 'credit' ? $account->name : $trackAccount->name;

            return "انتقال پول به {$toAccount} درک {$fromAccount} | {$amount} {$currencyName}";
        }

        if ($mode === 'no_track' || $mode === 'treasure') {
            return $type === 'credit'
                ? "آوردگی {$account->name} | {$amount} {$currencyName}"
                : "بردگی {$account->name} | {$amount} {$currencyName}";
        }

        return 'Journal entry';
    }

    private function detectTransactionModeFromRows($transactions, $currentAccountId, $cashId): array
    {
        $mode = 'no_track';
        $trackAccountId = null;

        if ($transactions->isEmpty()) {
            return [
                'mode' => $mode,
                'track_account_id' => $trackAccountId,
            ];
        }

        $hasCashRow = $transactions->contains(function ($trx) use ($cashId) {
            return (int) $trx->account_id === (int) $cashId || (int) $trx->is_visible === 0;
        });

        $visibleRows = $transactions->where('is_visible', 1)->values();

        if ($hasCashRow) {
            $mode = 'treasure';
        } elseif ($visibleRows->count() >= 2) {
            $mode = 'track';

            $trackRow = $visibleRows->first(function ($trx) use ($currentAccountId) {
                return (int) $trx->account_id !== (int) $currentAccountId;
            });

            $trackAccountId = $trackRow?->account_id;
        }

        return [
            'mode' => $mode,
            'track_account_id' => $trackAccountId,
        ];
    }

    protected function postTransaction(
        $linkedId,
        $accountId,
        $currencyId,
        $amount,
        $type,
        $note = null,
        $isCash = 0,
        $isVisible = 1,
        $attachment = null,
        $date = null,
        $reference = null,
        $fixedCreatedAt = null
    ) {
        if ($fixedCreatedAt) {
            $createdAt = Carbon::parse($fixedCreatedAt);
        } else {
            $createdAt = $date
                ? Carbon::parse($date)->setTime(now()->hour, now()->minute, now()->second)
                : now();
        }

        return Transaction::create([
            'account_id'          => $accountId,
            'currency_id'         => $currencyId,
            'amount'              => $amount,
            'payment_currency_id' => $currencyId,
            'payment_amount'      => $amount,
            'actual_currency_id'  => $currencyId,
            'actual_amount'       => $amount,
            'transaction_type'    => $type,
            'note'                => $note,
            'reference'           => $reference,
            'linked_id'           => $linkedId,
            'is_cash'             => $isCash,
            'is_visible'          => $isVisible,
            'table_name'          => 'Journal',
            'status'              => 'active',
            'created_by'          => auth()->id(),
            'attachment'          => $attachment,
            'created_at'          => $createdAt,
            'updated_at'          => $createdAt,
        ]);
    }
}
