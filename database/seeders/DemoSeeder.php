<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Sample customers, Sarafs, transactions and exchanges for a trial or a demonstration.
 *   php artisan tenants:run "db:seed --class=Database\\Seeders\\DemoSeeder" --tenant=demo
 * It refuses to run when APP_ENV=production unless --force is given to db:seed.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CustomerSarafSeeder::class,
            TransactionsSeeder::class,
            ExchangeSeeder::class,
        ]);
    }
}
