<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Tenancy\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TenantsRun extends Command
{
    protected $signature = 'tenants:run {artisan : An artisan command in quotes, e.g. "balances:recalculate"}
        {--tenant=* : Short names; default every Sarafi}';

    protected $description = 'Run an artisan command as each Sarafi';

    public function handle(Tenancy $tenancy): int
    {
        $failed = 0;
        $query = Tenant::query()->with('domains', 'plan')->orderBy('id');
        if ($this->option('tenant')) {
            $query->whereIn('slug', $this->option('tenant'));
        }

        foreach ($query->get() as $tenant) {
            $this->line('<info>'.$tenant->slug.'</info>');
            try {
                $tenancy->run($tenant, function () {
                    Artisan::call($this->argument('artisan'), [], $this->output);
                });
            } catch (\Throwable $e) {
                $failed++;
                $this->error($e->getMessage());
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
