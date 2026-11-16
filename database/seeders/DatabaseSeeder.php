<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Used when the application runs as a single Sarafi (TENANCY_ENABLED=false). */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TenantSeeder::class);
    }
}
