<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $connection = 'central';

    protected $fillable = ['name', 'slug', 'price_yearly', 'setup_fee', 'currency', 'max_users', 'features', 'is_active', 'sort'];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'price_yearly' => 'decimal:2',
            'setup_fee' => 'decimal:2',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
