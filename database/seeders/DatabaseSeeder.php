<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Used when the application runs as a single Sarafi (TENANCY_ENABLED=false).
 * With tenancy on, the central database needs no starting data: each Sarafi is seeded
 * when it is created (php artisan tenants:create).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (config('tenancy.enabled')) {
            $this->command?->warn('Tenancy is on: nothing to seed in the central database. Create a Sarafi with: php artisan tenants:create <short-name>');

            return;
        }

        $this->call(TenantSeeder::class);
    }
}
