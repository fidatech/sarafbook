<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyClosing extends Model
{
    protected $fillable = [
        'closing_date',
        'closed_by',
        'cash_balance',
        'profit_balance',
        'loss_balance',
        'unpaid_balance',
        'final_balance',
        'currency_balances',
        'remarks',
    ];

    protected $casts = [
        'closing_date' => 'date',
        'currency_balances' => 'array',
        'cash_balance' => 'decimal:6',
        'profit_balance' => 'decimal:6',
        'loss_balance' => 'decimal:6',
        'unpaid_balance' => 'decimal:6',
        'final_balance' => 'decimal:6',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
