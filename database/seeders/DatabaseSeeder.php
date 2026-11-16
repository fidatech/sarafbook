<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AccountCategorySeeder::class);
        $this->call(AccountSubCategorySeeder::class);
        $this->call(CurrencySeeder::class);
        $this->call(PermissionsSeeder::class);
        $this->call(AccountSeeder::class);
        // $this->call(CustomerSarafSeeder::class);
        // $this->call(TransactionsSeeder::class);
        $this->call(ExpenseCategorySeeder::class);
        $this->call(ExchangeRateSeeder::class);
        // $this->call(ExchangeSeeder::class);
    }
}
