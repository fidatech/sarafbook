<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Remittance;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\FormatsRemittanceNotes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

class RemittanceController extends Controller
{
    use FormatsRemittanceNotes;

    private function remittanceTypeText(Remittance $r): string
    {
        return $r->type === 'incoming'
            ? __('ui.incoming')
            : __('ui.outgoing');
    }


    public function sendWhatsApp(Remittance $remittance)
    {
        $remittance->loadMissing(['fromAccount', 'toAccount', 'currency', 'commissionCurrency']);

        $account = $remittance->type === 'outgoing'
            ? $remittance->toAccount
            : $remittance->toAccount;

        if (!$account) {
            return response()->json([
                'error' => $remittance->type === 'outgoing'
                    ? 'The outgoing remittance has no destination account.'
                    : 'The incoming remittance has no source account.',
            ], 422);
        }

        if (!$account->contact) {
            return response()->json([
                'error' => "No WhatsApp number was found for {$account->name}.",
            ], 422);
        }

        $phone = preg_replace('/[^0-9]/', '', $account->contact);

        if (!$phone) {
            return response()->json([
                'error' => 'The account WhatsApp number is invalid.',
            ], 422);
        }

        $setting = Setting::query()->first();

        $companyName = $setting?->company_name ?: config('app.name');
        $currencyName = $remittance->currency?->name ?: ($remittance->currency?->code ?? '-');
        $amount = $this->formatWhatsAppAmount($remittance->amount);
        $amountInWords = $this->formatDariAmountInWords($remittance->amount);
        $date = $this->formatAfghanJalaliDate($remittance->date ?? $remittance->created_at);
        $commission = $this->formatWhatsAppCommission($remittance);
        $accountAddress = trim((string) $account->address);
        $accountHeading = $account->name . ($accountAddress !== '' ? " - {$accountAddress}" : '');

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Message
        |--------------------------------------------------------------------------
        */

        if ($remittance->type === 'outgoing') {
            $message = "*{$companyName}*\n";
            $message .= '👤 ' . __('ui.account') . " : {$accountHeading}\n";
            $message .= "📅 تاریخ : {$date}\n\n";
            $message .= "🔢 نمبر حواله : " . ($remittance->hawala_number ?: '-') . "\n";
            $message .= "🔰 فرستنده  : " . ($remittance->sender ?: '-') . "\n";
            $message .= "✅ گیرنده     : " . ($remittance->receiver ?: '-') . "\n";
            $message .= "📗 معلومات : {$commission}\n\n";
            $message .= "🟢 مبلغ : *{$amount} {$currencyName}*\n";
            $message .= "💬 به حروف : *{$amountInWords} {$currencyName}*";
        } else {
            $currencyCode = $remittance->currency?->code ?? '-';
            $dateTime = ($remittance->created_at ?? $remittance->date)
                ?->format('d F Y, h:i A') ?? '-';

            $message = "🏢 *{$companyName}*\n\n";
            $message .= '📅 *' . __('ui.date') . ":*\n{$dateTime}\n\n";
            $message .= '📤 *' . __('ui.sender') . ':* ' . ($remittance->sender ?: '-') . "\n\n";
            $message .= '📥 *' . __('ui.receiver') . ':* ' . ($remittance->receiver ?: '-') . "\n\n";
            $message .= '💰 *' . __('ui.amount') . ":* {$amount} {$currencyCode}\n\n";
            $message .= '📍 *' . __('ui.address') . ':* ' . ($account->address ?: '-') . "\n\n";
        }


        /*
        |--------------------------------------------------------------------------
        | WhatsApp URL
        |--------------------------------------------------------------------------
        */

        $url = 'https://api.whatsapp.com/send/?' . http_build_query(
            [
                'phone' => $phone,
                'text'  => $message,
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        return response()->json([
            'message' => __('remittances.whatsapp_prepared'),
            'url'     => $url,
        ]);
    }

    private function formatAfghanJalaliDate($date): string
    {
        if (!$date) {
            return '-';
        }

        $jalali = Jalalian::fromCarbon(\Illuminate\Support\Carbon::parse($date));
        $months = [
            1 => 'حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله',
            'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت',
        ];

        return $this->toPersianDigits($jalali->getDay())
            . ' - ' . $months[$jalali->getMonth()]
            . ' - ' . $this->toPersianDigits($jalali->getYear());
    }

    private function formatWhatsAppCommission(Remittance $remittance): string
    {
        $commissionAmount = (float) $remittance->commission_kabul_amount
            + (float) $remittance->commission_partner_amount;

        if (!$remittance->commission_mode
            || $remittance->commission_mode === 'none'
            || $commissionAmount <= 0
        ) {
            return 'بدون کمیشن';
        }

        $currency = $remittance->commissionCurrency?->name
            ?: ($remittance->commissionCurrency?->code ?? '');

        return trim($this->formatWhatsAppAmount($commissionAmount) . ' ' . $currency);
    }

    private function formatWhatsAppAmount($amount): string
    {
        if ($amount === null) {
            return '-';
        }

        return rtrim(rtrim(number_format((float) $amount, 2, '.', ','), '0'), '.');
    }

    private function formatDariAmountInWords($amount): string
    {
        if ($amount === null || !class_exists(\NumberFormatter::class)) {
            return $this->formatWhatsAppAmount($amount);
        }

        $formatter = new \NumberFormatter('fa', \NumberFormatter::SPELLOUT);
        $words = $formatter->format((float) $amount);

        return str_replace(
            ['پانصد', 'دویست', 'سیصد', 'چهارصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'],
            ['پنج صد', 'دو صد', 'سه صد', 'چهار صد', 'شش صد', 'هفت صد', 'هشت صد', 'نه صد'],
            $words
        );
    }

    private function toPersianDigits($value): string
    {
        return strtr((string) $value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }

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

        $query = Remittance::query()
            ->where('type', $type)
            ->whereNotNull($column)
            ->where($column, '!=', '');

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
            ->selectRaw("MAX(CAST({$column} AS UNSIGNED)) as max_number")
            ->value('max_number');

        return response()->json([
            'number' => ((int) $lastNumber) + 1,
        ]);
    }

    public function data(Request $request)
    {
        $showPartnerAmount = $request->type === 'incoming' && $request->status === 'pending';

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
            ->when($request->currency_id, fn($x) => $x->where($showPartnerAmount ? 'partner_currency_id' : 'currency_id', $request->currency_id))
            ->when($request->account_id, function ($x) use ($request) {
                $x->where(function ($w) use ($request) {
                    $w->where('from_account_id', $request->account_id)
                        ->orWhere('to_account_id', $request->account_id)
                        ->orWhere('track_account_id', $request->account_id);
                });
            });

        if ($request->date_range) {
            [$start, $end] = explode(' - ', $request->date_range);
            $q->whereBetween('date', [$start, $end]);
        }

        return datatables()->eloquent($q)
            ->addIndexColumn()

            ->addColumn('from_name', function ($r) {
                return $r->fromAccount->name ?? (Account::where('id', 1)->value('name') ?? '-');
            })

            ->addColumn('to_name', function ($r) {
                return $r->toAccount->name ?? (Account::where('id', 1)->value('name') ?? '-');
            })

            ->addColumn('track_name', function ($r) {
                return $r->trackAccount->name ?? (Account::where('id', 1)->value('name') ?? '-');
            })

            ->editColumn('hawala_number', function ($r) {
                return $r->hawala_number ?: '-';
            })

            ->editColumn('sender', function ($r) {
                return $r->sender ?: '-';
            })

            ->editColumn('receiver', function ($r) {
                return $r->receiver ?: '-';
            })

            ->addColumn('currency_code', function ($r) {
                return $r->currency->code ?? '-';
            })

            ->addColumn('partner_currency_code', function ($r) {
                return $r->partnerCurrency->code ?? '-';
            })

            ->addColumn('amount_currency', function ($r) use ($showPartnerAmount) {
                $value = $showPartnerAmount ? $r->partner_amount : $r->amount;
                $amount = function_exists('format_amount')
                    ? format_amount((float) $value, 2)
                    : number_format((float) $value, 2);

                $currency = ($showPartnerAmount ? $r->partnerCurrency : $r->currency)?->code ?? '';

                return '<span class="fw-bold mono">' . e($amount) . '</span> <span class="text-muted small">' . e($currency) . '</span>';
            })

            ->addColumn('amount_text', function ($r) {
                $base = function_exists('format_amount')
                    ? format_amount((float) $r->amount, 2) . ' ' . ($r->currency->code ?? '')
                    : number_format((float) $r->amount, 2) . ' ' . ($r->currency->code ?? '');

                $partner = $r->partner_amount
                    ? (
                        function_exists('format_amount')
                        ? format_amount((float) $r->partner_amount, 2) . ' ' . ($r->partnerCurrency->code ?? '')
                        : number_format((float) $r->partner_amount, 2) . ' ' . ($r->partnerCurrency->code ?? '')
                    )
                    : '-';

                return $base . ' / ' . $partner;
            })

            ->editColumn('date', function ($r) {
                $date = $r->date ? $r->date->format('d M Y') : '-';
                $time = $r->created_at?->format('H:i') ?? '-';

                return '<span class="remittance-date">' . e($date) . '</span>'
                    . '<small class="remittance-time text-muted">' . e($time) . '</small>';
            })

            ->addColumn('commission_currency_code', function ($r) {
                return $r->commissionCurrency->code ?? '-';
            })

            ->addColumn('commission_text', function ($r) {
                if ($r->commission_mode === 'none') {
                    return '-';
                }

                $cc = $r->commissionCurrency->code ?? '';

                if ($r->commission_mode === 'company') {
                    return __('remittances.kabul') . e($r->commission_kabul_amount) . ' ' . e($cc);
                }

                if ($r->commission_mode === 'pay') {
                    return __('remittances.pay') . e($r->commission_partner_amount) . ' ' . e($cc);
                }

                return __('remittances.kabul') . e($r->commission_kabul_amount) . ' ' . e($cc)
                    . ' | ' . __('remittances.partner') . e($r->commission_partner_amount) . ' ' . e($cc);
            })

            ->addColumn('actions', function ($r) {
                return view('admin.remittances.partials.actions', compact('r'))->render();
            })

            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if ($search !== null && trim((string) $search) !== '') {
                    $query->where(function ($q) use ($search) {
                        $q->where('sender', 'like', "%{$search}%")
                            ->orWhere('receiver', 'like', "%{$search}%")
                            ->orWhere('hawala_number', 'like', "%{$search}%")
                            ->orWhere('track_hawala_number', 'like', "%{$search}%")
                            ->orWhereHas('fromAccount', function ($from) use ($search) {
                                $from->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('toAccount', function ($to) use ($search) {
                                $to->where('name', 'like', "%{$search}%");
                            });
                    });
                }
            })

            ->order(function ($query) {
                $query->orderByDesc('id');
            })

            ->rawColumns([
                'date',
                'amount_currency',
                'commission_text',
                'actions'
            ])

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
            return response()->json(['message' => __('remittances.from_required')], 422);
        }

        if ($request->type === 'outgoing' && !$request->to_account_id) {
            return response()->json(['message' => __('remittances.to_required')], 422);
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
                'message' => __('remittances.track_required')
            ], 422);
        }

