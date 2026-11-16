<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDomain extends Model
{
    protected $connection = 'central';

    protected $fillable = ['tenant_id', 'domain', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function booted(): void
    {
        static::saving(fn (self $domain) => $domain->domain = strtolower(trim($domain->domain)));
        $forget = fn (self $domain) => app(\App\Tenancy\Tenancy::class)->forgetDomain($domain->domain);
        static::saved($forget);
        static::deleted($forget);
    }
}
