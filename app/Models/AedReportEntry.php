<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AedReportEntry extends Model
{
    protected $fillable = ['account_id', 'currency_id', 'aed_balance', 'exchange_rate', 'converted_amount', 'date'];
    protected $casts = ['date' => 'date', 'aed_balance' => 'decimal:2', 'exchange_rate' => 'decimal:8', 'converted_amount' => 'decimal:2'];

    public $calculated_exchange_rate = null;
    public $usd_balance = 0;
    public $conversion_aed_balance = 0;

    public static function refreshLiveBalances($entries): void
    {
        if ($entries->isEmpty()) {
            return;
        }

        $balances = \Illuminate\Support\Facades\DB::table('aed_report_entries as reports')
            ->join('transactions as ledger', function ($join) {
                $join->on('ledger.account_id', '=', 'reports.account_id')
                    ->on('ledger.actual_currency_id', '=', 'reports.currency_id');
            })
            ->leftJoin('currencies as posting_currency', 'posting_currency.id', '=', 'ledger.currency_id')
            ->whereIn('reports.id', $entries->pluck('id'))
            ->where('ledger.status', 'active')
            ->where('ledger.created_at', '<', now()->addDay()->startOfDay())
            ->groupBy('reports.id')
            ->selectRaw("reports.id, SUM(CASE WHEN ledger.transaction_type = 'credit' THEN ledger.actual_amount ELSE -ledger.actual_amount END) AS balance")
            ->selectRaw("SUM(CASE WHEN posting_currency.code = 'USD' THEN CASE WHEN ledger.transaction_type = 'credit' THEN ledger.amount ELSE -ledger.amount END ELSE 0 END) AS usd_balance")
            ->selectRaw("SUM(CASE WHEN posting_currency.code = 'USD' THEN 0 ELSE CASE WHEN ledger.transaction_type = 'credit' THEN ledger.actual_amount ELSE -ledger.actual_amount END END) AS conversion_aed_balance")
            ->get()->keyBy('id');

        foreach ($entries as $entry) {
            $balance = $balances->get($entry->id);
            $entry->aed_balance = $balance->balance ?? 0;
            $entry->usd_balance = $balance->usd_balance ?? 0;
            $entry->conversion_aed_balance = $balance->conversion_aed_balance ?? 0;
            $entry->converted_amount = round((float) $entry->usd_balance + (float) $entry->conversion_aed_balance / (float) $entry->exchange_rate, 2);
            // Match the AED and USD balances shown in this report row.
            $usdBalance = (float) $entry->converted_amount;
            $entry->calculated_exchange_rate = $usdBalance != 0.0
                ? round((float) $entry->aed_balance / $usdBalance, 4)
                : null;
        }
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
