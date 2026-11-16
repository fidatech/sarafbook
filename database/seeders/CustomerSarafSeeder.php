<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountSubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSarafSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category IDs once (avoid multiple queries)
        $customerCategoryId = AccountSubCategory::where('id', 1)->value('id'); // Customer
        $sarafCategoryId    = AccountSubCategory::where('id', 2)->value('id'); // Saraf

        // Sample Afghan-style data
        $names = [
            'احمد خان',
            'شفیق',
            'رحمت الله',
            'حبیب الله',
            'شریف الله',
            'سید کریم',
        ];

        $companies = [
            'Arya Trading',
            'Kabul Exchange',
            'Noor Sarafi',
            'Afghan Money',
            'Barakat Group',
            'Sadaqat Ltd',
        ];

        $addresses = [
            'کابل، شهر نو',
            'کابل، کارته پروان',
            'کابل، مکروریان',
            'کابل، دشت برچی',
            'کابل، تایمنی'
        ];

        // 🔹 Create 5 Customers
        for ($i = 0; $i < 3; $i++) {
            Account::create([
                'name' => $names[$i],
                'code' => $this->generateUniqueCode('CUS'),
                'contact' => '07' . rand(10000000, 99999999),
                'address' => $addresses[array_rand($addresses)],
                'company' => null,
                'account_sub_category_id' => $customerCategoryId,
                'is_active' => true,
                'created_by' => 1,
                'is_safe' => false,
                'bi_icon' => 'bi-person',
                'bi_icon_color' => 'primary',
                'profile_bg' => '#f0f4ff',
            ]);
        }

        // 🔹 Create 5 Saraf
        for ($i = 4; $i < 6; $i++) {
            Account::create([
                'name' => $names[$i] . ' صراف',
                'code' => $this->generateUniqueCode('SAR'),
                'contact' => '07' . rand(10000000, 99999999),
                'address' => $addresses[array_rand($addresses)],
                'company' => $companies[$i],
                'account_sub_category_id' => $sarafCategoryId,
                'is_active' => true,
                'created_by' => 1,
                'is_safe' => false,
                'bi_icon' => 'bi-bank',
                'bi_icon_color' => 'warning',
                'profile_bg' => '#fff7e6',
            ]);
        }
    }

    private function generateUniqueCode($prefix)
    {
        // Find the highest code with the given prefix
        $maxCode = Account::where('code', 'LIKE', $prefix . '%')
            ->orderBy('code', 'desc')
            ->pluck('code')
            ->first();

        // Extract the numerical part of the code and increment it
        if ($maxCode) {
            $numericPart = (int) str_replace($prefix, '', $maxCode);
            $newNumericPart = $numericPart + 1;
            $newCode = $prefix . $newNumericPart;
        } else {
            // If no such code exists, start with the first number (100)
            $newCode = $prefix . '100';
        }

        return $newCode;
    }
}
