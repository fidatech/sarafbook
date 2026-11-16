<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Tenancy\TenantProvisioner;
use Illuminate\Console\Command;

class TenantsMigrate extends Command
{
    protected $signature = 'tenants:migrate {--tenant=* : Short names; default every Sarafi}';

    protected $description = 'Run the tenant migrations on each Sarafi database, one by one';

    public function handle(TenantProvisioner $provisioner): int
    {
        $failed = 0;
        $query = Tenant::query()->with('domains', 'plan')->orderBy('id');
        if ($this->option('tenant')) {
            $query->whereIn('slug', $this->option('tenant'));
        }

        foreach ($query->get() as $tenant) {
            try {
                $output = trim($provisioner->migrate($tenant));
                $this->line('<info>'.$tenant->slug.'</info>  '.(str_contains($output, 'Nothing to migrate') ? 'up to date' : "\n".$output));
            } catch (\Throwable $e) {
                // one Sarafi that fails does not stop the others
                $failed++;
                $this->error($tenant->slug.': '.$e->getMessage());
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
