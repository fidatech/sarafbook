<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountSubCategory extends Model
{
    protected $fillable = ['name', 'account_category_id'];

    public function accountCategory()
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }
}
