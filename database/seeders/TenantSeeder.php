<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * What a new Sarafi needs on its first day and nothing else: account categories, currencies,
 * permissions with the administrator, the system accounts, expense categories and starting rates.
 * Sample customers and transactions are in DemoSeeder and are never run from here.
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccountCategorySeeder::class,
            AccountSubCategorySeeder::class,
            CurrencySeeder::class,
            PermissionsSeeder::class,
            AccountSeeder::class,
            ExpenseCategorySeeder::class,
            ExchangeRateSeeder::class,
        ]);
    }
}
