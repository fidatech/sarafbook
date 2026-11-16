<?php

/*
| Multi-tenancy: one code base, one MySQL database for each Sarafi.
|
| TENANCY_ENABLED=false runs the application as a single Sarafi on the default database
| (local development, tests, or an installation for one office).
*/

return [

    'enabled' => (bool) env('TENANCY_ENABLED', true),

    // Addresses of the platform itself (the /platform panel). Every other address is a Sarafi.
    'central_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('CENTRAL_DOMAINS', 'localhost,127.0.0.1'))))),

    // New Sarafis get <name>.<base_domain>; a Sarafi may also bring its own domain.
    'base_domain' => env('TENANT_BASE_DOMAIN', 'sarafbook.test'),

    // Tenant databases are named <prefix><slug>.
    'database_prefix' => env('TENANT_DB_PREFIX', 'sb_'),

    // Create a MySQL user for each tenant that can reach only its own database.
    // The central database user then needs CREATE USER and GRANT OPTION.
    'create_database_user' => (bool) env('TENANT_DB_CREATE_USER', false),
    'database_user_host' => env('TENANT_DB_USER_HOST', 'localhost'),

    // Days a new Sarafi can try the system, and days it stays writable after its subscription ends.
    'trial_days' => (int) env('TENANT_TRIAL_DAYS', 14),
    'grace_days' => (int) env('TENANT_GRACE_DAYS', 14),

    // Folder with the per-tenant override layers: tenants/<slug>/{views,lang,migrations,...}
    'overrides_path' => base_path('tenants'),

    // Seeder run in a new tenant database: real starting data only.
    'seeder' => Database\Seeders\TenantSeeder::class,
];
