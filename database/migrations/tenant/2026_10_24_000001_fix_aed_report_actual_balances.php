<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $currencyId = DB::table('currencies')->where('code', 'AED')->value('id');
        if (!$currencyId) {
            return;
        }

        DB::transaction(function () use ($currencyId) {
            DB::table('aed_report_entries')->where('currency_id', $currencyId)
                ->orderBy('id')->chunkById(100, function ($entries) use ($currencyId) {
                    foreach ($entries as $entry) {
                        $balance = DB::table('transactions')->where('account_id', $entry->account_id)
                            ->where('actual_currency_id', $currencyId)->where('status', 'active')
                            ->where('created_at', '<', Carbon::parse($entry->date)->addDay()->startOfDay())
                            ->selectRaw("SUM(CASE WHEN transaction_type = 'credit' THEN actual_amount ELSE -actual_amount END) AS total")
                            ->value('total') ?? 0;

                        DB::table('aed_report_entries')->where('id', $entry->id)->update([
                            'aed_balance' => $balance,
                            'exchange_rate' => 3.67,
                            'converted_amount' => round((float) $balance / 3.67, 2),
                        ]);
                    }
                });
        });
    }

    public function down(): void
    {
        // Incorrect historical balances cannot be reconstructed reliably.
    }
};
