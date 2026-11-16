<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'table_name',
        'table_row_id',
        'linked_id',
        'account_id',
        'currency_id',
        'amount',
        'actual_currency_id',
        'actual_amount',
        'payment_currency_id',
        'payment_amount',
        'transaction_type',
        'is_cash',
        'note',
        'reference',
        'status',
        'is_visible',
        'created_by',
        'created_at',
        'updated_at',
        'attachment',
        'signature',
        'image',
        'category',
        'color',
        'is_ok',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_ok' => 'boolean',
    ];

    public function accountable()
    {
        return $this->morphTo(null, 'table_name', 'table_row_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function actualCurrency()
    {
        return $this->belongsTo(Currency::class, 'actual_currency_id');
    }

    public function paymentCurrency()
    {
        return $this->belongsTo(Currency::class, 'payment_currency_id');
    }

    public function getCurrencyCodeAttribute()
    {
        return $this->currency->code ?? '';
    }

    public function getAccountCodeAttribute()
    {
        return $this->account->code ?? '';
    }

    public function getAccountBgAttribute()
    {
        return $this->account->profile_bg ?? '#0d6efd';
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'table_row_id')
            ->where('table_name', 'expenses');
    }
}
