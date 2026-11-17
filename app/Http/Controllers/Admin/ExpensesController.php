<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class ExpensesController extends Controller
{
    /**
     * Expenses in the fida-sarafi style: the list with its filters and the totals by category and
     * currency of what is shown, all counted by the database; one pop-up to add or change one.
     */
    public function index(Request $request)
    {
        $currencies = Currency::orderBy('id')->get();
        $expenseCategories = ExpenseCategory::orderBy('name')->get();
        $cashAccounts = Account::whereIn('account_sub_category_id', [3, 5])->orderBy('id')->get();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'expense_category_id' => $request->integer('expense_category_id') ?: null,
            'expense_type' => in_array($request->query('expense_type'), ['company', 'personal', 'project'], true) ? $request->query('expense_type') : '',
            'currency_id' => $request->integer('currency_id') ?: null,
            'from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('from')) ? $request->query('from') : '',
            'to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('to')) ? $request->query('to') : '',
        ];
        $filtered = collect($filters)->filter(fn ($v) => $v !== '' && $v !== null)->isNotEmpty();
        $base = fn () => Expense::query()
            ->when($filters['q'] !== '', fn ($q) => $q->where('description', 'like', '%'.$filters['q'].'%'))
            ->when($filters['expense_category_id'], fn ($q, $v) => $q->where('expense_category_id', $v))
            ->when($filters['expense_type'] !== '', fn ($q) => $q->where('expense_type', $filters['expense_type']))
            ->when($filters['currency_id'], fn ($q, $v) => $q->where('currency_id', $v))
            ->when($filters['from'] !== '', fn ($q) => $q->whereDate('expense_date', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($q) => $q->whereDate('expense_date', '<=', $filters['to']));

        $rows = $base()->with(['currency:id,code', 'paymentCurrency:id,code', 'expenseCategory:id,name', 'paidFromAccount:id,name,code'])
            ->orderByDesc('expense_date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        $byCategory = $base()->leftJoin('expense_categories as ec', 'ec.id', '=', 'expenses.expense_category_id')
            ->join('currencies as c', 'c.id', '=', 'expenses.currency_id')
            ->groupBy('ec.name', 'c.code')
            ->selectRaw('COALESCE(ec.name, ?) AS category, c.code, SUM(expenses.amount) AS total, COUNT(*) AS n', [__('No category')])
            ->orderByDesc('total')->get();

        return page('admin.expenses.index', compact('currencies', 'expenseCategories', 'cashAccounts', 'rows', 'byCategory', 'filters', 'filtered'), ['list', 'totals']);
    }

    public function data(Request $request)
    {
        $query = Expense::with(['currency', 'expenseCategory'])
            ->orderByDesc('expense_date');

        // FILTER: date from
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }

        // FILTER: date to
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        // FILTER: currency
        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }

        // FILTER: status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // FILTER: expense type
        if ($request->filled('expense_type')) {
            $query->where('expense_type', $request->expense_type);
        }

        // FILTER: expense category
        if ($request->filled('expense_category_id')) {
            $query->where('expense_category_id', $request->expense_category_id);
        }

        return DataTables::of($query)
            ->addIndexColumn()

            ->editColumn('expense_date', function ($e) {
                return $e->expense_date;
            })

            // Type Badge + raw value
            ->addColumn('expense_type_badge', function ($e) {
                $map = [
                    'company'  => 'primary',
                    'personal' => 'info',
                ];
                $label = ucfirst($e->expense_type);
                $class = $map[$e->expense_type] ?? 'secondary';

                return "<span class=\"badge bg-{$class}\">{$label}</span>";
            })
            ->addColumn('expense_type_value', fn($e) => $e->expense_type)

            // Category
            ->addColumn('expense_category_name', function ($e) {
                return $e->expenseCategory->name ?? '—';
            })

            ->addColumn(
                'payment_amount',
                fn($e) =>
                format_amount($e->payment_amount, 0) . ' ' . $e->paymentCurrency->code
            )
            // Amount
            ->addColumn(
                'amount',
                fn($e) =>
                format_amount($e->amount, 0) . ' ' . $e->currency->code
            )
            ->addColumn(
                'amount_raw',
                fn($e) =>
                $e->amount
            )

            // Currency
            ->addColumn(
                'currency_code',
                fn($e) =>
                $e->currency->code ?? ''
            )

            // Status badge
            ->editColumn('status', function ($e) {
                $colors = [
                    'approved'  => 'success',
                    'pending'   => 'warning',
                ];
                $class = $colors[$e->status] ?? 'secondary';
                return '<span class="badge bg-' . $class . '">' . (['approved' => __('ui.approved'), 'pending' => __('ui.pending')][$e->status] ?? ucfirst($e->status)) . '</span>';
            })

            // Attachment button
            ->addColumn('attachment', function ($e) {
                if (!$e->attachment_path) {
                    return '<span class="text-muted">—</span>';
                }

                $url = asset('assets/attachments/' . $e->attachment_path);

                return '<a href="' . $url . '" class="btn btn-sm btn-outline-secondary btn-download" target="_blank">
                            <i class="bi bi-paperclip"></i>
                            <span class="d-none d-sm-inline-block ms-1">' . __('ui.download') . '</span>
                        </a>';
            })
            // Actions
            ->addColumn('actions', function ($e) {

                $auth = auth()->user();
                $html = '<div class="d-flex gap-1">';

                // Edit Button
                if ($auth->can('update expenses')) {
                    $html .= '
                        <button class="btn btn-sm btn-outline-primary btn-edit"
                            data-id="' . $e->id . '"
                            title="' . __('accounts.edit') . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                    ';
                }

                // Delete Button
                if ($auth->can('delete expenses')) {
                    $html .= '
                        <button class="btn btn-sm btn-outline-danger btn-delete"
                            data-id="' . $e->id . '"
                            title="' . __('accounts.delete') . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                }

                $html .= '</div>';

                return $html;
            })


            ->addColumn('attachment_url', function ($e) {
                return $e->attachment_path ? asset('storage/' . $e->attachment_path) : null;
            })

            ->rawColumns(['expense_type_badge', 'status', 'attachment', 'actions'])
            ->make(true);
    }


    // ============================================
    // STORE
    // ============================================
    public function store(Request $request)
    {
        $request->validate([
            'account_id'          => 'required|exists:accounts,id',
            'expense_type'        => 'required|in:company,personal,project',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'currency_id'         => 'nullable|exists:currencies,id',
            'payment_currency_id' => 'nullable|exists:currencies,id',
            'expense_date'        => 'required|date',
            'amount'              => 'required|numeric|min:0.01',
            'payment_amount'      => 'nullable|numeric|min:0',
            'rate'                => 'nullable|numeric|min:0',
            'operation_type'      => 'nullable|in:multiply,divide',
            'description'         => 'nullable|string',
            'attachment'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        // Upload attachment
        $path = null;
        if ($request->attachment != '') {
            $fileName = time() . '.' . upload_extension(request()->attachment);
            $path = $fileName;
            request()->attachment->move(tenant_public_path('assets/attachments'), $fileName);
        } else {
            $path = $request->attachment;
        }

        $currencyId = $request->currency_id;
        $safeAccountId = $request->account_id;

        // CREATE EXPENSE
        $expense = Expense::create([
            'expense_type'         => $request->expense_type,
            'expense_category_id'  => $request->expense_category_id,
            'paid_from_account_id' => 1,
            'currency_id'          => $currencyId,
            'amount'               => $request->amount,
            'payment_currency_id'  => $request->payment_currency_id,
            'payment_amount'       => $request->payment_amount,
            'rate'                 => $request->rate,
            'operation_type'       => $request->operation_type,
            'expense_date'         => $request->expense_date,
            'description'          => $request->description,
            'attachment_path'      => $path,
            'status'               => 'approved',
            'created_by'           => Auth::id(),
        ]);

        $linkedId = (string) Str::uuid();

        // CREATE TRANSACTION
        $transaction = Transaction::create([
            'linked_id'        => $linkedId,
            'table_name'       => 'expenses',
            'table_row_id'     => $expense->id,
            'purpose'          => 'expense',
            'type'             => 'expense',
            'account_id'       => $safeAccountId,
            'payment_currency_id' => $expense->payment_currency_id,
            'payment_amount'  => $request->payment_amount,
            'rate'            => $request->rate,
            'operation_type'  => $request->operation_type,
            'currency_id'      => $currencyId,
            'amount'           => $request->amount,
            'transaction_type' => 'debit',
            'note'             => $expense->description,
            'status'           => 'active',
            'is_visible'       => true,
            'is_cash'          => true,
            'created_by'       => Auth::id(),
            'category'         => 'expense',
            'attachment'      => $path,
        ]);

        $expense->transaction_id = $transaction->id;
        $expense->save();

        return done(__('expenses.created'));
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'expense_type'        => 'required|in:company,personal,project',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'expense_date'        => 'required|date',
            'currency_id'         => 'nullable|exists:currencies,id',
            'payment_currency_id' => 'nullable|exists:currencies,id',
            'amount'              => 'required|numeric|min:0.01',
            'payment_amount'      => 'nullable|numeric|min:0',
            'rate'                => 'nullable|numeric|min:0',
            'operation_type'      => 'nullable|in:multiply,divide',
            'description'         => 'nullable|string',
            'attachment'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        // a new attachment replaces the old one; without one the old stays
        $path = $expense->attachment_path;
        if ($request->hasFile('attachment')) {
            if ($path && basename($path) === $path && file_exists(tenant_public_path('assets/attachments/' . $path))) {
                @unlink(tenant_public_path('assets/attachments/' . $path));
            }
            $path = time() . '.' . upload_extension($request->file('attachment'));
            $request->file('attachment')->move(tenant_public_path('assets/attachments'), $path);
        }

        // Update core fields
        $expense->update([
            'expense_type'        => $request->expense_type,
            'expense_category_id' => $request->expense_category_id,
            'expense_date'        => $request->expense_date,
            'amount'              => $request->amount,
            'currency_id'         => $request->currency_id,
            'payment_currency_id' => $request->payment_currency_id,
            'payment_amount'      => $request->payment_amount,
            'rate'                => $request->rate,
            'operation_type'      => $request->operation_type,
            'description'         => $request->description,
            'attachment_path'     => $path,
        ]);

        // Sync transaction
        if ($expense->transaction) {
            $expense->transaction->update([
                'amount' => $request->amount,
                'rate'   => $request->rate,
                'operation_type' => $request->operation_type,
                'currency_id' => $request->currency_id,
                'payment_currency_id' => $request->payment_currency_id,
                'note'   => $expense->description,
                'attachment' => $path,
            ]);
        }

        return done(__('expenses.updated'));
    }


    // ============================================
    // DELETE
    // ============================================
    public function destroy(Expense $expense)
    {
        $file = $expense->attachment_path;
        if ($file && basename($file) === $file && file_exists(tenant_public_path('assets/attachments/' . $file))) {
            @unlink(tenant_public_path('assets/attachments/' . $file));
        }

        if ($expense->transaction) {
            $expense->transaction->delete();
        }

        $expense->delete();

        return done(__('expenses.removed'));
    }

    public function summary(Request $request)
    {
        $q = Expense::with(['currency']);

        // Apply all same filters
        if ($request->filled('date_from')) {
            $q->whereDate('expense_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('expense_date', '<=', $request->date_to);
        }
        if ($request->filled('currency_id')) {
            $q->where('currency_id', $request->currency_id);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('expense_type')) {
            $q->where('expense_type', $request->expense_type);
        }
        if ($request->filled('expense_category_id')) {
            $q->where('expense_category_id', $request->expense_category_id);
        }

        $expenses = $q->get();
        $currencies = Currency::orderBy('id')->get();

        $perCategory = ExpenseCategory::orderBy('name')->get()->map(function ($cat) use ($expenses, $currencies) {

            $currencyTotals = [];

            foreach ($currencies as $cur) {
                $currencyTotals[$cur->code] = $expenses
                    ->where('expense_category_id', $cat->id)
                    ->where('currency_id', $cur->id)
                    ->sum('amount');
            }

            return [
                'category' => $cat->name,
                'totals' => $currencyTotals
            ];
        })
            // remove categories with all zeros
            ->filter(function ($row) {
                return collect($row['totals'])->sum() > 0;
            })
            ->values();

        // -------------------------------
        // TOTAL SUMMARY (all categories)
        // -------------------------------
        $totalSummary = [];
        foreach ($currencies as $cur) {
            $totalSummary[$cur->code] = $expenses
                ->where('currency_id', $cur->id)
                ->sum('amount');
        }

        return response()->json([
            'perCategory' => $perCategory,
            'currencies' => $currencies->pluck('code'),
            'totals' => $totalSummary
        ]);
    }

    public function export(Request $request)
    {
        $query = Expense::with(['currency', 'expenseCategory'])
            ->orderByDesc('expense_date');

        // Filters
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }
        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('expense_type')) {
            $query->where('expense_type', $request->expense_type);
        }
        if ($request->filled('expense_category_id')) {
            $query->where('expense_category_id', $request->expense_category_id);
        }

        $expenses = $query->get();

        // Settings (company info)
        $setting = DB::table('settings')->first();

        $pdf = Pdf::loadView('admin.expenses.export', [
            'expenses' => $expenses,
            'setting'  => $setting
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Expenses-' . now()->format('Ymd-His') . '.pdf');
    }
}
