<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One Sarafi: a row in the central database that points at its own database.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $database
 * @property string $status  trial | active | suspended
 */
class Tenant extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'slug', 'name', 'database', 'db_username', 'db_password', 'plan_id', 'status',
        'trial_ends_at', 'expires_at', 'release', 'features', 'settings', 'contact_name', 'contact_phone', 'notes',
    ];

    protected $hidden = ['db_password'];

    protected function casts(): array
    {
        return [
            'db_password' => 'encrypted',
            'features' => 'array',
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function primaryDomain(): ?string
    {
        $domains = $this->relationLoaded('domains') ? $this->domains : $this->domains()->get();

        return ($domains->firstWhere('is_primary', true) ?? $domains->first())?->domain;
    }

    public function url(string $path = ''): ?string
    {
        $domain = $this->primaryDomain();
        if (! $domain) {
            return null;
        }
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

        return $scheme.'://'.$domain.($path !== '' ? '/'.ltrim($path, '/') : '');
    }

    /** storage/tenants/<id>/... */
    public function storagePath(string $path = ''): string
    {
        return storage_path('tenants'.DIRECTORY_SEPARATOR.$this->id.($path !== '' ? DIRECTORY_SEPARATOR.$path : ''));
    }

    public function overridesPath(): string
    {
        return rtrim((string) config('tenancy.overrides_path'), '/\\').DIRECTORY_SEPARATOR.$this->slug;
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /** The date the Sarafi is paid (or on trial) until. Null means no end date. */
    public function validUntil(): ?\Illuminate\Support\Carbon
    {
        return $this->status === 'trial' ? ($this->trial_ends_at ?? $this->expires_at) : $this->expires_at;
    }

    public function isExpired(): bool
    {
        $until = $this->validUntil();

        return $until !== null && $until->isPast();
    }

    /** Expired and past the grace period: the books can be read and exported, not changed. */
    public function isReadOnly(): bool
    {
        $until = $this->validUntil();
        if ($until === null) {
            return false;
        }
        $grace = $this->status === 'trial' ? 0 : (int) config('tenancy.grace_days', 14);

        return $until->copy()->addDays($grace)->isPast();
    }

    public function daysLeft(): ?int
    {
        $until = $this->validUntil();

        return $until === null ? null : (int) floor(now()->diffInDays($until, false));
    }

    /** Features of the plan, changed by the switches set on this Sarafi. */
    public function allFeatures(): array
    {
        return array_merge((array) ($this->plan?->features ?? []), (array) ($this->features ?? []));
    }

    public function maxUsers(): ?int
    {
        return $this->plan?->max_users;
    }
}
