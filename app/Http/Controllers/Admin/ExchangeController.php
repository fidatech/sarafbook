<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\ExchangeRate;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Support\FormatsExchangeNotes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExchangeController extends Controller
{
    use FormatsExchangeNotes;

    /**
     * The exchanges in the fida-sarafi style: filtered and paged on the server, with the count,
     * the volume in US dollars and the profit by currency of what is shown.
     */
    public function index(Request $request)
    {
        $currencies = Currency::orderBy('id')->get();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => in_array($request->query('type'), ['sell', 'purchase'], true) ? $request->query('type') : '',
            'mode' => in_array($request->query('mode'), ['cash', 'account'], true) ? $request->query('mode') : '',
            'currency_id' => $request->integer('currency_id') ?: null,
            'from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('from')) ? $request->query('from') : '',
            'to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('to')) ? $request->query('to') : '',
        ];
        $filtered = collect($filters)->filter(fn ($v) => $v !== '' && $v !== null)->isNotEmpty();
        $base = fn () => Exchange::query()
            ->when($filters['type'] !== '', fn ($q) => $q->where('type', $filters['type']))
            ->when($filters['mode'] === 'cash', fn ($q) => $q->where('mode', 'cash'))
            ->when($filters['mode'] === 'account', fn ($q) => $q->where('mode', '!=', 'cash'))
            ->when($filters['currency_id'], fn ($q, $v) => $q->where(fn ($w) => $w->where('from_currency_id', $v)->orWhere('to_currency_id', $v)))
            ->when($filters['from'] !== '', fn ($q) => $q->where('date', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($q) => $q->where('date', '<=', $filters['to']))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $term = '%'.$filters['q'].'%';
                $q->where(fn ($w) => $w->where('note', 'like', $term)
                    ->orWhereIn('account_id', Account::query()->where('name', 'like', $term)->orWhere('code', 'like', $term)->select('id')));
            });

        $rows = $base()->with(['account:id,name,code', 'fromCurrency:id,code', 'toCurrency:id,code', 'profitCurrency:id,code'])
            ->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();

        // without filters the totals are of today, so the page stays fast with years of exchanges
        $sumQuery = $filtered ? $base() : $base()->where('date', '>=', now()->toDateString());
        $summary = (clone $sumQuery)->selectRaw('COUNT(*) AS n, COALESCE(SUM(usd_amount), 0) AS usd')->first();
        $profits = (clone $sumQuery)->whereNotNull('profit_currency_id')->groupBy('profit_currency_id')
            ->selectRaw('profit_currency_id, SUM(profit) AS total')->pluck('total', 'profit_currency_id');

        return page('admin.exchanges.index', compact('rows', 'currencies', 'filters', 'filtered', 'summary', 'profits'), ['list', 'totals']);
    }

    // =========================
    // CREATE PAGE
    // =========================
    public function create()
    {
        return view('admin.exchanges.create', $this->formData());
    }

    private function formData(?Exchange $exchange = null): array
    {
        $trackId = null;
        if ($exchange && in_array($exchange->mode, ['track'], true)) {
            $skip = array_filter([$exchange->account_id, Account::where('account_sub_category_id', 3)->value('id'), Account::where('account_sub_category_id', 4)->value('id')]);
            $trackId = Transaction::where('linked_id', $exchange->linked_id)->whereNotIn('account_id', $skip)->value('account_id');
        }

        return [
            'exchange' => $exchange,
            'trackId' => $trackId,
            'accounts' => Account::whereIn('account_sub_category_id', [1, 2])->where('is_active', true)->orderBy('code')->get(['id', 'name', 'code', 'account_sub_category_id']),
            'currencies' => Currency::where('is_active', true)->orderByRaw("CASE WHEN code = 'USD' THEN 0 ELSE 1 END")->orderBy('id')->get(),
            'cashAccount' => Account::where('account_sub_category_id', 3)->first(),
            'usdId' => Currency::where('code', 'USD')->value('id'),
        ];
    }

    public function data(Request $request)
    {
        $query = Exchange::with(['fromCurrency', 'toCurrency', 'profitCurrency']);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->mode) {
            $query->where('mode', $request->mode);
        }

        $summaryQuery = clone $query;

        $totalVolume = (clone $summaryQuery)->sum('usd_amount');
        $count = (clone $summaryQuery)->count();

        $totalProfits = (clone $summaryQuery)
            ->selectRaw('profit_currency_id, SUM(profit) as total_profit')
            ->whereNotNull('profit_currency_id')
            ->groupBy('profit_currency_id')
            ->get();

        $currencyCodes = Currency::pluck('code', 'id');

        $totalProfitText = $totalProfits->map(function ($row) use ($currencyCodes) {
            $code = $currencyCodes[$row->profit_currency_id] ?? 'UNK';
            return format_amount($row->total_profit, 2) . ' ' . $code;
        })->implode(' | ');

        return datatables()->of($query)
            ->addIndexColumn()

            ->addColumn('pair', function ($row) {
                return "<strong>{$row->fromCurrency->code}</strong>
                <i class='bi bi-arrow-right text-muted mx-1'></i>
                <strong>{$row->toCurrency->code}</strong>";
            })

            ->addColumn('profit_display', function ($row) {
                $color = $row->profit >= 0 ? 'text-success' : 'text-danger';
                $code = $row->profitCurrency->code ?? '';

                return "<span class='amount-mono {$color}'>"
                    . ($row->profit >= 0 ? '+' : '')
                    . number_format($row->profit, 2)
                    . " {$code}</span>";
            })

            ->addColumn('from_amount_display', function ($row) {
                return format_amount($row->from_amount, 0) . ' ' . $row->fromCurrency->code;
            })

            ->addColumn('to_amount_display', function ($row) {
                return format_amount($row->to_amount, 0) . ' ' . $row->toCurrency->code;
            })

            ->addColumn('rate_display', function ($row) {
                return format_amount($row->rate, 2);
            })

            ->addColumn('actions', function ($row) {
                return view('admin.exchanges.actions', compact('row'));
            })

            ->with([
                'totalProfit' => $totalProfitText ?: '0',
                'totalVolume' => format_amount($totalVolume, 0),
                'totalCount'  => $count,
            ])

            ->rawColumns([
                'pair',
                'profit_display',
                'actions',
                'from_amount_display',
                'to_amount_display',
                'rate_display',
            ])

            ->make(true);
    }

    public function show(Request $request, Exchange $exchange)
    {
        $exchange->load(['fromCurrency', 'toCurrency', 'account', 'profitCurrency']);
        if ($request->expectsJson()) {
            return response()->json($exchange);
        }
        $lines = Transaction::query()->with(['account:id,name,code', 'currency:id,code'])
            ->where('linked_id', $exchange->linked_id)->orderBy('id')->get();
        $creator = \App\Models\User::find($exchange->created_by);

        return view('admin.exchanges.show', compact('exchange', 'lines', 'creator'));
    }

    public function edit(Exchange $exchange)
    {
        return view('admin.exchanges.create', $this->formData($exchange));
    }

    // public function update(Request $request, Exchange $exchange)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'mode' => 'required|in:cash,customer',
    //         'from_currency_id' => 'required',
    //         'to_currency_id' => 'required|different:from_currency_id',
    //         'from_amount' => 'required|numeric|min:0.0001',
    //     ]);

    //     DB::transaction(function () use ($request, $exchange) {

    //         Transaction::where('linked_id', $exchange->linked_id)->get()->each->delete(); // one by one, so the balances follow

    //         $operation = $request->operation_type ?? 'multiply';

    //         $rate = $this->resolveSmartRate(
    //             $request->from_currency_id,
    //             $request->to_currency_id,
    //             null,
    //             $request->rate
    //         );

    //         if (!$rate) {
    //             throw new \Exception('Rate not available');
    //         }

    //         $fromAmount = (float) $request->from_amount;

    //         $toAmount = ($operation === 'multiply')
    //             ? $fromAmount * $rate
    //             : $fromAmount / $rate;

    //         [$usdAmount, $baseRate, $profit, $profitCurrencyId, $profitUsd] = $this->calculateExactCurrencyProfit(
    //             $request->from_currency_id,
    //             $request->to_currency_id,
    //             $fromAmount,
    //             $toAmount,
    //             $rate
    //         );

    //         $cashId = Account::where('account_sub_category_id', 3)->value('id');

    //         $accountIdForProfit = ($request->mode == 'cash')
    //             ? $cashId
    //             : $request->account_id;

    //         [$profit, $profitUsd] = $this->adjustProfitByAccountPerspective(
    //             $accountIdForProfit,
    //             $profit,
    //             $profitUsd
    //         );

    //         $exchange->update([
    //             'date' => $request->date,
    //             'mode' => $request->mode,
    //             'account_id' => $request->account_id,
    //             'from_currency_id' => $request->from_currency_id,
    //             'to_currency_id' => $request->to_currency_id,
    //             'from_amount' => $fromAmount,
    //             'to_amount' => $toAmount,
    //             'rate' => $rate,
    //             'base_rate' => $baseRate,
    //             'usd_amount' => $usdAmount,
    //             'profit' => $profit,
    //             'profit_currency_id' => $profitCurrencyId,
    //             'note' => $request->note,
    //         ]);

    //         $accountId = ($request->mode == 'cash') ? $cashId : $request->account_id;

    //         $fromCurrencyCode = Currency::where('id', $request->from_currency_id)->value('code');
    //         $toCurrencyCode = Currency::where('id', $request->to_currency_id)->value('code');
    //         $profitCurrencyCode = Currency::where('id', $profitCurrencyId)->value('code');

    //         $note = ($request->note !== null && $request->note !== '')
    //             ? $request->note
    //             : "{$fromCurrencyCode} {$fromAmount} exchanged to {$toCurrencyCode} {$toAmount} at rate {$rate}. Profit/Loss = {$profit} {$profitCurrencyCode}";

    //         if ($request->mode == 'cash') {
    //             $this->createTx($exchange->linked_id, $cashId, $request->from_currency_id, $fromAmount, 'debit', $exchange->id, true, true, $note);
    //             $this->createTx($exchange->linked_id, $cashId, $request->to_currency_id, $toAmount, 'credit', $exchange->id, true, true, $note);
    //         } else {
    //             $this->createTx($exchange->linked_id, $accountId, $request->from_currency_id, $fromAmount, 'debit', $exchange->id, true, true, $note);
    //             $this->createTx($exchange->linked_id, $cashId, $request->from_currency_id, $fromAmount, 'credit', $exchange->id, true, true, $note);

    //             $this->createTx($exchange->linked_id, $cashId, $request->to_currency_id, $toAmount, 'debit', $exchange->id, true, true, $note);
    //             $this->createTx($exchange->linked_id, $accountId, $request->to_currency_id, $toAmount, 'credit', $exchange->id, true, true, $note);
    //         }

    //         $profitAccountId = Account::where('account_sub_category_id', 4)->value('id');

    //         if ($profitAccountId && $profitCurrencyId && $profit != 0) {
    //             $direction = $profit > 0 ? 'credit' : 'debit';
    //             $amount = abs($profit);

    //             if ($amount > 0.000001) {
    //                 $this->createTx(
    //                     $exchange->linked_id,
    //                     $profitAccountId,
    //                     $profitCurrencyId,
    //                     $amount,
    //                     $direction,
    //                     $exchange->id,
    //                     false,
    //                     false,
    //                     $note,
    //                     'exchange_profit'
    //                 );
    //             }
    //         }
    //     });

    //     return response()->json(['message' => 'Exchange updated successfully']);
    // }

    public function destroy(Exchange $exchange)
    {
        try {

            $fromCode = $exchange->fromCurrency->code ?? '';
            $toCode   = $exchange->toCurrency->code ?? '';

            DB::transaction(function () use ($exchange) {

                \App\Models\Transaction::where('linked_id', $exchange->linked_id)->get()->each->delete(); // one by one, so the balances follow

                $exchange->delete();
            });

            logActivity(
                $exchange,
                "معامله تبادله حذف شد: {$fromCode} → {$toCode}",
                [
                    'exchange_id' => $exchange->id,
                    'from_currency_id' => $exchange->from_currency_id,
                    'to_currency_id' => $exchange->to_currency_id,
                    'from_amount' => $exchange->from_amount,
                    'to_amount' => $exchange->to_amount,
                    'rate' => $exchange->rate,
                    'profit' => $exchange->profit
                ],
                'deleted'
            );

            return done(__('exchanges.deleted'), url()->previous() === route('admin.exchanges.show', $exchange->id) ? route('admin.exchanges.index') : null);
        } catch (\Exception $e) {
            report($e);

            return done(__('exchanges.delete_error'), null, false, 500);
        }
    }

    public function balances(Request $request)
    {
        $rows = Transaction::selectRaw("
                currency_id,
                SUM(CASE WHEN transaction_type='credit' THEN amount ELSE 0 END) -
                SUM(CASE WHEN transaction_type='debit' THEN amount ELSE 0 END) as balance
            ")
            ->where('account_id', $request->account_id)
            ->where('is_cash', 1)
            ->groupBy('currency_id')
            ->get();

        $currencies = Currency::pluck('code', 'id');

        $balances = [];
        foreach ($rows as $r) {
            $balances[$currencies[$r->currency_id] ?? 'UNK'] = [
                'balance' => (float)$r->balance
            ];
        }

        return response()->json(['balances' => $balances]);
    }

    public function getSmartRate(Request $request)
    {
        $from = (int) $request->from_currency_id;
        $to   = (int) $request->to_currency_id;

        if (!$from || !$to || $from === $to) {
            return response()->json([
                'rate' => null,
                'base_rate' => null,
            ]);
        }

        $avgRate = $this->resolveSmartRate($from, $to, null, null);

        $baseRate = $this->getPairBaseRate($from, $to);

        return response()->json([
            'rate' => $avgRate,
            'base_rate' => $baseRate,
        ]);
    }

    private function resolveSmartRate($fromCurrency, $toCurrency, $type = null, $manualRate = null)
    {
        if (!empty($manualRate) && (float) $manualRate > 0) {
            return (float) $manualRate;
        }

        $fromCurrency = (int) $fromCurrency;
        $toCurrency   = (int) $toCurrency;

        if (!$fromCurrency || !$toCurrency || $fromCurrency === $toCurrency) {
            return null;
        }

        $today = now()->toDateString();

        $todayExchanges = Exchange::whereDate('date', $today)
            ->where('from_currency_id', $fromCurrency)
            ->where('to_currency_id', $toCurrency)
            ->get();

        if ($todayExchanges->count() > 0) {
            $totalFrom = $todayExchanges->sum('from_amount');
            $totalTo   = $todayExchanges->sum('to_amount');

            if ($totalFrom > 0) {
                return $totalTo / $totalFrom;
            }
        }

        $todayRates = ExchangeRate::whereDate('date', $today)
            ->where('from_currency_id', $fromCurrency)
            ->where('to_currency_id', $toCurrency)
            ->get();

        if ($todayRates->count() > 0) {
            return $todayRates->count() > 1
                ? (float) $todayRates->avg('rate')
                : (float) $todayRates->first()->rate;
        }

        $inverseTodayRates = ExchangeRate::whereDate('date', $today)
            ->where('from_currency_id', $toCurrency)
            ->where('to_currency_id', $fromCurrency)
            ->get();

        if ($inverseTodayRates->count() > 0) {
            $inverseRate = $inverseTodayRates->count() > 1
                ? (float) $inverseTodayRates->avg('rate')
                : (float) $inverseTodayRates->first()->rate;

            return $inverseRate > 0 ? (1 / $inverseRate) : null;
        }

        return $this->getPairBaseRate($fromCurrency, $toCurrency);
    }

    private function getPairBaseRate($fromCurrencyId, $toCurrencyId)
    {
        $fromCurrencyId = (int) $fromCurrencyId;
        $toCurrencyId   = (int) $toCurrencyId;

        if (!$fromCurrencyId || !$toCurrencyId || $fromCurrencyId === $toCurrencyId) {
            return null;
        }

        $directRate = ExchangeRate::where('from_currency_id', $fromCurrencyId)
            ->where('to_currency_id', $toCurrencyId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->value('rate');

        if ($directRate && (float) $directRate > 0) {
            return (float) $directRate;
        }

        $inverseRate = ExchangeRate::where('from_currency_id', $toCurrencyId)
            ->where('to_currency_id', $fromCurrencyId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->value('rate');

        if ($inverseRate && (float) $inverseRate > 0) {
            return 1 / (float) $inverseRate;
        }

        $usdId = Currency::where('code', 'USD')->value('id');

        if (!$usdId) {
            return null;
        }

        if ($fromCurrencyId === (int) $usdId) {
            return $this->getCurrencyRateAgainstUsd($toCurrencyId);
        }

        if ($toCurrencyId === (int) $usdId) {
            $fromAgainstUsd = $this->getCurrencyRateAgainstUsd($fromCurrencyId);
            return $fromAgainstUsd && $fromAgainstUsd > 0 ? 1 / $fromAgainstUsd : null;
        }

        $fromAgainstUsd = $this->getCurrencyRateAgainstUsd($fromCurrencyId);
        $toAgainstUsd   = $this->getCurrencyRateAgainstUsd($toCurrencyId);

        if (!$fromAgainstUsd || !$toAgainstUsd || $fromAgainstUsd <= 0 || $toAgainstUsd <= 0) {
            return null;
        }

        return (float) $toAgainstUsd / (float) $fromAgainstUsd;
    }

    private function getCurrencyRateAgainstUsd($currencyId)
    {
        $currencyId = (int) $currencyId;

        $usdId = Currency::where('code', 'USD')->value('id');

        if (!$usdId || !$currencyId) {
            return null;
        }

        if ($currencyId === (int) $usdId) {
            return 1;
        }

        $directRate = ExchangeRate::where('from_currency_id', $usdId)
            ->where('to_currency_id', $currencyId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->value('rate');

        if ($directRate && (float) $directRate > 0) {
            return (float) $directRate;
        }

        $inverseRate = ExchangeRate::where('from_currency_id', $currencyId)
            ->where('to_currency_id', $usdId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->value('rate');

        if ($inverseRate && (float) $inverseRate > 0) {
            return 1 / (float) $inverseRate;
        }

        return null;
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'mode' => 'required|in:cash,customer',
    //         'from_currency_id' => 'required',
    //         'to_currency_id' => 'required|different:from_currency_id',
    //         'from_amount' => 'required|numeric|min:0.0001',
    //     ]);

    //     $operation = $request->operation_type ?? 'multiply';

    //     $usdId = Currency::where('code', 'USD')->value('id');

    //     $type = $request->from_currency_id == $usdId ? 'sell' : 'purchase';

    //     $rate = $this->resolveSmartRate(
    //         $request->from_currency_id,
    //         $request->to_currency_id,
    //         $type,
    //         $request->rate
    //     );

    //     if (!$rate) {
    //         return response()->json(['message' => 'Rate not available'], 422);
    //     }

    //     $fromAmount = (float) $request->from_amount;

    //     $toAmount = ($operation === 'multiply')
    //         ? $fromAmount * $rate
    //         : $fromAmount / $rate;

    //     [$usdAmount, $baseRate, $profit, $profitCurrencyId, $profitUsd] = $this->calculateExactCurrencyProfit(
    //         $request->from_currency_id,
    //         $request->to_currency_id,
    //         $fromAmount,
    //         $toAmount,
    //         $rate
    //     );

    //     $cashId = Account::where('account_sub_category_id', 3)->value('id');

    //     $accountIdForProfit = ($request->mode == 'cash')
    //         ? $cashId
    //         : $request->account_id;

    //     [$profit, $profitUsd] = $this->adjustProfitByAccountPerspective(
    //         $accountIdForProfit,
    //         $profit,
    //         $profitUsd
    //     );

    //     $linkedId = (string) Str::uuid();

    //     $profitAccountId = Account::where('account_sub_category_id', 4)->value('id');

    //     DB::transaction(function () use (
    //         $request,
    //         $cashId,
    //         $linkedId,
    //         $fromAmount,
    //         $toAmount,
    //         $rate,
    //         $usdAmount,
    //         $baseRate,
    //         $profit,
    //         $profitCurrencyId,
    //         $profitUsd,
    //         $profitAccountId,
    //         $type
    //     ) {
    //         $accountId = ($request->mode == 'cash') ? $cashId : $request->account_id;
    //         $exchange = Exchange::create([
    //             'date' => $request->date,
    //             'type' => $type,
    //             'mode' => $request->mode,
    //             'account_id' => $accountId,
    //             'from_currency_id' => $request->from_currency_id,
    //             'to_currency_id' => $request->to_currency_id,
    //             'from_amount' => $fromAmount,
    //             'to_amount' => $toAmount,
    //             'rate' => $rate,
    //             'base_rate' => $baseRate,
    //             'usd_amount' => $usdAmount,
    //             'profit' => $profit,
    //             'profit_currency_id' => $profitCurrencyId,
    //             'note' => $request->note,
    //             'linked_id' => $linkedId,
    //             'created_by' => Auth::id(),
    //         ]);

    //         $fromCurrencyCode = Currency::where('id', $request->from_currency_id)->value('code');
    //         $toCurrencyCode = Currency::where('id', $request->to_currency_id)->value('code');
    //         $profitCurrencyCode = Currency::where('id', $profitCurrencyId)->value('code');

    //         $note = ($request->note !== null && $request->note !== '')
    //             ? $request->note
    //             : "{$fromCurrencyCode} {$fromAmount} exchanged to {$toCurrencyCode} {$toAmount} at rate {$rate}. Profit/Loss = {$profit} {$profitCurrencyCode}";

    //         if ($request->mode == 'cash') {
    //             $this->createTx($linkedId, $cashId, $request->from_currency_id, $fromAmount, 'debit', $exchange->id, true, true, $note, null);
    //             $this->createTx($linkedId, $cashId, $request->to_currency_id, $toAmount, 'credit', $exchange->id, true, true, $note, null);
    //         } else {
    //             $this->createTx($linkedId, $accountId, $request->from_currency_id, $fromAmount, 'debit', $exchange->id, true, true, $note);
    //             $this->createTx($linkedId, $cashId, $request->from_currency_id, $fromAmount, 'credit', $exchange->id, true, true, $note, null);

    //             $this->createTx($linkedId, $cashId, $request->to_currency_id, $toAmount, 'debit', $exchange->id, true, true, $note, null);
    //             $this->createTx($linkedId, $accountId, $request->to_currency_id, $toAmount, 'credit', $exchange->id, true, true, $note, null);
    //         }

    //         if ($profitAccountId && $profitCurrencyId && $profit != 0) {
    //             $direction = $profit > 0 ? 'credit' : 'debit';
    //             $amount = abs($profit);

    //             if ($amount > 0.000001) {
    //                 $this->createTx(
    //                     $linkedId,
    //                     $profitAccountId,
    //                     $profitCurrencyId,
    //                     $amount,
    //                     $direction,
    //                     $exchange->id,
    //                     false,
    //                     false,
    //                     $note,
    //                     'exchange_profit'
    //                 );
    //             }
    //         }

    //         $fromAmountFormatted = format_amount($fromAmount, 0);
    //         $toAmountFormatted = format_amount($toAmount, 0);

    //         logActivity(
    //             $exchange,
    //             "معامله تبادله ثبت شد: ({$fromAmountFormatted} {$exchange->fromCurrency->code}) → ({$toAmountFormatted} {$exchange->toCurrency->code}) | نرخ: {$rate} | حساب: " . ($request->mode == 'cash' ? 'Cash' : optional(Account::find($accountId))->name) . " | توضیح: " . ($request->note ?? '-'),
    //             [
    //                 'exchange_id' => $exchange->id,
    //                 'account_id' => $accountId,
    //                 'account_name' => ($request->mode == 'cash' ? 'Cash' : optional(Account::find($accountId))->name),
    //                 'from_currency' => $exchange->fromCurrency->code,
    //                 'to_currency' => $exchange->toCurrency->code,
    //                 'from_amount' => $fromAmount,
    //                 'to_amount' => $toAmount,
    //                 'rate' => $rate,
    //                 'profit' => $profit,
    //                 'profit_currency_id' => $profitCurrencyId,
    //                 'profit_usd_equivalent' => $profitUsd,
    //                 'description' => $request->note,
    //             ],
    //             'created'
    //         );
    //     });

    //     return response()->json(['message' => 'Exchange Processed Successfully']);
    // }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'mode' => 'required|in:cash,customer,saraf',
            'account_id' => 'required_unless:mode,cash|nullable|exists:accounts,id',
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id|different:from_currency_id',
            'from_amount' => 'required|numeric|min:0.0001',
            'operation_type' => 'nullable|in:multiply,divide',
            'exchange_mode' => 'nullable|in:treasure,no_track,track',
            'track_account_id' => 'nullable|exists:accounts,id',
            'rate' => 'nullable|numeric|min:0.0000001',
        ]);

        $operation = $request->operation_type ?? 'multiply';

        $usdId = Currency::where('code', 'USD')->value('id');
        $type = ((int) $request->from_currency_id === (int) $usdId) ? 'sell' : 'purchase';

        $rate = $this->resolveSmartRate(
            $request->from_currency_id,
            $request->to_currency_id,
            $type,
            $request->rate
        );

        if (!$rate) {
            return done(__('exchanges.rate_unavailable'), null, false, 422);
        }

        $fromAmount = (float) $request->from_amount;

        $toAmount = ($operation === 'multiply')
            ? $fromAmount * $rate
            : $fromAmount / $rate;

        [$usdAmount, $baseRate, $profit, $profitCurrencyId, $profitUsd] =
            $this->calculateExactCurrencyProfit(
                $request->from_currency_id,
                $request->to_currency_id,
                $fromAmount,
                $toAmount,
                $rate
            );

        $cashId = Account::where('account_sub_category_id', 3)->value('id');

        if (!$cashId) {
            return done(__('exchanges.cash_unconfigured'), null, false, 500);
        }

        $accountId = $request->mode === 'cash'
            ? $cashId
            : (int) $request->account_id;

        [$profit, $profitUsd] = $this->adjustProfitByAccountPerspective(
            $accountId,
            $profit,
            $profitUsd
        );

        $linkedId = (string) Str::uuid();
        $profitAccountId = Account::where('account_sub_category_id', 4)->value('id');

        DB::transaction(function () use (
            $request,
            $linkedId,
            $accountId,
            $cashId,
            $fromAmount,
            $toAmount,
            $rate,
            $usdAmount,
            $baseRate,
            $profit,
            $profitCurrencyId,
            $profitUsd,
            $profitAccountId,
            $type
        ) {
            $postingMode = $this->resolveExchangePostingMode($request, $accountId, $cashId);

            $savedMode = match ($postingMode) {
                'cash' => 'cash',
                'legacy' => $request->mode,
                default => $postingMode,
            };

            $exchange = Exchange::create([
                'date' => $request->date,
                'type' => $type,
                'mode' => $savedMode,
                'account_id' => $accountId,
                'from_currency_id' => $request->from_currency_id,
                'to_currency_id' => $request->to_currency_id,
                'from_amount' => $fromAmount,
                'to_amount' => $toAmount,
                'rate' => $rate,
                'operation_type' => ($request->operation_type === 'divide') ? 'divide' : 'multiply',
                'base_rate' => $baseRate,
                'usd_amount' => $usdAmount,
                'profit' => $profit,
                'profit_currency_id' => $profitCurrencyId,
                'note' => $request->note,
                'linked_id' => $linkedId,
                'created_by' => Auth::id(),
            ]);

            $customNote = $this->formatExchangeNote(
                $fromAmount,
                Currency::find($request->from_currency_id),
                $toAmount,
                Currency::find($request->to_currency_id),
                $rate,
                $request->date
            );

            $note = $request->note ?: $customNote;

            $this->postExchangeTransactions(
                $request,
                $linkedId,
                $exchange->id,
                $postingMode,
                $accountId,
                $cashId,
                $fromAmount,
                $toAmount,
                $note
            );

            if ($profitAccountId && $profitCurrencyId && $profit != 0) {
                $direction = $profit > 0 ? 'credit' : 'debit';
                $amount = abs($profit);

                if ($amount > 0.000001) {
                    $this->createTx(
                        $linkedId,
                        $profitAccountId,
                        $profitCurrencyId,
                        $amount,
                        $direction,
                        $exchange->id,
                        false,
                        false,
                        $note,
                        'exchange_profit'
                    );
                }
            }

            logActivity(
                $exchange,
                "معامله تبادله ثبت شد",
                [
                    'exchange_id' => $exchange->id,
                    'account_id' => $accountId,
                    'mode' => $savedMode,
                    'from_currency_id' => $request->from_currency_id,
                    'to_currency_id' => $request->to_currency_id,
                    'from_amount' => $fromAmount,
                    'to_amount' => $toAmount,
                    'rate' => $rate,
                    'profit' => $profit,
                    'profit_currency_id' => $profitCurrencyId,
                    'profit_usd_equivalent' => $profitUsd,
                ],
                'created'
            );
        });

        return done(__('exchanges.processed'), $request->boolean('again') ? route('admin.exchanges.create') : route('admin.exchanges.index'));
    }

    public function update(Request $request, Exchange $exchange)
{
    $request->validate([
        'date' => 'required|date',
        'mode' => 'required|in:cash,customer,saraf',
        'account_id' => 'required_unless:mode,cash|nullable|exists:accounts,id',
        'from_currency_id' => 'required|exists:currencies,id',
        'to_currency_id' => 'required|exists:currencies,id|different:from_currency_id',
        'from_amount' => 'required|numeric|min:0.0001',
        'operation_type' => 'nullable|in:multiply,divide',
        'exchange_mode' => 'nullable|in:treasure,no_track,track',
        'track_account_id' => 'nullable|exists:accounts,id',
        'rate' => 'nullable|numeric|min:0.0000001',
    ]);

    $operation = $request->operation_type ?? 'multiply';

    $rate = $this->resolveSmartRate(
        $request->from_currency_id,
        $request->to_currency_id,
        null,
        $request->rate
    );

    if (!$rate) {
        return done(__('exchanges.rate_unavailable'), null, false, 422);
    }

    $fromAmount = (float) $request->from_amount;

    $toAmount = ($operation === 'multiply')
        ? $fromAmount * $rate
        : $fromAmount / $rate;

    [$usdAmount, $baseRate, $profit, $profitCurrencyId, $profitUsd] =
        $this->calculateExactCurrencyProfit(
            $request->from_currency_id,
            $request->to_currency_id,
            $fromAmount,
            $toAmount,
            $rate
        );

    $cashId = Account::where('account_sub_category_id', 3)->value('id');

    if (!$cashId) {
        return done(__('exchanges.cash_unconfigured'), null, false, 500);
    }

    $accountId = $request->mode === 'cash'
        ? $cashId
        : (int) $request->account_id;

    [$profit, $profitUsd] = $this->adjustProfitByAccountPerspective(
        $accountId,
        $profit,
        $profitUsd
    );

    $profitAccountId = Account::where('account_sub_category_id', 4)->value('id');

    DB::transaction(function () use (
        $request,
        $exchange,
        $accountId,
        $cashId,
        $fromAmount,
        $toAmount,
        $rate,
        $usdAmount,
        $baseRate,
        $profit,
        $profitCurrencyId,
        $profitUsd,
        $profitAccountId
    ) {
        $linkedId = $exchange->linked_id ?: (string) Str::uuid();

        Transaction::where('linked_id', $linkedId)->get()->each->delete(); // one by one, so the balances follow

        $postingMode = $this->resolveExchangePostingMode($request, $accountId, $cashId);

        $savedMode = match ($postingMode) {
            'cash' => 'cash',
            'legacy' => $request->mode,
            default => $postingMode,
        };

        $exchange->update([
            'date' => $request->date,
            'mode' => $savedMode,
            'account_id' => $accountId,
            'from_currency_id' => $request->from_currency_id,
            'to_currency_id' => $request->to_currency_id,
            'from_amount' => $fromAmount,
            'to_amount' => $toAmount,
            'rate' => $rate,
            'operation_type' => ($request->operation_type === 'divide') ? 'divide' : 'multiply',
            'base_rate' => $baseRate,
            'usd_amount' => $usdAmount,
            'profit' => $profit,
            'profit_currency_id' => $profitCurrencyId,
            'note' => $request->note,
            'linked_id' => $linkedId,
        ]);

        $customNote = $this->formatExchangeNote(
            $fromAmount,
            Currency::find($request->from_currency_id),
            $toAmount,
            Currency::find($request->to_currency_id),
            $rate,
            $request->date
        );

        $note = $request->note ?: $customNote;

        $this->postExchangeTransactions(
            $request,
            $linkedId,
            $exchange->id,
            $postingMode,
            $accountId,
            $cashId,
            $fromAmount,
            $toAmount,
            $note
        );

        if ($profitAccountId && $profitCurrencyId && $profit != 0) {
            $direction = $profit > 0 ? 'credit' : 'debit';
            $amount = abs($profit);

            if ($amount > 0.000001) {
                $this->createTx(
                    $linkedId,
                    $profitAccountId,
                    $profitCurrencyId,
                    $amount,
                    $direction,
                    $exchange->id,
                    false,
                    false,
                    $note,
                    'exchange_profit'
                );
            }
        }

        logActivity(
            $exchange,
            "معامله تبادله بروزرسانی شد",
            [
                'exchange_id' => $exchange->id,
                'account_id' => $accountId,
                'mode' => $savedMode,
                'from_currency_id' => $request->from_currency_id,
                'to_currency_id' => $request->to_currency_id,
                'from_amount' => $fromAmount,
                'to_amount' => $toAmount,
                'rate' => $rate,
                'profit' => $profit,
                'profit_currency_id' => $profitCurrencyId,
                'profit_usd_equivalent' => $profitUsd,
            ],
            'updated'
        );
    });

    return done(__('exchanges.updated'), route('admin.exchanges.show', $exchange->id));
}

private function resolveExchangePostingMode(Request $request, int $accountId, int $cashId): string
{
    /*
    |--------------------------------------------------------------------------
    | Normal exchange page compatibility
    |--------------------------------------------------------------------------
    | account_id = 1 keeps your old working logic.
    | Other accounts use the same transaction style as modalStore/modalUpdate.
    |--------------------------------------------------------------------------
    */

    if ($request->mode === 'cash' || $accountId === $cashId) {
        return 'cash';
    }

    if ($accountId === 1 && !$request->filled('exchange_mode')) {
        return 'legacy';
    }

    return $request->exchange_mode ?: 'no_track';
}

private function postExchangeTransactions(
    Request $request,
    string $linkedId,
    int $exchangeId,
    string $postingMode,
    int $accountId,
    int $cashId,
    float $fromAmount,
    float $toAmount,
    string $note
): void {
    /*
    |--------------------------------------------------------------------------
    | Cash / office mode
    |--------------------------------------------------------------------------
    */
    if ($postingMode === 'cash') {
        $this->createTx(
            $linkedId,
            $cashId,
            $request->from_currency_id,
            $fromAmount,
            'debit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        $this->createTx(
            $linkedId,
            $cashId,
            $request->to_currency_id,
            $toAmount,
            'credit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Old working logic for account_id = 1
    |--------------------------------------------------------------------------
    */
    if ($postingMode === 'legacy') {
        $this->createTx(
            $linkedId,
            $accountId,
            $request->from_currency_id,
            $fromAmount,
            'debit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        $this->createTx(
            $linkedId,
            $cashId,
            $request->from_currency_id,
            $fromAmount,
            'credit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        $this->createTx(
            $linkedId,
            $cashId,
            $request->to_currency_id,
            $toAmount,
            'debit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        $this->createTx(
            $linkedId,
            $accountId,
            $request->to_currency_id,
            $toAmount,
            'credit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Same logic as modalStore/modalUpdate: treasure
    |--------------------------------------------------------------------------
    */
    if ($postingMode === 'treasure') {
        $this->createTx(
            $linkedId,
            $accountId,
            $request->from_currency_id,
            $fromAmount,
            'debit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        $this->createTx(
            $linkedId,
            $cashId,
            $request->from_currency_id,
            $fromAmount,
            'credit',
            $exchangeId,
            true,
            false,
            $note,
            null
        );

        $this->createTx(
            $linkedId,
            $accountId,
            $request->to_currency_id,
            $toAmount,
            'credit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        $this->createTx(
            $linkedId,
            $cashId,
            $request->to_currency_id,
            $toAmount,
            'debit',
            $exchangeId,
            true,
            false,
            $note,
            null
        );

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Same logic as modalStore/modalUpdate: no_track
    |--------------------------------------------------------------------------
    */
    if ($postingMode === 'no_track') {
        $this->createTx(
            $linkedId,
            $accountId,
            $request->from_currency_id,
            $fromAmount,
            'debit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        $this->createTx(
            $linkedId,
            $accountId,
            $request->to_currency_id,
            $toAmount,
            'credit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Same logic as modalStore/modalUpdate: track
    |--------------------------------------------------------------------------
    */
    if ($postingMode === 'track') {
        $trackId = (int) $request->track_account_id;

        if (!$trackId) {
            throw new \Exception('Track account required');
        }

        $this->createTx(
            $linkedId,
            $accountId,
            $request->from_currency_id,
            $fromAmount,
            'debit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        $this->createTx(
            $linkedId,
            $trackId,
            $request->from_currency_id,
            $fromAmount,
            'credit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        $this->createTx(
            $linkedId,
            $accountId,
            $request->to_currency_id,
            $toAmount,
            'credit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        $this->createTx(
            $linkedId,
            $trackId,
            $request->to_currency_id,
            $toAmount,
            'debit',
            $exchangeId,
            true,
            true,
            $note,
            null
        );

        return;
    }

    throw new \Exception('Invalid exchange posting mode');
}

    private function createTx($linkedId, $accId, $curId, $amt, $dir, $rowId, $isCash, $isVisible, $note, $category = null)
    {
        Transaction::create([
            'linked_id' => $linkedId,
            'account_id' => $accId,
            'currency_id' => $curId,
            'amount' => $amt,
            'payment_currency_id' => $curId,
            'payment_amount' => $amt,
            'actual_amount' => $amt,
            'actual_currency_id' => $curId,
            'note' => $note,
            'transaction_type' => $dir,
            'table_name' => 'exchanges',
            'table_row_id' => $rowId,
            'is_cash' => $isCash,
            'is_visible' => $isVisible,
            'category' => $category,
            'created_by' => Auth::id(),
        ]);
    }

    private function getFallbackRate($usdId, $targetCurrencyId)
    {
        // 1. Try today
        $todayRate = ExchangeRate::where('from_currency_id', $usdId)
            ->where('to_currency_id', $targetCurrencyId)
            ->whereDate('date', now())
            ->latest()
            ->value('rate');

        if ($todayRate) return $todayRate;

        // 2. Try yesterday
        $yesterdayRate = ExchangeRate::where('from_currency_id', $usdId)
            ->where('to_currency_id', $targetCurrencyId)
            ->whereDate('date', now()->subDay())
            ->latest()
            ->value('rate');

        if ($yesterdayRate) return $yesterdayRate;

        // 3. Fallback: last 7 days average
        $avg = ExchangeRate::where('from_currency_id', $usdId)
            ->where('to_currency_id', $targetCurrencyId)
            ->whereDate('date', '>=', now()->subDays(7))
            ->avg('rate');

        return $avg ?: null;
    }

    public function modalStore(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id|different:from_currency_id',
            'from_amount' => 'required|numeric|min:0.0001',
            'operation_type' => 'required|in:multiply,divide',
            'exchange_mode' => 'required|in:treasure,no_track,track',
            'track_account_id' => 'nullable|exists:accounts,id',
            'rate' => 'nullable|numeric|min:0.0000001',
        ], [
            'from_currency_id.required' => 'Please select sold currency.',
            'to_currency_id.required' => 'Please select purchased currency.',
            'to_currency_id.different' => 'Sold and purchased currency cannot be the same.',
            'from_amount.required' => 'Please enter sold amount.',
            'exchange_mode.required' => 'Please select exchange mode.',
            'rate.required' => 'Please enter exchange rate.',
        ]);

        $operation = $request->operation_type ?? 'multiply';

        $rate = $this->resolveSmartRate(
            $request->from_currency_id,
            $request->to_currency_id,
            null,
            $request->rate
        );

        if (!$rate) {
            return response()->json(['message' => __('exchanges.rate_unavailable')], 422);
        }

        $fromAmount = (float) $request->from_amount;

        $toAmount = ($operation === 'multiply')
            ? $fromAmount * $rate
            : $fromAmount / $rate;

        [$usdAmount, $baseRate, $profit, $profitCurrencyId, $profitUsd] = $this->calculateExactCurrencyProfit(
            $request->from_currency_id,
            $request->to_currency_id,
            $fromAmount,
            $toAmount,
            $rate
        );

        [$profit, $profitUsd] = $this->adjustProfitByAccountPerspective(
            $request->account_id,
            $profit,
            $profitUsd
        );

        $cashId = Account::where('account_sub_category_id', 3)->value('id');

        if (!$cashId) {
            return response()->json(['message' => __('exchanges.cash_unconfigured')], 500);
        }

        $linkedId = (string) Str::uuid();
        $mode = $request->exchange_mode;
        $trackId = $request->track_account_id;

        $profitAccountId = Account::where('account_sub_category_id', 4)->value('id');

        DB::transaction(function () use (
            $request,
            $linkedId,
            $fromAmount,
            $toAmount,
            $rate,
            $usdAmount,
            $baseRate,
            $profit,
            $profitCurrencyId,
            $profitUsd,
            $cashId,
            $mode,
            $trackId,
            $profitAccountId
        ) {
            $exchange = Exchange::create([
                'date' => $request->date,
                'mode' => $mode,
                'account_id' => $request->account_id,
                'from_currency_id' => $request->from_currency_id,
                'to_currency_id' => $request->to_currency_id,
                'from_amount' => $fromAmount,
                'to_amount' => $toAmount,
                'rate' => $rate,
                'operation_type' => ($request->operation_type === 'divide') ? 'divide' : 'multiply',
                'base_rate' => $baseRate,
                'usd_amount' => $usdAmount,
                'profit' => $profit,
                'profit_currency_id' => $profitCurrencyId,
                'note' => $request->note,
                'linked_id' => $linkedId,
                'created_by' => Auth::id(),
            ]);

            $customNote = $this->formatExchangeNote(
                $fromAmount,
                Currency::find($request->from_currency_id),
                $toAmount,
                Currency::find($request->to_currency_id),
                $rate,
                $request->date
            );

            $note = $request->note ?: $customNote;

            if ($mode === 'treasure') {
                $this->createTx($linkedId, $request->account_id, $request->from_currency_id, $fromAmount, 'debit', $exchange->id, true, 1, $note, null);
                $this->createTx($linkedId, $cashId, $request->from_currency_id, $fromAmount, 'credit', $exchange->id, true, 0, $note, null);

                $this->createTx($linkedId, $request->account_id, $request->to_currency_id, $toAmount, 'credit', $exchange->id, true, 1, $note, null);
                $this->createTx($linkedId, $cashId, $request->to_currency_id, $toAmount, 'debit', $exchange->id, true, 0, $note, null);
            } elseif ($mode === 'no_track') {
                $this->createTx($linkedId, $request->account_id, $request->from_currency_id, $fromAmount, 'debit', $exchange->id, true, 1, $note, null);
                $this->createTx($linkedId, $request->account_id, $request->to_currency_id, $toAmount, 'credit', $exchange->id, true, 1, $note, null);
            } elseif ($mode === 'track') {
                if (!$trackId) {
                    throw new \Exception('Track account required');
                }

                $this->createTx($linkedId, $request->account_id, $request->from_currency_id, $fromAmount, 'debit', $exchange->id, true, 1, $note, null);
                $this->createTx($linkedId, $trackId, $request->from_currency_id, $fromAmount, 'credit', $exchange->id, true, 1, $note, null);

                $this->createTx($linkedId, $request->account_id, $request->to_currency_id, $toAmount, 'credit', $exchange->id, true, 1, $note, null);
                $this->createTx($linkedId, $trackId, $request->to_currency_id, $toAmount, 'debit', $exchange->id, true, 1, $note, null);
            }

            if ($profitAccountId && $profitCurrencyId && $profit != 0) {
                $direction = $profit > 0 ? 'credit' : 'debit';
                $amount = abs($profit);

                if ($amount > 0.000001) {
                    $this->createTx(
                        $linkedId,
                        $profitAccountId,
                        $profitCurrencyId,
                        $amount,
                        $direction,
                        $exchange->id,
                        false,
                        false,
                        $note,
                        'exchange_profit'
                    );
                }
            }
        });

        return response()->json(['message' => __('exchanges.processed')]);
    }

    public function modalUpdate(Request $request, Exchange $exchange)
    {
        $request->validate([
            'date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id|different:from_currency_id',
            'from_amount' => 'required|numeric|min:0.0001',
            'operation_type' => 'required|in:multiply,divide',
            'exchange_mode' => 'required|in:treasure,no_track,track',
            'track_account_id' => 'nullable|exists:accounts,id',
            'rate' => 'nullable|numeric|min:0.0000001',
        ]);

        $operation = $request->operation_type ?? 'multiply';

        $rate = $this->resolveSmartRate(
            $request->from_currency_id,
            $request->to_currency_id,
            null,
            $request->rate
        );

        if (!$rate) {
            return response()->json(['message' => __('exchanges.rate_unavailable')], 422);
        }

        $fromAmount = (float) $request->from_amount;

        $toAmount = ($operation === 'multiply')
            ? $fromAmount * $rate
            : $fromAmount / $rate;

        [$usdAmount, $baseRate, $profit, $profitCurrencyId, $profitUsd] = $this->calculateExactCurrencyProfit(
            $request->from_currency_id,
            $request->to_currency_id,
            $fromAmount,
            $toAmount,
            $rate
        );

        [$profit, $profitUsd] = $this->adjustProfitByAccountPerspective(
            $request->account_id,
            $profit,
            $profitUsd
        );

        $cashId = Account::where('account_sub_category_id', 3)->value('id');

        if (!$cashId) {
            abort(500, __('exchanges.cash_unconfigured'));
        }

        $mode = $request->exchange_mode;
        $trackId = $request->track_account_id;

        $profitAccountId = Account::where('account_sub_category_id', 4)->value('id');

        DB::transaction(function () use (
            $request,
            $exchange,
            $fromAmount,
            $toAmount,
            $rate,
            $usdAmount,
            $baseRate,
            $profit,
            $profitCurrencyId,
            $profitUsd,
            $cashId,
            $mode,
            $trackId,
            $profitAccountId
        ) {
            $linkedId = $exchange->linked_id ?: (string) Str::uuid();

            Transaction::where('linked_id', $linkedId)->get()->each->delete(); // one by one, so the balances follow

            $exchange->update([
                'date' => $request->date,
                'mode' => $mode,
                'account_id' => $request->account_id,
                'from_currency_id' => $request->from_currency_id,
                'to_currency_id' => $request->to_currency_id,
                'from_amount' => $fromAmount,
                'to_amount' => $toAmount,
                'rate' => $rate,
                'operation_type' => ($request->operation_type === 'divide') ? 'divide' : 'multiply',
                'base_rate' => $baseRate,
                'usd_amount' => $usdAmount,
                'profit' => $profit,
                'profit_currency_id' => $profitCurrencyId,
                'note' => $request->note,
                'linked_id' => $linkedId,
            ]);

            $customNote = $this->formatExchangeNote(
                $fromAmount,
                Currency::find($request->from_currency_id),
                $toAmount,
                Currency::find($request->to_currency_id),
                $rate,
                $request->date
            );

            $note = $request->note ?: $customNote;

            if ($mode === 'treasure') {
                $this->createTx($linkedId, $request->account_id, $request->from_currency_id, $fromAmount, 'debit', $exchange->id, true, 1, $note, null);
                $this->createTx($linkedId, $cashId, $request->from_currency_id, $fromAmount, 'credit', $exchange->id, true, 0, $note, null);

                $this->createTx($linkedId, $request->account_id, $request->to_currency_id, $toAmount, 'credit', $exchange->id, true, 1, $note, null);
                $this->createTx($linkedId, $cashId, $request->to_currency_id, $toAmount, 'debit', $exchange->id, true, 0, $note, null);
            }

            if ($mode === 'no_track') {
                $this->createTx($linkedId, $request->account_id, $request->from_currency_id, $fromAmount, 'debit', $exchange->id, true, 1, $note, null);
                $this->createTx($linkedId, $request->account_id, $request->to_currency_id, $toAmount, 'credit', $exchange->id, true, 1, $note, null);
            }

            if ($mode === 'track') {
                if (!$trackId) {
                    throw new \Exception('Track account required');
                }

                $this->createTx($linkedId, $request->account_id, $request->from_currency_id, $fromAmount, 'debit', $exchange->id, true, 1, $note, null);
                $this->createTx($linkedId, $trackId, $request->from_currency_id, $fromAmount, 'credit', $exchange->id, true, 1, $note, null);

                $this->createTx($linkedId, $request->account_id, $request->to_currency_id, $toAmount, 'credit', $exchange->id, true, 1, $note, null);
                $this->createTx($linkedId, $trackId, $request->to_currency_id, $toAmount, 'debit', $exchange->id, true, 1, $note, null);
            }

            if ($profitAccountId && $profitCurrencyId && $profit != 0) {
                $direction = $profit > 0 ? 'credit' : 'debit';
                $amount = abs($profit);

                if ($amount > 0.000001) {
                    $this->createTx(
                        $linkedId,
                        $profitAccountId,
                        $profitCurrencyId,
                        $amount,
                        $direction,
                        $exchange->id,
                        false,
                        false,
                        $note,
                        'exchange_profit'
                    );
                }
            }

            logActivity(
                $exchange,
                "معامله تبادله بروزرسانی شد",
                [
                    'exchange_id' => $exchange->id,
                    'mode' => $mode,
                    'from_currency_id' => $request->from_currency_id,
                    'to_currency_id' => $request->to_currency_id,
                    'from_amount' => $fromAmount,
                    'to_amount' => $toAmount,
                    'rate' => $rate,
                    'profit' => $profit,
                    'profit_currency_id' => $profitCurrencyId,
                    'profit_usd_equivalent' => $profitUsd,
                ],
                'updated'
            );
        });

        return response()->json([
            'message' => __('exchanges.updated'),
        ]);
    }

    private function adjustProfitByAccountPerspective($accountId, $profit, $profitUsd)
    {
        if ((int) $accountId !== 1) {
            $profit = $profit * -1;
            $profitUsd = $profitUsd * -1;
        }

        return [
            round($profit, 4),
            round($profitUsd, 4),
        ];
    }

    private function calculateExactCurrencyProfit($fromCurrencyId, $toCurrencyId, $fromAmount, $toAmount, $rate)
    {
        $fromCurrencyId = (int) $fromCurrencyId;
        $toCurrencyId   = (int) $toCurrencyId;

        $fromAmount = (float) $fromAmount;
        $toAmount   = (float) $toAmount;
        $rate       = (float) $rate;

        if (!$fromCurrencyId || !$toCurrencyId || $fromCurrencyId === $toCurrencyId) {
            return [0, null, 0, null, 0];
        }

        if ($fromAmount <= 0 || $toAmount <= 0 || $rate <= 0) {
            return [0, null, 0, null, 0];
        }

        /*
        |--------------------------------------------------------------------------
        | Universal base rate
        |--------------------------------------------------------------------------
        | This is always from_currency -> to_currency.
        |--------------------------------------------------------------------------
        */
        $baseRate = $this->getPairBaseRate($fromCurrencyId, $toCurrencyId);

        if (!$baseRate || $baseRate <= 0) {
            return [0, null, 0, $toCurrencyId, 0];
        }

        /*
        |--------------------------------------------------------------------------
        | Business / Office Perspective
        |--------------------------------------------------------------------------
        | from_currency_id = currency office SOLD
        | to_currency_id   = currency office RECEIVED / PURCHASED
        |
        | Profit/Loss = actual received - market expected received
        | Profit currency = received currency / to_currency_id
        |--------------------------------------------------------------------------
        */
        $marketToAmount = $fromAmount * $baseRate;
        $actualToAmount = $toAmount;

        $profitAmount = $actualToAmount - $marketToAmount;

        $profitCurrencyId = $toCurrencyId;

        /*
        |--------------------------------------------------------------------------
        | USD equivalent only for reports/volume.
        | The actual profit posting should NOT use this.
        |--------------------------------------------------------------------------
        */
        $usdAmount = $this->convertToUsd($fromCurrencyId, $fromAmount);

        $profitUsd = $this->convertToUsd($profitCurrencyId, $profitAmount);

        return [
            round($usdAmount, 4),
            round($baseRate, 8),
            round($profitAmount, 4),
            $profitCurrencyId,
            round($profitUsd, 4),
        ];
    }

    private function convertToUsd($currencyId, $amount)
    {
        $currencyId = (int) $currencyId;
        $amount     = (float) $amount;

        if (!$currencyId || $amount == 0) {
            return 0;
        }

        $usdId = Currency::where('code', 'USD')->value('id');

        if (!$usdId) {
            return 0;
        }

        if ($currencyId === (int) $usdId) {
            return $amount;
        }

        $rateAgainstUsd = $this->getCurrencyRateAgainstUsd($currencyId);

        if (!$rateAgainstUsd || $rateAgainstUsd <= 0) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------------
        | rateAgainstUsd means:
        | 1 USD = X currency
        |
        | So:
        | currency amount / X = USD amount
        |--------------------------------------------------------------------------
        */
        return $amount / $rateAgainstUsd;
    }
}
