<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExchangeRateController extends Controller
{
    /**
     * The rates of one day (today unless another is chosen) and the form to set one.
     */
    public function index(Request $request)
    {
        $currencies = Currency::where('is_active', true)->orderByRaw("CASE WHEN code = 'USD' THEN 0 ELSE 1 END")->orderBy('id')->get();
        $date = rescue(fn () => \Illuminate\Support\Carbon::parse((string) $request->query('date', ''))->toDateString(), now()->toDateString(), false);
        if (! $request->filled('date')) {
            $date = now()->toDateString();
        }

        $rates = ExchangeRate::with(['fromCurrency', 'toCurrency', 'creator:id,username'])
            ->whereDate('date', $date)
            ->orderByDesc('id')
            ->get();
        $days = ExchangeRate::query()->selectRaw('DATE(date) AS d')->groupBy('d')->orderByDesc('d')->limit(14)->pluck('d');

        return page('admin.exchange_rates.index', compact('currencies', 'rates', 'date', 'days'));
    }

    /**
     * List today's rates for AJAX table
     */
    public function list()
    {
        $today = now()->toDateString();

        $rates = ExchangeRate::with(['fromCurrency', 'toCurrency'])
            ->whereDate('date', $today)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $rates,
        ]);
    }

    /**
     * Store/update any currency pair rate
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'from_currency_id' => ['required', 'exists:currencies,id'],
            'to_currency_id' => ['required', 'exists:currencies,id', 'different:from_currency_id'],
            'rate' => ['required', 'numeric', 'min:0.000001'],
        ]);

        DB::beginTransaction();

        try {
            $rate = ExchangeRate::updateOrCreate(
                [
                    'date' => $validated['date'],
                    'from_currency_id' => $validated['from_currency_id'],
                    'to_currency_id' => $validated['to_currency_id'],
                ],
                [
                    'rate' => $validated['rate'],
                    'created_by' => auth()->id(),
                ]
            );

            /*
             * IMPORTANT:
             * currencies.exchange_rate is usually a single/base-rate column.
             * For cross rates like AFN→PKR or PKR→AED, do NOT update it,
             * because one currency can now have many different rates.
             *
             * If you still need old USD-based compatibility, only update
             * currencies.exchange_rate when FROM currency is USD.
             */
            $fromCurrency = Currency::find($validated['from_currency_id']);

            if ($fromCurrency && strtoupper($fromCurrency->code) === 'USD') {
                Currency::where('id', $validated['to_currency_id'])
                    ->update([
                        'exchange_rate' => $validated['rate'],
                    ]);
            }

            DB::commit();

            $rate->load(['fromCurrency', 'toCurrency']);

            if (! $request->expectsJson()) {
                return redirect()->route('admin.exchange-rates.index', ['date' => $validated['date']])->with('success', __('exchange_rates.saved'));
            }

            return response()->json([
                'status' => true,
                'message' => __('exchange_rates.saved'),
                'data' => $rate,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return done(__('exchange_rates.save_failed'), null, false, 500);
        }
    }

    /**
     * Delete rate
     */
    public function destroy($id)
    {
        $rate = ExchangeRate::findOrFail($id);
        $rate->delete();

        return done(__('exchange_rates.deleted'));
    }
}