        // Partner currency/amount needed when saraf side is different
        if (!$request->partner_currency_id || !$request->partner_amount) {
            return response()->json(['message' => __('remittances.partner_required')], 422);
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
            return response()->json(['message' => __('remittances.split_required')], 422);
        }

        if ($commissionMode === 'pay') {
            $kabulCommission = 0;

            if ($partnerCommission <= 0) {
                return response()->json(['message' => __('remittances.pay_required')], 422);
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

                'from_account_id' => $request->type === 'incoming' ? $request->from_account_id : $cashId,
                'to_account_id' => $request->type === 'incoming' ? $cashId : $request->to_account_id,

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

    public function payment(Remittance $remittance)
    {
        abort_unless(auth()->user()->can('update remittances'), 403);
        $remittance->load(['fromAccount', 'toAccount', 'trackAccount', 'currency', 'partnerCurrency', 'actualCurrency', 'commissionCurrency', 'createdBy', 'paidBy']);

        return view('admin.remittances.payment', compact('remittance'));
    }

    public function paymentReceipt(Remittance $remittance)
    {
        abort_unless(auth()->user()->can('update remittances'), 403);
        abort_unless($remittance->status === 'paid' && $remittance->paid_at, 404);
        $remittance->load(['partnerCurrency', 'paidBy', 'fromAccount', 'toAccount']);

        return response()->view('admin.remittances.payment-receipt', compact('remittance'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function paymentEvidence(Remittance $remittance, string $kind)
    {
        abort_unless(auth()->user()->can('update remittances'), 403);
        $field = match ($kind) {
            'id-copy' => 'receiver_id_copy_path',
            'fingerprint' => 'receiver_fingerprint_path',
            'photo' => 'receiver_photo_path',
            default => abort(404),
        };
        $path = $remittance->{$field};
        abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function markPaid(Request $request, $id)
    {
        abort_unless($request->user()->can('update remittances'), 403);
        $data = $request->validate([
            'receiver_id_number' => ['nullable', 'string', 'max:100'],
            'receiver_id_copy' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'receiver_fingerprint' => ['prohibited'],
            'receiver_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_confirmed' => ['accepted'],
        ]);
        $stored = [];
        try {
            DB::transaction(function () use ($request, $id, $data, &$stored) {
                $r = Remittance::lockForUpdate()->findOrFail($id);
                if ($r->type !== 'incoming' || $r->status !== 'pending' || $r->paid_at || $r->remittance_mode !== 'no_track') {
                    throw \Illuminate\Validation\ValidationException::withMessages(['payment' => __('remittance_payment.only_pending')]);
                }
                $cashId = Account::where('account_sub_category_id', 3)->value('id');
                if (!$cashId || !Account::whereKey(4)->exists() || !$r->linked_id || !$r->partnerCurrency || $r->partner_amount <= 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['payment' => __('remittance_payment.invalid_accounts')]);
                }
                if (Transaction::where('linked_id', $r->linked_id . '-mark-paid')->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['payment' => __('remittance_payment.already_posted')]);
                }

                foreach (['receiver_id_copy', 'receiver_photo'] as $field) {
                    if ($request->hasFile($field)) {
                        $path = $request->file($field)->store('remittance-payments/' . $r->id, 'local');
                        if (!$path) {
                            throw new \RuntimeException('Unable to store remittance payment evidence.');
                        }
                        $stored[] = $path;
                        $r->{$field . '_path'} = $path;
                    }
                }

                $this->postIncomingCashOnMarkPaid($r, $cashId);
                $r->receiver_id_number = $data['receiver_id_number'] ?? null;
                $r->paid_by = $request->user()->id;
                $r->paid_at = now();
                $r->status = 'paid';
                $r->save();
                logActivity($r, 'Remittance approved and paid', [
                    'remittance_id' => $r->id,
                    'amount' => $r->partner_amount,
                    'currency_id' => $r->partner_currency_id,
                    'paid_by' => $r->paid_by,
                    'identity_confirmed' => true,
                ], 'paid');
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($stored);
            throw $e;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('remittance_payment.success'),
                'receipt_url' => route('admin.remittances.payment-receipt', $id),
            ]);
        }

        return redirect()->route('admin.remittances.payment-receipt', $id)->with('success', __('remittance_payment.success'));
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

        return response()->json(['message' => __('remittances.rejected')]);
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

        return response()->json(['message' => __('ui.blade_deleted')]);
    }

    public function print(Remittance $remittance)
    {
        $remittance->load([
            'fromAccount',
            'toAccount',
            'trackAccount',
            'currency',
            'partnerCurrency',
            'commissionCurrency',
        ]);

        $settings = Setting::first();

        return view('admin.remittances.print', compact(
            'remittance',
            'settings'
        ));
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
        $pendubgText = __('ui.pending');
        $statusText = $status ? " | {$pendubgText}" : '';
        if ($r->remittance_mode === 'no_track') {
            $incomingnote = $this->formatRemittanceNote($r, 'incoming', pending: (bool) $status);
            $outgoingnote = $this->formatRemittanceNote($r, 'outgoing', pending: (bool) $status);
        } else if ($r->remittance_mode === 'track') {
            $outgoingnote = $this->formatIncomingTrackedTransactionNote($r, 'credit');
            $incomingnote = $this->formatIncomingTrackedTransactionNote($r, 'debit');
            $note = $incomingnote;
        } else if ($r->remittance_mode === 'treasure') {
            $incomingnote = $this->formatRemittanceNote($r, 'incoming', pending: (bool) $status);
            $outgoingnote = $this->formatRemittanceNote($r, 'outgoing', pending: (bool) $status);
            $note = $incomingnote;
        }

        if ($r->remittance_mode === 'treasure') {
            $this->createTransaction(
                $linkedId,
                $r->id,
                $r->from_account_id,

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
                $r->from_account_id,
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
                $r->from_account_id,

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
            $r, 'incoming', payment: true, cashCounterpart: $r->fromAccount
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
                    $trackAccountId,
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
                    (int) $r->from_account_id,
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
                    "کمیشن شرکت از حساب " . optional($r->fromAccount)->name . " منفی شد | حواله نمبر: {$r->hawala_number}"
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
                    $trackAccountId,
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
                        (int) $payerAccountId,
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

    public function createNew()
    {
        return view('admin.remittances.create', [
            'sarafs' => Account::where('account_sub_category_id', 2)->orderBy('name')->get(),
            'accounts' => Account::orderBy('name')->get(),
            'currencies' => Currency::orderBy('id')->get(),
        ]);
    }

    private function listPage(string $title, ?string $type = null, ?string $status = null)
    {
        return view('admin.remittances.list', [
            'pageTitle' => $title,
            'presetType' => $type,
            'presetStatus' => $status,
            'sarafs' => Account::where('account_sub_category_id', 2)->orderBy('name')->get(),
            'currencies' => Currency::orderBy('id')->get(),
        ]);
    }

    public function outgoing()
    {
        return $this->listPage(__('ui.outgoing_remittances'), 'outgoing', null);
    }

    public function incomingPaid()
    {
        return $this->listPage(__('ui.incoming_paid_remittances'), 'incoming', 'paid');
    }

    public function pendingIncoming()
    {
        return $this->listPage(__('ui.pending_incoming_remittances'), 'incoming', 'pending');
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
}
