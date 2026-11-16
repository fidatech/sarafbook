<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;

class AccountSubCategorySeeder extends Seeder
{
    public function run()
    {
        // ===============================
        // Fetch Main Categories
        // ===============================
        $clientCategory   = AccountCategory::where('name', 'Client')->first();   // Client accounts
        $cashCategory     = AccountCategory::where('name', 'Cash')->first();     // Safe, Bank, Expenses
        $employeeCategory = AccountCategory::where('name', 'Employee')->first(); // Employee accounts

        // ===============================
        // CLIENT SUBCATEGORIES
        // ===============================
        AccountSubCategory::create([
            'name' => 'Customer',
            'account_category_id' => $clientCategory->id,
        ]);

        AccountSubCategory::create([
            'name' => 'Saraf',
            'account_category_id' => $clientCategory->id,
        ]);

        AccountSubCategory::create([
            'name' => 'Safe',
            'account_category_id' => $cashCategory->id,
        ]);
        AccountSubCategory::create([
            'name' => 'PnL',
            'account_category_id' => $cashCategory->id,
        ]);
        AccountSubCategory::create([
            'name' => 'EXP',
            'account_category_id' => $cashCategory->id,
        ]);

        // AccountSubCategory::create([
        //     'name' => 'Bank',
        //     'account_category_id' => $cashCategory->id,
        // ]);

        // AccountSubCategory::create([
        //     'name' => 'Employee',
        //     'account_category_id' => $employeeCategory->id,
        // ]);
    }
}
