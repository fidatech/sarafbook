<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_name',
        'logo',
        'contact',
        'email',
        'address',
        'note_en',
        'note_fa',
        'note_ps',
        'default_language',
        'currency',
        'round_amount',
    ];

    protected $casts = [
        'round_amount' => 'boolean',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }
}
