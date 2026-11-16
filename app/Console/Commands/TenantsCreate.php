<?php

namespace App\Console\Commands;

use App\Models\Central\Plan;
use App\Tenancy\TenantProvisioner;
use Illuminate\Console\Command;

class TenantsCreate extends Command
{
    protected $signature = 'tenants:create {slug : Short name, used in the address and the database name}
        {--name= : Name of the Sarafi}
        {--domain= : Address; default <slug>.<TENANT_BASE_DOMAIN>}
        {--plan= : Slug of the plan}
        {--status=trial : trial or active}
        {--expires= : Date the subscription ends (YYYY-MM-DD)}
        {--admin-username=admin}
        {--admin-password= : Left empty, a password is made and shown once}
        {--existing-database : The database <prefix><slug> already exists: an imported client keeps its data, an empty database (made in cPanel) is filled like a new one}';

    protected $description = 'Create a Sarafi with its own database';

    public function handle(TenantProvisioner $provisioner): int
    {
        if (! config('tenancy.enabled')) {
            $this->error('Tenancy is off (TENANCY_ENABLED=false).');

            return self::FAILURE;
        }

        $plan = $this->option('plan') ? Plan::query()->where('slug', $this->option('plan'))->first() : null;
        if ($this->option('plan') && ! $plan) {
            $this->error('Unknown plan: '.$this->option('plan'));

            return self::FAILURE;
        }

        try {
            [$tenant, $username, $password] = $provisioner->create(array_filter([
                'slug' => $this->argument('slug'),
                'name' => $this->option('name') ?: ucfirst($this->argument('slug')),
                'domain' => $this->option('domain'),
                'plan_id' => $plan?->id,
                'status' => $this->option('status'),
                'expires_at' => $this->option('expires'),
                'admin_username' => $this->option('admin-username'),
                'admin_password' => $this->option('admin-password'),
                'existing_database' => (bool) $this->option('existing-database'),
            ], fn ($value) => $value !== null && $value !== ''));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Created: '.$tenant->name);
        $this->table(['Address', 'Database', 'Username', 'Password'], [[
            $tenant->url(), $tenant->database, $password === '' ? '(kept from the imported database)' : $username, $password === '' ? '(kept)' : $password,
        ]]);

        return self::SUCCESS;
    }
}
