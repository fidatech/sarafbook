<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Office Rent',
                'description' => 'Monthly rental payments for office space.',
            ],
            [
                'name' => 'Utilities',
                'description' => 'Electricity, water, heating, and cooling expenses.',
            ],
            [
                'name' => 'Internet & Communication',
                'description' => 'Internet, phone bills, and communication systems.',
            ],
            [
                'name' => 'Employee Salaries',
                'description' => 'Monthly payroll expenses.',
            ],
            [
                'name' => 'Transportation',
                'description' => 'Fuel, taxi, travel, and related logistics.',
            ],
            [
                'name' => 'Maintenance & Repairs',
                'description' => 'Repairs of equipment, office, and tools.',
            ],
            [
                'name' => 'Software Subscriptions',
                'description' => 'SaaS tools and digital services.',
            ],
            [
                'name' => 'Marketing & Advertising',
                'description' => 'Promotional materials, ads, and branding.',
            ],
            [
                'name' => 'Office Supplies',
                'description' => 'Stationery, printing paper, and office materials.',
            ],
            [
                'name' => 'Legal & Consultancy',
                'description' => 'Legal advice, audits, and professional consulting.',
            ],
            [
                'name' => 'Travel & Accommodation',
                'description' => 'Business trips, hotels, and related expenses.',
            ],
            [
                'name' => 'Meals & Hospitality',
                'description' => 'Client meetings, hospitality, and food.',
            ],
            [
                'name' => 'Training & Development',
                'description' => 'Workshops, courses, and employee training.',
            ],
            [
                'name' => 'Bank Charges',
                'description' => 'Bank transfer fees and service charges.',
            ],
            [
                'name' => 'Insurance',
                'description' => 'Office, equipment, or employee insurance.',
            ],
            [
                'name' => 'Miscellaneous',
                'description' => 'Other small expenses not in specific categories.',
            ],
        ];

        DB::table('expense_categories')->insert($categories);
    }
}
