<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\WhatsappSession;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionsSeeder extends Seeder
{
    public function run()
    {
        $totalRecords = 100000;
        $chunkSize = 1000;

        // 🔹 Sample Data (Adjust according to your DB)
        $accountIds = DB::table('accounts')->pluck('id')->toArray();
        $currencyIds = DB::table('currencies')->pluck('id')->toArray();

        if (empty($accountIds) || empty($currencyIds)) {
            $this->command->error('Accounts or currencies table is empty!');
            return;
        }

        $categories = [
            'expense',
            'exchange_profit',
            'commission',
            'remittance',
            'transfer',
            'salary',
            'purchase'
        ];

        $statuses = ['active'];

        $this->command->info("Seeding $totalRecords transactions...");

        for ($i = 0; $i < $totalRecords; $i += $chunkSize) {

            $data = [];

            for ($j = 0; $j < $chunkSize; $j++) {

                $currency = $currencyIds[array_rand($currencyIds)];
                $account = $accountIds[array_rand($accountIds)];

                $paymentCurrency = $currencyIds[array_rand($currencyIds)];
                $actualCurrency = $currencyIds[array_rand($currencyIds)];

                $amount = rand(100, 100000) / 10;
                $paymentAmount = rand(100, 100000) / 10;
                $actualAmount = rand(100, 100000) / 10;

                $type = rand(0, 1) ? 'credit' : 'debit';

                $date = Carbon::now()->subDays(rand(0, 365));

                $data[] = [
                    'table_name' => 'seeded',
                    'table_row_id' => rand(1, 10000),
                    'linked_id' => (string) Str::uuid(),

                    'account_id' => $account,

                    'payment_currency_id' => $paymentCurrency,
                    'payment_amount' => $paymentAmount,

                    'actual_currency_id' => $actualCurrency,
                    'actual_amount' => $actualAmount,

                    'currency_id' => $currency,
                    'amount' => $amount,

                    'transaction_type' => $type,
                    'is_cash' => rand(0, 1),

                    'note' => 'Auto generated transaction ' . Str::random(10),

                    'status' => $statuses[array_rand($statuses)],
                    'is_visible' => 1,

                    'created_by' => 1,

                    'attachment' => null,
                    'signature' => null,
                    'image' => null,

                    'category' => $categories[array_rand($categories)],

                    'created_at' => $date,
                    'updated_at' => $date,
                ];
            }

            DB::table('transactions')->insert($data);

            $this->command->info("Inserted: " . ($i + $chunkSize));
        }

        $this->command->info('✅ Done seeding transactions.');
    }
}
