<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use Illuminate\Console\Command;

class TenantsList extends Command
{
    protected $signature = 'tenants:list';

    protected $description = 'List every Sarafi';

    public function handle(): int
    {
        $this->table(['Short name', 'Name', 'Address', 'Database', 'Plan', 'Status', 'Valid until'], Tenant::query()->with('domains', 'plan')->orderBy('slug')->get()->map(fn (Tenant $t) => [
            $t->slug, $t->name, $t->primaryDomain(), $t->database, $t->plan?->name ?? '-', $t->status, $t->validUntil()?->toDateString() ?? '-',
        ]));

        return self::SUCCESS;
    }
}
