<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAudit extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_audits';

    public const UPDATED_AT = null;

    protected $fillable = ['platform_user_id', 'tenant_id', 'action', 'details', 'ip'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'platform_user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function record(string $action, ?Tenant $tenant = null, array $details = []): void
    {
        static::query()->create([
            'platform_user_id' => auth('platform')->id(),
            'tenant_id' => $tenant?->id,
            'action' => $action,
            'details' => $details ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
