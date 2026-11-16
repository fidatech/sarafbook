<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('currencies')->insert([
            [
                'name' => 'افغانی',
                'code' => 'AFN',
                'symbol' => '؋',
                'country' => 'Afghanistan',
                'flag' => 'assets/flags/af.svg',
                'exchange_rate' => 66.41,
                'is_default' => false,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'دالر',
                'code' => 'USD',
                'symbol' => '$',
                'country' => 'United States',
                'flag' => 'assets/flags/us.svg',
                'exchange_rate' => 1,
                'is_default' => true,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'کالدار',
                'code' => 'PKR',
                'symbol' => '₨',
                'country' => 'Pakistan',
                'flag' => 'assets/flags/pk.svg',
                'exchange_rate' => 279.50,
                'is_default' => false,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'درهم',
                'code' => 'AED',
                'symbol' => 'د.إ',
                'country' => 'United Arab Emirates',
                'flag' => 'assets/flags/ae.svg',
                'exchange_rate' => 3.67,
                'is_default' => false,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'ریال',
                'code' => 'SAR',
                'symbol' => '﷼',
                'country' => 'Saudi Arabia',
                'flag' => 'assets/flags/sa.svg',
                'exchange_rate' => 3.75,
                'is_default' => false,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
