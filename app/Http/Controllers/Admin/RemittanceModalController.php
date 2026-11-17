<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Remittance;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\FormatsRemittanceNotes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RemittanceModalController extends Controller
{
    use FormatsRemittanceNotes;

    public function nextHawala(Request $request)
    {
        $accountId = $request->account_id;
        $type = $request->type;
        $column = $request->column ?? 'hawala_number';

        $allowedColumns = [
            'hawala_number',
            'track_hawala_number',
        ];

        if (!in_array($column, $allowedColumns, true)) {
            return response()->json(['number' => 1]);
        }

        if (!$accountId || !in_array($type, ['incoming', 'outgoing'], true)) {
            return response()->json(['number' => 1]);
        }

        if ($type === 'outgoing') {
            return response()->json([
                'number' => Remittance::nextOutgoingHawalaNumber((int) $accountId),
            ]);
        }

        $query = Remittance::query()->where('type', $type);

        if ($column === 'hawala_number') {
            if ($type === 'outgoing') {
                $query->where('to_account_id', $accountId);
            }

            if ($type === 'incoming') {
                $query->where('to_account_id', $accountId);
            }
        }

        if ($column === 'track_hawala_number') {
            $query->where('track_account_id', $accountId);
        }

        $lastNumber = (clone $query)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->selectRaw("MAX(CAST({$column} AS UNSIGNED)) as max_number")
            ->value('max_number');

        return response()->json([
            'number' => ((int) $lastNumber) + 1,
        ]);
    }

    public function data(Request $request)
    {
        $q = Remittance::query()
            ->with([
                'fromAccount',
                'toAccount',
                'trackAccount',
                'currency',
                'partnerCurrency',
                'commissionCurrency'
            ])
            ->when($request->type, fn($x) => $x->where('type', $request->type))
            ->when($request->status, fn($x) => $x->where('status', $request->status))
            ->when($request->currency_id, fn($x) => $x->where('currency_id', $request->currency_id))
            ->when($request->account_id, function ($x) use ($request) {
                $x->where(function ($w) use ($request) {
                    $w->where('from_account_id', $request->account_id)
                        ->orWhere('to_account_id', $request->account_id)
                        ->orWhere('track_account_id', $request->account_id);
                });
            })
            ->orderByDesc('id');

        if ($request->date_range) {
            [$start, $end] = explode(' - ', $request->date_range);
            $q->whereBetween('date', [$start, $end]);
        }

        return datatables()->of($q)
            ->addIndexColumn()
            ->addColumn('from_name', fn($r) => $r->fromAccount->name ?? '-')
            ->addColumn('to_name', fn($r) => $r->toAccount->name ?? '-')
            ->addColumn('track_name', fn($r) => $r->trackAccount->name ?? '-')
            ->addColumn('currency_code', fn($r) => $r->currency->code ?? '-')
            ->addColumn('partner_currency_code', fn($r) => $r->partnerCurrency->code ?? '-')
            ->addColumn('amount_text', function ($r) {
                $base = format_amount((float)$r->amount, 2) . ' ' . ($r->currency->code ?? '');
                $partner = $r->partner_amount
                    ? format_amount((float)$r->partner_amount, 2) . ' ' . ($r->partnerCurrency->code ?? '')
                    : '-';

                return $base . ' / ' . $partner;
            })
            ->editColumn('date', function ($r) {
                return $r->date ? $r->date->format('d F Y') : '-';
            })
            ->addColumn('commission_currency_code', fn($r) => $r->commissionCurrency->code ?? '-')
            ->addColumn('commission_text', function ($r) {
                if ($r->commission_mode === 'none') {
                    return '-';
                }

                $cc = $r->commissionCurrency->code ?? '';

                if ($r->commission_mode === 'company') {
                    return "Kabul: {$r->commission_kabul_amount} {$cc}";
                }

                return "Kabul: {$r->commission_kabul_amount} {$cc} | Partner: {$r->commission_partner_amount} {$cc}";
            })
            ->addColumn('actions', function ($r) {
                return view('admin.remittances.partials.actions', compact('r'))->render();
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:incoming,outgoing',

            'from_account_id' => 'nullable|exists:accounts,id',
            'to_account_id' => 'nullable|exists:accounts,id',
            'track_account_id' => 'nullable|exists:accounts,id',

            'hawala_number' => 'nullable|string|max:255',
            'track_hawala_number' => 'nullable|string|max:255',
            'sender' => 'nullable|string|max:255',
            'receiver' => 'nullable|string|max:255',

            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0.01',

            'partner_currency_id' => 'nullable|exists:currencies,id',
            'partner_amount' => 'nullable|numeric|min:0.01',

            'actual_currency_id' => 'nullable|exists:currencies,id',
            'actual_amount' => 'nullable|numeric|min:0.01',

            'remittance_mode' => 'required|in:track,no_track,treasure',

            'commission_mode' => 'required|in:none,company,split,pay',
            'commission_currency_id' => 'required_if:commission_mode,company,split,pay|nullable|exists:currencies,id',
            'commission_kabul_amount' => 'nullable|numeric|min:0',
            'commission_partner_amount' => 'nullable|numeric|min:0',

            'description' => 'nullable|string',
        ]);

        // Business validation
        if ($request->type === 'incoming' && !$request->from_account_id) {
            return response()->json(['message' => 'From saraf is required for incoming remittance'], 422);
        }

        if ($request->type === 'outgoing' && !$request->to_account_id) {
            return response()->json(['message' => 'To saraf is required for outgoing remittance'], 422);
        }

        // Main selected account for account statement page
        $selectedAccountId = $request->type === 'incoming'
            ? $request->from_account_id
            : $request->to_account_id;

        $selectedAccount = Account::find($selectedAccountId);

        $isSubCategoryOne = $selectedAccount && (int) $selectedAccount->account_sub_category_id === 1;

        // If account_sub_category_id = 1, outgoing remittance must be track
        if ($isSubCategoryOne && $request->type === 'outgoing') {
            $hawalaNumber = null;
            $remittanceMode = 'track';
            $trackAccountId = $request->track_account_id;
            $trackHawalaNumber = $request->track_hawala_number ?? null;
        } else {
            $hawalaNumber = $request->hawala_number;
            $remittanceMode = $request->remittance_mode;
            $trackAccountId = $request->track_account_id;
            $trackHawalaNumber = $request->track_hawala_number ?? null;
        }

        // Track hawala number is required for outgoing remittance
        if ($request->type === 'outgoing' && $isSubCategoryOne && !$request->track_account_id) {
            return response()->json([
                'message' => 'Track account is required for this outgoing remittance.'
            ], 422);
        }

        // Partner currency/amount needed when saraf side is different
        if (!$request->partner_currency_id || !$request->partner_amount) {
            return response()->json(['message' => 'Partner currency and partner amount are required'], 422);
        }

        $commissionMode = $request->commission_mode;
        $kabulCommission = (float) ($request->commission_kabul_amount ?? 0);
        $partnerCommission = (float) ($request->commission_partner_amount ?? 0);

        if ($commissionMode === 'none') {
            $kabulCommission = 0;
            $partnerCommission = 0;
        }

        if ($commissionMode === 'company') {
            $partnerCommission = 0;
        }

        if ($commissionMode === 'split' && ($kabulCommission <= 0 || $partnerCommission <= 0)) {
            return response()->json(['message' => 'Both Kabul and Partner commission are required for split'], 422);
        }

        if ($commissionMode === 'pay') {
            $kabulCommission = 0;

            if ($partnerCommission <= 0) {
                return response()->json(['message' => 'Partner commission required for pay mode'], 422);
            }
        }

        $cashId = Account::where('account_sub_category_id', 3)->value('id');

        if (!$cashId) {
            abort(500, 'Main cash account not configured');
        }

        $linkedId = (string) Str::uuid();

        DB::transaction(function () use (
            $request,
            $cashId,
            $linkedId,
            $kabulCommission,
            $partnerCommission,
            $hawalaNumber,
            $remittanceMode,
            $trackAccountId,
            $trackHawalaNumber
        ) {
            // incoming no_track starts pending, otherwise paid
            $status = (
                $remittanceMode === 'no_track' && $request->input('type') === 'incoming') ? 'pending' : 'paid';

            $r = Remittance::create([
                'date' => $request->date,
                'type' => $request->type,

                'from_account_id' => $request->type === 'incoming' ? $request->to_account_id : $request->from_account_id,
                'to_account_id' => $request->type === 'incoming' ? $request->from_account_id : $request->to_account_id,

                'track_account_id' => $trackAccountId,
                'hawala_number' => $hawalaNumber,
                'track_hawala_number' => $trackHawalaNumber,

                'sender' => $request->sender,
                'receiver' => $request->receiver,

                'currency_id' => $request->currency_id,
                'amount' => $request->amount,

                'partner_currency_id' => $request->partner_currency_id,
                'partner_amount' => $request->partner_amount,

                'actual_currency_id' => $request->actual_currency_id,
                'actual_amount' => $request->actual_amount,

                'remittance_mode' => $remittanceMode,

                'commission_mode' => $request->commission_mode,
                'commission_currency_id' => $request->commission_mode === 'none' ? null : $request->commission_currency_id,
                'commission_kabul_amount' => $kabulCommission,
                'commission_partner_amount' => $partnerCommission,

                'status' => $status,
                'linked_id' => $linkedId,
                'description' => $request->description,
                'created_by' => auth()->id(),
            ]);

            logActivity(
                $r,
                "حواله ثبت شد: {$r->amount} | نوع: {$r->type}",
                [
                    'remittance_id' => $r->id,
                    'type' => $r->type,
                    'remittance_mode' => $r->remittance_mode,
                    'amount' => $r->amount,
                    'currency_id' => $r->currency_id,
                    'partner_amount' => $r->partner_amount,
                    'partner_currency_id' => $r->partner_currency_id,
                    'from_account_id' => $r->from_account_id,
                    'to_account_id' => $r->to_account_id,
                    'track_account_id' => $r->track_account_id,
                    'hawala_number' => $r->hawala_number,
                    'track_hawala_number' => $r->track_hawala_number,
                    'sender' => $r->sender,
                    'receiver' => $r->receiver,
                    'description' => $r->description,
                ],
                'created'
            );

            $page = $request->page ?? 'remittance';

            $this->postTransactionsForRemittance($r, $cashId, $page, $linkedId);
        });

        if ($request->type === 'outgoing') {
            return response()->json(['message' => __('ui.outgoing_remittance_stored')]);
        }

        return response()->json(['message' => __('ui.incoming_remittance_stored')]);
    }

    public function markPaid(Request $request, $id)
    {
        return app(RemittanceController::class)->markPaid($request, $id);
    }

    public function reject($id)
    {
        $r = Remittance::findOrFail($id);

        DB::transaction(function () use ($r) {
            $r = Remittance::lockForUpdate()->findOrFail($r->id);
            if ($r->paid_at) {
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => __('remittance_payment.locked')]);
            }
            TransactionService::deleteByLinkedId($r->linked_id);
            $r->update(['status' => 'rejected']);

            logActivity(
                $r,
                "حواله رد شد: {$r->amount}",
                [
                    'remittance_id' => $r->id,
                    'amount' => $r->amount,
                    'currency_id' => $r->currency_id,
                ],
                'rejected'
            );
        });

        return response()->json(['message' => 'Rejected']);
    }

    public function destroy(Remittance $remittance)
    {
        DB::transaction(function () use ($remittance) {
            $remittance = Remittance::lockForUpdate()->findOrFail($remittance->id);
            if ($remittance->paid_at) {
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => __('remittance_payment.locked')]);
            }
            logActivity(
                $remittance,
                "حواله حذف شد: {$remittance->amount}",
                [
                    'remittance_id' => $remittance->id,
                    'amount' => $remittance->amount,
                    'currency_id' => $remittance->currency_id,
                    'partner_currency_id' => $remittance->partner_currency_id,
                    'from_account_id' => $remittance->from_account_id,
                    'to_account_id' => $remittance->to_account_id,
                    'track_account_id' => $remittance->track_account_id,
                ],
                'deleted'
            );

            TransactionService::deleteByLinkedId($remittance->linked_id);
            $remittance->delete();
        });

        return response()->json(['message' => 'Deleted']);
    }

    public function print(Remittance $remittance)
    {
        $remittance->load([
            'fromAccount',
            'toAccount',
            'trackAccount',
            'currency',
            'partnerCurrency',
            'commissionCurrency'
        ]);

        return view('admin.remittances.print', compact('remittance'));
    }

    public function getAccountBalances(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
        ]);

        $balances = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.code as currency',
                DB::raw("SUM(CASE WHEN transaction_type='credit' THEN amount ELSE 0 END) AS credit"),
                DB::raw("SUM(CASE WHEN transaction_type='debit' THEN amount ELSE 0 END) AS debit")
            )
            ->where('account_id', $request->account_id)
            ->groupBy('currencies.code')
            ->get();

        $result = [];
        foreach ($balances as $b) {
            $result[$b->currency] = [
                'credit' => (float) $b->credit,
                'debit' => (float) $b->debit,
                'balance' => (float) $b->credit - (float) $b->debit,
            ];
        }

        return response()->json(['balances' => $result]);
    }

    private function postTransactionsForRemittance(Remittance $r, int $cashId, string $page, string $linkedId): void
    {
        $baseNote = trim((string) $r->description);

        // avoid duplicate base postings
        if (Transaction::where('linked_id', $linkedId)->exists()) {
            return;
        }

        if ($r->type === 'outgoing') {
            $this->postOutgoingTransactions($r, $cashId, $linkedId, $baseNote, $page);
        }

        if ($r->type === 'incoming') {
            $this->postIncomingInitialTransactions($r, $cashId, $linkedId, $baseNote, $page);
        }

        $this->postCommissionTransactions($r, $cashId, $linkedId);
    }

    private function postOutgoingTransactions(Remittance $r, int $cashId, string $linkedId, string $baseNote, string $page): void
    {
        // OUTGOING rules:
        // treasure => cash credit (local currency), saraf credit (partner currency)
        // no_track => only saraf credit (partner currency)
        // track => saraf credit (partner currency), track account debit (local currency)
        $outgoingHawalaText = __('ui.outgoing');
        $incomingHawalaText = __('ui.incoming');

        if ($r->remittance_mode === 'treasure') {

            // 1. Credit CASH (currency_id / amount)
            $note = $this->formatRemittanceNote($r, 'outgoing');
            $cashNote = $this->formatRemittanceNote(
                $r, 'outgoing', cashCounterpart: $r->toAccount
            );

            $this->createTransaction(
                $linkedId,
                $r->id,
                $cashId,
                $r->partner_currency_id,
                $r->partner_amount,

                $r->partner_currency_id,
                $r->partner_amount,

                $r->actual_currency_id,
                $r->actual_amount,

                'credit',
                1,
                $cashNote,
                null
            );

            // 2. Debit SARAF / RECEIVER (partner currency)
            $this->createTransaction(
                $linkedId,
                $r->id,
                $r->to_account_id,
                $r->currency_id,
                $r->amount,

                $r->currency_id,
                $r->amount,

                $r->actual_currency_id,
                $r->actual_amount,

                'credit',
                0,
                $note,
                null
            );
        }

        if ($r->remittance_mode === 'track') {
            $creditNote = $this->formatOutgoingTrackedTransactionNote($r, 'credit');
            $debitNote = $this->formatOutgoingTrackedTransactionNote($r, 'debit');


            $payingPartnerAccount = $r->to_account_id;       // Ghazni branch / partner who pays receiver
            $trackAccount         = $r->track_account_id;    // Kabul branch / settlement account

            $this->createTransaction(
                $linkedId,
                $r->id,
                $payingPartnerAccount,

                $r->currency_id,
                $r->amount,

                $r->currency_id,
                $r->amount,

                $r->actual_currency_id,
                $r->actual_amount,

                'credit',
                0,
                $creditNote,
                null
            );

            $this->createTransaction(
                $linkedId,
                $r->id,
                $trackAccount,

                $r->partner_currency_id,
                $r->partner_amount,

                $r->partner_currency_id,
                $r->partner_amount,

                $r->partner_currency_id,
                $r->partner_amount,

                'debit',
                0,
                $debitNote,
                null
            );
        }
    }

    private function postIncomingInitialTransactions(Remittance $r, int $cashId, string $linkedId, string $baseNote, string $page): void
    {
        $unpaidAccountId = 4;
        $incomingHawalaText = __('ui.incoming');
        $outgoingHawalaText = __('ui.outgoing');
        $status = $r->status === 'pending' ? 'pending' : '';
        if ($r->remittance_mode === 'no_track') {
            $incomingnote = $this->formatRemittanceNote($r, 'incoming', pending: (bool) $status);
            $outgoingnote = $this->formatRemittanceNote($r, 'outgoing', pending: (bool) $status);
        } else if ($r->remittance_mode === 'track') {
            $outgoingnote = $this->formatRemittanceNote(
                $r,
                'outgoing',
                $r->track_hawala_number,
                $r->toAccount,
                $r->hawala_number
            );
            $incomingnote = $this->formatRemittanceNote(
                $r,
                'incoming',
                $r->hawala_number,
                $r->trackAccount,
                $r->track_hawala_number
            );
            $note = $incomingnote;
        } else if ($r->remittance_mode === 'treasure') {
            $incomingnote = $this->formatRemittanceNote($r, 'incoming', pending: (bool) $status);
            $outgoingnote = $this->formatRemittanceNote($r, 'outgoing', pending: (bool) $status);
            $cashNote = $this->formatRemittanceNote(
                $r, 'incoming', pending: (bool) $status, cashCounterpart: $r->toAccount
            );
            $note = $incomingnote;
        }

        if ($r->remittance_mode === 'treasure') {
            $this->createTransaction(
                $linkedId,
                $r->id,
                $cashId,
                $r->partner_currency_id,
                $r->partner_amount,

                $r->partner_currency_id,
                $r->partner_amount,

                $r->actual_currency_id,
                $r->actual_amount,
                'debit',
                0,
                $cashNote,
                null
            );

            $this->createTransaction(
                $linkedId,
                $r->id,
                $r->to_account_id,

                $r->currency_id,
                $r->amount,

                $r->currency_id,
                $r->amount,
                $r->actual_currency_id,
                $r->actual_amount,
                'debit',
                0,
                $incomingnote,
                null
            );
        } else if ($r->remittance_mode === 'no_track') {
            $this->createTransaction(
                $linkedId,
                $r->id,
                $unpaidAccountId,
                $r->partner_currency_id,
                $r->partner_amount,
                $r->currency_id,
                $r->amount,
                $r->actual_currency_id,
                $r->actual_amount,
                'credit',
                0,
                $outgoingnote,
                null
            );

            $this->createTransaction(
                $linkedId,
                $r->id,
                $r->to_account_id,
                $r->currency_id,
                $r->amount,
                $r->currency_id,
                $r->amount,
                $r->actual_currency_id,
                $r->actual_amount,
                'debit',
                0,
                $incomingnote,
                null
            );
        }



        // track logic stays same if needed
        if ($r->remittance_mode === 'track' && $r->track_account_id) {
            $payingPartnerAccount = $r->to_account_id;       // Ghazni branch / partner who pays receiver
            $trackAccount         = $r->track_account_id;    // Kabul branch / settlement account

            $this->createTransaction(
                $linkedId,
                $r->id,
                $trackAccount,


                $r->partner_currency_id,
                $r->partner_amount,

                $r->partner_currency_id,
                $r->partner_amount,

                $r->actual_currency_id,
                $r->actual_amount,

                'credit',
                0,
                $outgoingnote,
                null
            );

            $this->createTransaction(
                $linkedId,
                $r->id,
                $payingPartnerAccount,

                $r->currency_id,
                $r->amount,

                $r->currency_id,
                $r->amount,

                $r->actual_currency_id,
                $r->actual_amount,

                'debit',
                0,
                $incomingnote,
                null
            );
        }
    }

    private function postIncomingCashOnMarkPaid(Remittance $r, int $cashId): void
    {
        $markPaidKey = $r->linked_id . '-mark-paid';
        $unpaidAccountId = 4;

        if (Transaction::where('linked_id', $markPaidKey)->exists()) {
            return;
        }
        $incomingHawalaText = __('ui.incoming');
        $outgoingHawalaText = __('ui.outgoing');
        $incomingnote = $this->formatRemittanceNote($r, 'incoming', payment: true);
        $cashNote = $this->formatRemittanceNote(
            $r, 'incoming', payment: true, cashCounterpart: $r->toAccount
        );

        // 1. Debit UNPAID ACCOUNT (clear liability)
        $this->createTransaction(
            $markPaidKey,
            $r->id,
            $unpaidAccountId,
            $r->partner_currency_id,
            $r->partner_amount,
            $r->partner_currency_id,
            $r->partner_amount,
            $r->partner_currency_id,
            $r->partner_amount,
            'debit',
            0,
            $incomingnote,
            null
        );

        $this->createTransaction(
            $markPaidKey,
            $r->id,
            $cashId,
            $r->partner_currency_id,
            $r->partner_amount,
            $r->partner_currency_id,
            $r->partner_amount,
            $r->actual_currency_id,
            $r->actual_amount,
            'debit',
            1,
            $cashNote,
            null
        );
    }

    private function postCommissionTransactions(Remittance $r, int $cashId, string $linkedId): void
    {
        if (!$r->commission_mode || $r->commission_mode === 'none') {
            return;
        }

        if (!$r->commission_currency_id) {
            return;
        }

        $profitAccountId = Account::where('account_sub_category_id', 4)->value('id');

        // if (!$profitAccountId) {
        //     \Log::warning('COMMISSION STOPPED: profit account not found', [
        //         'remittance_id' => $r->id,
        //     ]);

        //     return;
        // }

        $currencyId = (int) $r->commission_currency_id;

        $companyAmount = round((float) ($r->commission_kabul_amount ?? 0), 2);
        $partnerAmount = round((float) ($r->commission_partner_amount ?? 0), 2);

        // if ($companyAmount <= 0 && $partnerAmount <= 0) {
        //     \Log::warning('COMMISSION STOPPED: both commission amounts are zero', [
        //         'remittance_id' => $r->id,
        //         'companyAmount' => $companyAmount,
        //         'partnerAmount' => $partnerAmount,
        //     ]);

        //     return;
        // }

        $payerAccountId = null;
        $trackAccountId = $r->track_account_id ? (int) $r->track_account_id : null;

        if ($r->type === 'outgoing') {
            $payerAccountId = $r->to_account_id ? (int) $r->to_account_id : null;
        }

        if ($r->type === 'incoming') {
            $payerAccountId = $r->from_account_id
                ? (int) $r->from_account_id
                : ($r->to_account_id ? (int) $r->to_account_id : null);
        }

        // if (!$payerAccountId) {
        //     \Log::warning('COMMISSION STOPPED: payer account missing', [
        //         'remittance_id' => $r->id,
        //         'type' => $r->type,
        //         'from_account_id' => $r->from_account_id,
        //         'to_account_id' => $r->to_account_id,
        //     ]);

        //     return;
        // }

        // if ($r->remittance_mode === 'track' && !$trackAccountId) {
        //     \Log::warning('COMMISSION STOPPED: track account required for track mode', [
        //         'remittance_id' => $r->id,
        //         'remittance_mode' => $r->remittance_mode,
        //         'track_account_id' => $r->track_account_id,
        //     ]);

        //     return;
        // }

        // \Log::info('COMMISSION METHOD STARTED', [
        //     'remittance_id' => $r->id,
        //     'hawala_number' => $r->hawala_number,
        //     'commission_mode' => $r->commission_mode,
        //     'remittance_mode' => $r->remittance_mode,
        //     'type' => $r->type,
        //     'commission_currency_id' => $r->commission_currency_id,
        //     'companyAmount' => $companyAmount,
        //     'partnerAmount' => $partnerAmount,
        //     'payerAccountId' => $payerAccountId,
        //     'trackAccountId' => $trackAccountId,
        //     'profitAccountId' => $profitAccountId,
        //     'cashId' => $cashId,
        // ]);

        $post = function (
            int $accountId,
            float $amount,
            string $direction,
            string $description,
            string $status = 'commission'
        ) use ($linkedId, $r, $currencyId) {
            $amount = round((float) $amount, 2);

            if ($amount <= 0) {
                return;
            }

            $this->createTransaction(
                $linkedId,
                $r->id,
                $accountId,
                $currencyId,
                $amount,

                $currencyId,
                $amount,
                $currencyId,
                $amount,

                $direction,
                0,
                $description,
                $status
            );
        };

        /*
        |--------------------------------------------------------------------------
        | Company Commission
        |--------------------------------------------------------------------------
        */
        if ($r->commission_mode === 'company') {
            if ($companyAmount <= 0) {
                return;
            }

            if ($r->type === 'outgoing' && $r->remittance_mode === 'track') {
                $post(
                    $payerAccountId,
                    $companyAmount,
                    'debit',
                    "کمیشن از حساب منفی شد | حواله نمبر: {$r->hawala_number}"
                );

                $post(
                    (int) $profitAccountId,
                    $companyAmount,
                    'credit',
                    "کمیشن شرکت به مفاد و ضرر جمع شد | حواله نمبر: {$r->hawala_number}"
                );

                return;
            }

            if ($r->type === 'outgoing' && in_array($r->remittance_mode, ['treasure', 'no_track'], true)) {
                $post(
                    (int) $profitAccountId,
                    $companyAmount,
                    'credit',
                    "کمیشن شرکت به مفاد و ضرر جمع شد | حواله نمبر: {$r->hawala_number}"
                );

                $post(
                    (int) $cashId,
                    $companyAmount,
                    'credit',
                    "کمیشن شرکت به صندوق جمع شد | حواله نمبر: {$r->hawala_number}"
                );

                return;
            }

            if ($r->type === 'incoming' && $r->remittance_mode === 'track') {
                $post(
                    (int) $profitAccountId,
                    $companyAmount,
                    'credit',
                    "کمیشن شرکت به مفاد و ضرر جمع شد | حواله نمبر: {$r->hawala_number}"
                );

                $post(
                    (int) $cashId,
                    $companyAmount,
                    'credit',
                    "کمیشن شرکت به صندوق جمع شد | حواله نمبر: {$r->hawala_number}"
                );

                $post(
                    $r->to_account_id,
                    $companyAmount,
                    'debit',
                    "کمیشن شرکت از حساب " . optional($r->trackAccount)->name . " منفی شد | حواله نمبر: {$r->hawala_number}"
                );

                return;
            }

            if ($r->type === 'incoming' && in_array($r->remittance_mode, ['treasure', 'no_track'], true)) {
                $post(
                    (int) $profitAccountId,
                    $companyAmount,
                    'credit',
                    "کمیشن شرکت به مفاد و ضرر جمع شد | حواله نمبر: {$r->hawala_number}"
                );

                $post(
                    (int) $cashId,
                    $companyAmount,
                    'credit',
                    "کمیشن شرکت به صندوق جمع شد | حواله نمبر: {$r->hawala_number}"
                );

                $post(
                    $r->to_account_id,
                    $companyAmount,
                    'debit',
                    "کمیشن شرکت از " . optional($r->fromAccount)->name . " منفی شد | حواله نمبر: {$r->hawala_number}"
                );

                return;
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Split Commission
        |--------------------------------------------------------------------------
        */
        if ($r->commission_mode === 'split') {
            $totalCommission = round($companyAmount + $partnerAmount, 2);

            if ($totalCommission <= 0) {
                return;
            }

            /*
        |--------------------------------------------------------------------------
        | OUTGOING + TRACK + SPLIT
        |--------------------------------------------------------------------------
        */
            if ($r->type === 'outgoing' && $r->remittance_mode === 'track') {
                $post(
                    $payerAccountId,
                    $totalCommission,
                    'debit',
                    "مجموع کمیشن از حساب منفی شد | حواله نمبر: {$r->hawala_number}"
                );

                if ($companyAmount > 0) {
                    $post(
                        (int) $profitAccountId,
                        $companyAmount,
                        'credit',
                        "کمیشن شرکت به مفاد و ضرر جمع شد | حواله نمبر: {$r->hawala_number}"
                    );
                }

                if ($partnerAmount > 0) {
                    $post(
                        (int) $trackAccountId,
                        $partnerAmount,
                        'credit',
                        "کمیشن درک به حساب درک جمع شد | حواله نمبر: {$r->hawala_number}"
                    );
                }

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | OUTGOING + TREASURE / NO_TRACK + SPLIT
            |--------------------------------------------------------------------------
            */
            if ($r->type === 'outgoing' && in_array($r->remittance_mode, ['treasure', 'no_track'], true)) {
                if ($companyAmount > 0) {
                    $post(
                        (int) $profitAccountId,
                        $companyAmount,
                        'credit',
                        "کمیشن شرکت به مفاد و ضرر جمع شد | حواله نمبر: {$r->hawala_number}"
                    );

                    $post(
                        (int) $cashId,
                        $companyAmount,
                        'credit',
                        "کمیشن شرکت به صندوق جمع شد | حواله نمبر: {$r->hawala_number}"
                    );
                }

                if ($partnerAmount > 0) {
                    // Partner commission is also received in safe/cash
                    $post(
                        (int) $cashId,
                        $partnerAmount,
                        'credit',
                        "کمیشن شریک به صندوق جمع شد و بالای ما قابل پرداخت است | حواله نمبر: {$r->hawala_number}"
                    );

                    // Partner/saraf account is credited because this amount is owed by us
                    $post(
                        $payerAccountId,
                        $partnerAmount,
                        'credit',
                        "کمیشن شریک به حساب " . optional($r->toAccount)->name . " جمع شد و بالای ما طلب شد | حواله نمبر: {$r->hawala_number}"
                    );
                }

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | INCOMING + TRACK + SPLIT
            |--------------------------------------------------------------------------
            */
            if ($r->type === 'incoming' && $r->remittance_mode === 'track') {
                if ($companyAmount > 0) {
                    $post(
                        (int) $profitAccountId,
                        $companyAmount,
                        'credit',
                        "کمیشن شرکت به مفاد و ضرر جمع شد | حواله نمبر: {$r->hawala_number}"
                    );

                    $post(
                        (int) $cashId,
                        $companyAmount,
                        'credit',
                        "کمیشن شرکت به صندوق جمع شد | حواله نمبر: {$r->hawala_number}"
                    );
                }

                if ($partnerAmount > 0) {
                    $post(
                        (int) $trackAccountId,
                        $partnerAmount,
                        'credit',
                        "کمیشن شریک به حساب درک جمع شد | حواله نمبر: {$r->hawala_number}"
                    );
                }

                $post(
                    $payerAccountId,
                    $totalCommission,
                    'debit',
                    "مجموع کمیشن شرکت و شریک از حساب " . optional($r->fromAccount)->name . " منفی شد | حواله نمبر: {$r->hawala_number}"
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | INCOMING + TREASURE / NO_TRACK + SPLIT
            |--------------------------------------------------------------------------
            */
            if ($r->type === 'incoming' && in_array($r->remittance_mode, ['treasure', 'no_track'], true)) {
                if ($companyAmount > 0) {
                    $post(
                        (int) $profitAccountId,
                        $companyAmount,
                        'credit',
                        "کمیشن شرکت به مفاد و ضرر جمع شد | حواله نمبر: {$r->hawala_number}"
                    );

                    $post(
                        (int) $cashId,
                        $companyAmount,
                        'credit',
                        "کمیشن شرکت به صندوق جمع شد | حواله نمبر: {$r->hawala_number}"
                    );

                    $post(
                        $payerAccountId,
                        $companyAmount,
                        'debit',
                        "کمیشن شرکت از حساب " . optional($r->fromAccount)->name . " منفی شد | حواله نمبر: {$r->hawala_number}"
                    );
                }

                if ($partnerAmount > 0) {
                    // Partner commission is also received in safe/cash
                    $post(
                        (int) $cashId,
                        $partnerAmount,
                        'credit',
                        "کمیشن شریک به صندوق جمع شد و بالای ما قابل پرداخت است | حواله نمبر: {$r->hawala_number}"
                    );

                    // Partner/saraf account is credited because this amount is owed by us
                    $post(
                        $payerAccountId,
                        $partnerAmount,
                        'credit',
                        "کمیشن شریک به حساب " . optional($r->fromAccount)->name . " جمع شد و بالای ما طلب شد | حواله نمبر: {$r->hawala_number}"
                    );
                }

                return;
            }

            return;
        }
    }


    private function createTransaction(
        string $linkedId,
        int $remittanceId,
        ?int $accountId,
        ?int $currencyId,
        ?float $amount,
        ?int $paymentCurrencyId,
        ?float $paymentAmount,
        ?int $actualCurrencyId,
        ?float $actualAmount,
        string $transactionType,
        int $isCash,
        string $note,
        string $category = null
    ): void {
        if (!$accountId || !$currencyId || !$amount || $amount <= 0) {
            return;
        }

        Transaction::create([
            'linked_id' => $linkedId,
            'table_name' => 'remittances',
            'table_row_id' => $remittanceId,
            'account_id' => $accountId,
            'currency_id' => $currencyId,
            'amount' => $amount,
            'payment_currency_id' => $paymentCurrencyId,
            'payment_amount' => $paymentAmount,
            'actual_currency_id' => $actualCurrencyId,
            'actual_amount' => $actualAmount,
            'transaction_type' => $transactionType,
            'is_cash' => $isCash,
            'note' => $note,
            'status' => 'active',
            'is_visible' => 1,
            'created_by' => auth()->id(),
            'category' => $category
        ]);
    }

    public function accountReport($accountId)
    {
        $data = DB::table('transactions')
            ->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId);
            })
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($data);
    }

    public function update(Request $request, Remittance $remittance)
    {
        if ($request->type === 'incoming' && $request->remittance_mode === 'track') {
            $request->merge(['from_account_id' => 1]);
            $request->validate(['to_account_id' => 'required|exists:accounts,id']);
        }

        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:incoming,outgoing',

            'from_account_id' => 'nullable|exists:accounts,id',
            'to_account_id' => 'nullable|exists:accounts,id',
            'track_account_id' => 'nullable|exists:accounts,id',

            'hawala_number' => 'nullable|string|max:255',
            'track_hawala_number' => 'nullable|string|max:255',
            'sender' => 'nullable|string|max:255',
            'receiver' => 'nullable|string|max:255',

            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0.01',

            'partner_currency_id' => 'nullable|exists:currencies,id',
            'partner_amount' => 'nullable|numeric|min:0.01',

            'actual_currency_id' => 'nullable|exists:currencies,id',
            'actual_amount' => 'nullable|numeric|min:0.01',

            'remittance_mode' => 'required|in:track,no_track,treasure',

            'commission_mode' => 'required|in:none,company,split,pay',
            'commission_currency_id' => 'required_if:commission_mode,company,split,pay|nullable|exists:currencies,id',
            'commission_kabul_amount' => 'nullable|numeric|min:0',
            'commission_partner_amount' => 'nullable|numeric|min:0',

            'description' => 'nullable|string',
        ]);

        if ($request->type === 'incoming' && !$request->from_account_id) {
            return response()->json(['message' => 'From saraf is required for incoming remittance'], 422);
        }

        if ($request->type === 'outgoing' && !$request->to_account_id) {
            return response()->json(['message' => 'To saraf is required for outgoing remittance'], 422);
        }

        $selectedAccountId = $request->type === 'incoming'
            ? $request->from_account_id
            : $request->to_account_id;

        $selectedAccount = Account::find($selectedAccountId);

        $isSubCategoryOne = $selectedAccount && (int) $selectedAccount->account_sub_category_id === 1;

        if ($isSubCategoryOne && $request->type === 'outgoing') {
            $hawalaNumber = null;
            $remittanceMode = 'track';
            $trackAccountId = $request->track_account_id;
            $trackHawalaNumber = $request->track_hawala_number ?? null;
        } else {
            $hawalaNumber = $request->hawala_number;
            $remittanceMode = $request->remittance_mode;
            $trackAccountId = $request->track_account_id;
            $trackHawalaNumber = $request->track_hawala_number ?? null;
        }

        if ($request->type === 'outgoing' && $isSubCategoryOne && !$request->track_account_id) {
            return response()->json([
                'message' => 'Track account is required for this outgoing remittance.'
            ], 422);
        }

        if (!$request->partner_currency_id || !$request->partner_amount) {
            return response()->json(['message' => 'Partner currency and partner amount are required'], 422);
        }

        $commissionMode = $request->commission_mode;
        $kabulCommission = (float) ($request->commission_kabul_amount ?? 0);
        $partnerCommission = (float) ($request->commission_partner_amount ?? 0);

        if ($commissionMode === 'none') {
            $kabulCommission = 0;
            $partnerCommission = 0;
        }

        if ($commissionMode === 'company') {
            $partnerCommission = 0;
        }

        if ($commissionMode === 'split' && ($kabulCommission <= 0 || $partnerCommission <= 0)) {
            return response()->json(['message' => 'Both Kabul and Partner commission are required for split'], 422);
        }

        if ($commissionMode === 'pay') {
            $kabulCommission = 0;

            if ($partnerCommission <= 0) {
                return response()->json(['message' => 'Partner commission required for pay mode'], 422);
            }
        }

        $cashId = Account::where('account_sub_category_id', 3)->value('id');

        if (!$cashId) {
            abort(500, 'Main cash account not configured');
        }

        DB::transaction(function () use (
            $request,
            $remittance,
            $cashId,
            $kabulCommission,
            $partnerCommission,
            $hawalaNumber,
            $remittanceMode,
            $trackAccountId,
            $trackHawalaNumber
        ) {
            $remittance = Remittance::lockForUpdate()->findOrFail($remittance->id);
            if ($remittance->paid_at) {
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => __('remittance_payment.locked')]);
            }
            $linkedId = $remittance->linked_id ?: (string) Str::uuid();

            Transaction::where('linked_id', $linkedId)->get()->each->delete(); // one by one, so the balances follow

            $status = (
                $remittanceMode === 'no_track' &&
                $request->input('type') === 'incoming'
            ) ? 'pending' : 'paid';

            $remittance->update([
                'date' => $request->date,
                'type' => $request->type,

                'from_account_id' => $request->from_account_id,
                'to_account_id' => $request->to_account_id,

                'track_account_id' => $trackAccountId,
                'hawala_number' => $hawalaNumber,
                'track_hawala_number' => $trackHawalaNumber,

                'sender' => $request->sender,
                'receiver' => $request->receiver,

                'currency_id' => $request->currency_id,
                'amount' => $request->amount,

                'partner_currency_id' => $request->partner_currency_id,
                'partner_amount' => $request->partner_amount,

                'actual_currency_id' => $request->actual_currency_id,
                'actual_amount' => $request->actual_amount,

                'remittance_mode' => $remittanceMode,

                'commission_mode' => $request->commission_mode,
                'commission_currency_id' => $request->commission_mode === 'none' ? null : $request->commission_currency_id,
                'commission_kabul_amount' => $kabulCommission,
                'commission_partner_amount' => $partnerCommission,

                'status' => $status,
                'linked_id' => $linkedId,
                'description' => $request->description,
            ]);

            $page = $request->page ?? 'remittance';

            $this->postTransactionsForRemittance($remittance->fresh(), $cashId, $page, $linkedId);

            logActivity(
                $remittance,
                "حواله بروزرسانی شد: {$remittance->amount} | نوع: {$remittance->type}",
                [
                    'remittance_id' => $remittance->id,
                    'type' => $remittance->type,
                    'remittance_mode' => $remittance->remittance_mode,
                    'amount' => $remittance->amount,
                    'currency_id' => $remittance->currency_id,
                    'partner_amount' => $remittance->partner_amount,
                    'partner_currency_id' => $remittance->partner_currency_id,
                    'from_account_id' => $remittance->from_account_id,
                    'to_account_id' => $remittance->to_account_id,
                    'track_account_id' => $remittance->track_account_id,
                    'hawala_number' => $remittance->hawala_number,
                    'track_hawala_number' => $remittance->track_hawala_number,
                    'sender' => $remittance->sender,
                    'receiver' => $remittance->receiver,
                    'description' => $remittance->description,
                ],
                'updated'
            );
        });

        return response()->json([
            'message' => 'Remittance updated successfully'
        ]);
    }
}
