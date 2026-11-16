<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::today()->toDateString();

        // Base currency: USD = 2
        $usd = 2;

        // Define currencies (example IDs — adjust if needed)
        $rates = [
            [
                'currency_id' => 1, // AFN
                'sell' => 65,
            ],
            [
                'currency_id' => 3, // PKR
                'sell' => 282.00,
            ],
            [
                'currency_id' => 4, // AED
                'sell' => 3.67,
            ],
            [
                'currency_id' => 5, // SAR
                'sell' => 3.82,
            ],
        ];

        foreach ($rates as $rate) {
            // SELL RATE (USD → currency)
            DB::table('exchange_rates')->updateOrInsert(
                [
                    'date' => $today,
                    'from_currency_id' => $usd,
                    'to_currency_id' => $rate['currency_id'],
                    'type' => 'sell',
                ],
                [
                    'rate' => $rate['sell'],
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
