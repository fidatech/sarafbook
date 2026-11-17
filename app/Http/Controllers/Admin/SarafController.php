<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class SarafController extends Controller
{
    /**
     * Display listing.
     */
    public function index()
    {
        $accountCategories = AccountCategory::all();
        $currencies = Currency::where('is_active', 1)->get();
        return view('admin.saraf.index', compact('accountCategories', 'currencies'));
    }

    /**
     * Create Saraf.
     */
    public function store(Request $request)
    {
        $data = new Account();
        $data->name = $request->name;
        $data->account_sub_category_id = $request->create_account_sub_category_id;
        $data->code = $request->code;
        $data->contact = $request->contact;
        $data->address = $request->address;
        $data->company = $request->company;

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
        $data->save();

        return redirect()->back()->with('success', 'Saraf created successfully!');
    }

    /**
     * Show Saraf.
     */
    public function show($id)
    {
        $account = Account::with('subCategory')->findOrFail($id);
        $currencies = Currency::all();
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
                    'debit'  => $item->debit
                ]];
            });

        return view('admin.accounts.show', compact('account', 'currencies', 'summariesByCurrency'));
    }

    public function print($id)
    {
        $account = Account::with('subCategory')->findOrFail($id);
        $currencies = Currency::all();
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
                    'debit'  => $item->debit
                ]];
            });

        return view('admin.accounts.print', compact('account', 'currencies', 'summariesByCurrency'));
    }

    public function showAccount($id)
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
            $currency = Currency::find($item->currency_id);
            $flag = $currency ? strtolower(substr($currency->code, 0, 2)) : 'xx';
            $currencyBalances[] = [
                'currency' => $currency->code ?? 'N/A',
                'amount'   => format_amount($item->net_balance, 2),
                'flag'     => "/assets/flags/{$flag}.svg",
            ];
        }

        return response()->json([
            'id'       => $account->id,
            'name'     => $account->name,
            'code'     => $account->code,
            'balances' => $currencyBalances,
        ]);
    }

    public function transactionData($id, $type, Request $request)
    {
        $query = Transaction::with('currency')
            ->where('account_id', $id)
            ->where('account_type', $type)
            ->orderByDesc('created_at');

        if ($request->currency_id) {
            $query->where('currency_id', $request->currency_id);
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('amount', fn($row) => format_amount($row->amount, 2))
            ->addColumn('currency', fn($row) => $row->currency->code ?? '-')
            ->editColumn(
                'transaction_type',
                fn($row) =>
                $row->transaction_type === 'credit'
                    ? '<span class="badge bg-success">Credit</span>'
                    : '<span class="badge bg-danger">Debit</span>'
            )
            ->editColumn('created_at', fn($row) => $row->created_at->format('Y-m-d H:i'))
            ->rawColumns(['transaction_type'])
            ->make(true);
    }

    public function edit(string $id)
    {
        $account = Account::findOrFail($id);
        return response()->json($account);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'code'    => 'required|string|max:255|unique:accounts,code,' . $id,
            'contact' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'company' => 'nullable|string|max:255',
        ]);

        $account = Account::findOrFail($id);
        $account->update($request->only(['name', 'code', 'contact', 'address', 'company']));

        return redirect()->back()->with('success', 'Account updated successfully.');
    }

    public function destroy(string $id)
    {
        $account = Account::findOrFail($id);

        try {
            $hasTransactions = Transaction::where('account_id', $account->id)->exists();
            if ($hasTransactions) {
                Transaction::where('account_id', $account->id)->get()->each->delete(); // one by one, so the balances follow
            }
            $account->delete();

            return response()->json([
                'success' => true,
                'message' => 'Account and related transactions deleted successfully.'
            ]);
        } catch (Exception $e) {
            Log::error('Account deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account or its transactions.'
            ], 500);
        }
    }

    /* =========================
       Balance helpers (NEW)
       ========================= */

    private function perCurrencyNetSubquery(?int $currencyId = null)
    {
        $q = DB::table('transactions')
            ->select([
                'account_id',
                'currency_id',
                DB::raw("SUM(CASE WHEN transaction_type='credit' THEN amount ELSE -amount END) AS net_balance"),
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
        $perCur = $this->perCurrencyNetSubquery(null);

        return DB::query()->fromSub($perCur, 't')
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
    }

    /* =========================
       List API with balance filter (UPDATED)
       ========================= */
    public function fetchSaraf(Request $request)
    {
        $accounts = Account::query()->where('is_active', true);

        // Filters: type, sub-type, search
        if ($request->filled('account_type_id')) {
            $accounts->whereHas('subCategory', function ($q) use ($request) {
                $q->where('account_category_id', $request->account_type_id);
            });
        }

        if ($request->filled('account_sub_category_id')) {
            $accounts->where('account_sub_category_id', $request->account_sub_category_id);
        }

        if ($request->filled('search_term')) {
            $search = $request->search_term;
            $accounts->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Saraf only
        $accounts->whereIn('account_sub_category_id', [2, 3]);

        // Balance ordering/filtering
        $currencyId    = $request->integer('currency_id') ?: null;
        $balanceFilter = $request->input('balance_filter'); // '', 'creditors', 'debtors', 'mixed'

        if ($currencyId) {
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
                $dom = $this->dominantBalanceSubquery();
                $accounts->leftJoinSub($dom, 'dom', function ($join) {
                    $join->on('dom.account_id', '=', 'accounts.id');
                })->where('dom.has_pos', 1)->where('dom.has_neg', 1)
                    ->orderByDesc(DB::raw('COALESCE(bal.net_balance, 0)'));
            } else {
                $accounts->orderBy('created_at', 'desc');
            }

            $accounts->addSelect('accounts.*', DB::raw('COALESCE(bal.net_balance, 0) as _net_balance'));
        } else {
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

        return DataTables::of($accounts)
            ->addColumn('name_with_profile', function ($account) {
                $bgColor = $account->profile_bg ?? '#6c757d';
                $accountUrl = route('admin.saraf.show', $account->id);
                $codeParts = explode(' ', $account->code);
                $number = '#' === ($codeParts[0] ?? '')
                    ? substr($account->code, 1, 10)
                    : (count($codeParts) > 1
                        ? implode(' ', array_slice($codeParts, 0, -1)) . ' ' . end($codeParts)
                        : $account->code);

                return '<div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:40px;height:40px;border-radius:50%;background-color:' . $bgColor . ';color:white;font-weight:bold;display:flex;justify-content:center;align-items:center;font-size:16px;line-height:1;overflow:hidden;">
                                ' . e($number) . '
                            </div>
                            <div>
                                <a href="' . $accountUrl . '" style="font-weight:bold;color:inherit;">' . e($account->name) . '</a>
                            </div>
                        </div>';
            })
            ->addColumn('account_type_with_icon', function ($account) {
                $category = optional($account->subCategory)->accountCategory;
                $icon = $category ? '<i class="bi ' . $category->bi_icon . '" style="font-size:20px;color:' . $category->bi_icon_color . ';margin-right:5px;"></i>' : '';
                $categoryName = $category ? '<span style="font-size:16px;">' . $category->name . '</span>' : '<span style="font-size:16px;">No Category</span>';
                $subCategoryName = $account->subCategory ? '<span style="font-size:12px;color:#6c757d;"><br>' . $account->subCategory->name . '</span>' : '<span style="font-size:12px;color:#6c757d;"><br>No SubCategory</span>';
                return $icon . $categoryName . $subCategoryName;
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
                    ->when($currencyId, function ($q) use ($currencyId) {
                        $q->havingRaw('currency_id = ?', [$currencyId]);
                    })
                    ->get();

                if ($balances->isEmpty()) return '0';

                $html = '';
                foreach ($balances as $b) {
                    $currency = Currency::find($b->currency_id);
                    $flag = $currency ? "<img src='/assets/flags/" . strtolower(substr($currency->code, 0, 2)) . ".svg' height='16' width='20' />" : '';
                    $color = $b->balance < 0 ? '#b23434' : '#2b2bc7';
                    $html .= "<b style='font-size:0.9rem;color:{$color}'>{$flag} " . format_amount(abs($b->balance), 2) . '</b><br>';
                }
                return $html;
            })
            ->addColumn('contact_details', function ($account) {
                return "<div style='font-size:12px;'>"
                    . "<b>Contact:</b> " . e($account->contact) . "<br>"
                    . "<b>Address:</b> " . e($account->address) . "<br>"
                    . "<b>Company:</b> " . e($account->company) . "<br>"
                    . "<b>Created By:</b> " . (optional($account->user)->name ?? 'N/A')
                    . "</div>";
            })
            ->addColumn('actions', function ($account) {
                $show_url = route('admin.saraf.show', $account->id);
                $whatsapp_url = route('admin.accounts.send-whatsapp', $account->id); // if you have a saraf-specific route, swap it

                $html = '';
                if (auth()->user()->can('view saraf')) {
                    $html .= '<a href="' . $show_url . '" class="btn btn-sm btn-outline-primary" title="View Account">
                                <i class="bi bi-eye" style="color:black;font-size:20px;"></i>
                              </a>
                              <button type="button" class="btn btn-sm btn-outline-dark btn-preview-account me-1" data-id="' . $account->id . '" title="Preview">
                                <i class="bi bi-printer" style="color:black;font-size:20px;"></i>
                              </button>';
                }
                if (auth()->user()->can('update saraf')) {
                    $html .= '<button type="button" class="btn btn-sm btn-outline-warning btn-edit-account me-1" data-id="' . $account->id . '" title="Edit">
                                <i class="bi bi-pencil" style="font-size:20px;"></i>
                              </button>';
                }
                if (auth()->user()->can('send-whatsapp saraf')) {
                    $html .= '<button type="button" class="btn btn-sm btn-outline-success btn-send-whatsapp" data-url="' . $whatsapp_url . '" title="Send WhatsApp">
                                <i class="bi bi-whatsapp" style="font-size:20px;"></i>
                              </button>';
                }
                return $html;
            })
            ->rawColumns(['name_with_profile', 'account_type_with_icon', 'balance', 'contact_details', 'actions'])
            ->make(true);
    }

    public function updateStats(Request $request)
    {
        $accountCategoryId = $request->input('account_category_id');
        $currencyId = $request->input('currency_id');

        $accountsQuery = Account::query()->where('is_active', true);

        if ($accountCategoryId) {
            $accountsQuery->whereHas('subCategory', function ($query) use ($accountCategoryId) {
                $query->where('account_category_id', $accountCategoryId);
            });
        }

        // Saraf only
        $accountsQuery->whereIn('account_sub_category_id', [2, 3]);

        $accounts = $accountsQuery->pluck('id');

        $transactions = Transaction::whereIn('account_id', $accounts);

        if ($currencyId) {
            $transactions->where('currency_id', $currencyId);
            $currency = Currency::find($currencyId)?->name ?? '';
        } else {
            $currency = '';
        }

        $credit = (clone $transactions)->where('transaction_type', 'credit')->sum('amount');
        $debit  = (clone $transactions)->where('transaction_type', 'debit')->sum('amount');
        $balance = $credit - $debit;

        return response()->json([
            'total_accounts' => $accounts->count(),
            'total_credit'   => format_amount($credit, 2),
            'total_debit'    => format_amount($debit, 2),
            'balance'        => format_amount($balance, 2),
            'currency'       => $currency,
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
        $exists = Account::where('code', $request->code)->exists();
        return response()->json(['available' => !$exists]);
    }
}
