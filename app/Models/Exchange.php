<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
    protected $fillable = [
        'date',
        'type',
        'mode',
        'account_id',
        'from_currency_id',
        'to_currency_id',
        'profit_currency_id',
        'from_amount',
        'to_amount',
        'rate',
        'base_rate',
        'usd_amount',
        'profit',
        'operation_type',
        'note',
        'linked_id',
        'created_by'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function fromCurrency()
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency()
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    public function profitCurrency()
    {
        return $this->belongsTo(Currency::class, 'profit_currency_id');
    }
}
