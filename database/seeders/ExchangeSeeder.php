<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExchangeSeeder extends Seeder
{
    public function run()
    {
        // Get necessary IDs once to avoid repeated queries
        $customerIds = Account::where('account_sub_category_id', 1)->pluck('id')->toArray();
        $cashId = Account::where('account_sub_category_id', 3)->value('id');
        $currencyIds = Currency::pluck('id')->toArray();
        $userId = 1; // Assuming a default admin user ID

        $totalRecords = 100000;
        $batchSize = 1000; // Optimal for performance

        $this->command->info("Starting seeding 100k exchanges...");

        for ($i = 0; $i < $totalRecords / $batchSize; $i++) {
            DB::transaction(function () use ($batchSize, $customerIds, $cashId, $currencyIds, $userId) {
                $exchangesToInsert = [];
                $transactionsToInsert = [];

                for ($j = 0; $j < $batchSize; $j++) {
                    $type = collect(['purchase', 'sell'])->random();
                    $mode = collect(['cash', 'customer'])->random();
                    $accountId = ($mode === 'cash') ? $cashId : collect($customerIds)->random();

                    // Currencies
                    $fromCur = collect($currencyIds)->random();
                    $toCur = collect($currencyIds)->reject(fn($id) => $id == $fromCur)->random();

                    $op = collect(['multiply', 'divide'])->random();
                    $rate = rand(100000, 800000) / 10000; // Random rate between 10.0000 and 80.0000
                    $fromAmount = rand(500, 50000);
                    $toAmount = ($op === 'multiply') ? ($fromAmount * $rate) : ($fromAmount / $rate);

                    $linkedId = (string) Str::uuid();
                    $date = Carbon::now()->subDays(rand(0, 365));

                    // 1. Prepare Exchange Record
                    $exchangeData = [
                        'date' => $date,
                        'type' => $type,
                        'mode' => $mode,
                        'account_id' => $accountId,
                        'from_currency_id' => $fromCur,
                        'to_currency_id' => $toCur,
                        'from_amount' => $fromAmount,
                        'to_amount' => round($toAmount, 2),
                        'rate' => $rate,
                        'operation_type' => $op,
                        'linked_id' => $linkedId,
                        'created_by' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Note: We need the ID for transactions.
                    // Since we are bulk inserting, we'll use the linked_id to associate them.
                    $exId = DB::table('exchanges')->insertGetId($exchangeData);

                    // 2. Prepare Transaction Records (Matching your Controller logic)

                }

            });

            $this->command->comment("Batch " . ($i + 1) . " of " . ($totalRecords / $batchSize) . " completed.");
        }

        $this->command->info("Seeding finished successfully.");
    }

    private function txArray($linkedId, $accId, $curId, $amt, $dir, $exId)
    {
        return [
            'linked_id' => $linkedId,
            'account_id' => $accId,
            'currency_id' => $curId,
            'amount' => round($amt, 2),
            'direction' => $dir,
            'table_name' => 'exchanges',
            'table_row_id' => $exId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
