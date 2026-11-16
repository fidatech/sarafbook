<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'name',
        'code',
        'contact',
        'address',
        'company',
        'account_sub_category_id',
        'is_active',
        'bi_icon',
        'bi_icon_color',
        'profile_bg',
        'is_safe',
        'created_by',
        'user_id',
        'include_in_reports',
    ];

    // Common account types used for business logic
    const TYPES = [
        'customer',
        'saraf',
        'bank',
        'office',
        'expense',
        'employee',
    ];

    protected $casts = [
    'include_in_reports' => 'boolean',
];

    /** Relationships */

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
    /** Scopes */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeType($query, $type)
    {
        return $query->whereHas('subCategory', function ($q) use ($type) {
            $q->where('name', $type);
        });
    }

    protected static function booted()
    {
        static::creating(function ($account) {
            // Generate consistent profile background color based on account code
            $colors = ['#FF6B6B', '#6BCB77', '#4D96FF', '#988890', '#9D4EDD', '#00C2D1', '#FF9F1C'];
            $index = crc32($account->code) % count($colors);
            $account->profile_bg = $colors[$index];
        });
    }

    // Accessor for initials (e.g., 'AR', 'EX')
    public function getInitialsAttribute()
    {
        return strtoupper(substr($this->code, 0, 2));
    }

    // Relationship: Belongs to a sub-category
    public function subCategory()
    {
        return $this->belongsTo(AccountSubCategory::class, 'account_sub_category_id');
    }


    // Shortcut relationship: Category through SubCategory
    public function category()
    {
        return $this->subCategory?->category;
    }

    // Dynamically get icon from related category
    // Change to avoid conflict with actual column
    public function getCategoryIconAttribute()
    {
        return $this->category()?->bi_icon ?? 'bi-question-circle';
    }

    public function getCategoryIconColorAttribute()
    {
        return $this->category()?->bi_icon_color ?? '#6c757d';
    }

    // Relationship: Belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
