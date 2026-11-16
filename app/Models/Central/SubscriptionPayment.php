<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $connection = 'central';

    protected $fillable = ['tenant_id', 'plan_id', 'amount', 'currency', 'method', 'reference', 'paid_at', 'period_start', 'period_end', 'note', 'recorded_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'period_start' => 'date', 'period_end' => 'date'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'recorded_by');
    }
}
