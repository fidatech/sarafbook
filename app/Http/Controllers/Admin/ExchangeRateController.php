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
     * Show form + latest rates
     */
    public function index()
    {
        $currencies = Currency::orderBy('code')->get();

        $today = now()->toDateString();

        $rates = ExchangeRate::with(['fromCurrency', 'toCurrency'])
            ->whereDate('date', $today)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return view('admin.exchange_rates.index', compact('currencies', 'rates'));
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

            return response()->json([
                'status' => true,
                'message' => __('exchange_rates.saved'),
                'data' => $rate,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => __('exchange_rates.save_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete rate
     */
    public function destroy($id)
    {
        $rate = ExchangeRate::findOrFail($id);
        $rate->delete();

        return response()->json([
            'status' => true,
            'message' => __('exchange_rates.deleted'),
        ]);
    }
}
