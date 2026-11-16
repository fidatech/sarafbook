<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remittance extends Model
{
    public static function nextOutgoingHawalaNumber(int $accountId): int
    {
        // A saraf shares one sequence between direct and tracked remittances.
        // Read each number only from the side belonging to this account.
        $direct = static::query()->where('type', 'outgoing')->where('to_account_id', $accountId);
        $tracked = static::query()->where('type', 'outgoing')->where('track_account_id', $accountId);

        $directMax = (clone $direct)->selectRaw('MAX(CAST(hawala_number AS UNSIGNED)) as max_number')
            ->value('max_number');
        $trackedMax = (clone $tracked)->selectRaw('MAX(CAST(track_hawala_number AS UNSIGNED)) as max_number')
            ->value('max_number');
        $count = static::query()->where('type', 'outgoing')
            ->where(function ($query) use ($accountId) {
                $query->where('to_account_id', $accountId)->orWhere('track_account_id', $accountId);
            })->count();

        return max($count, (int) $directMax, (int) $trackedMax) + 1;
    }

    protected $fillable = [
        'date',
        'type',
        'from_account_id',
        'to_account_id',
        'track_account_id',
        'hawala_number',
        'track_hawala_number',
        'sender',
        'receiver',
        'currency_id',
        'amount',
        'partner_currency_id',
        'partner_amount',
        'actual_currency_id',
        'actual_amount',
        'remittance_mode',
        'commission_mode',
        'commission_currency_id',
        'commission_kabul_amount',
        'commission_partner_amount',
        'status',
        'linked_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'partner_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'commission_kabul_amount' => 'decimal:2',
        'commission_partner_amount' => 'decimal:2',
    ];

    protected $hidden = ['receiver_id_copy_path', 'receiver_fingerprint_path', 'receiver_photo_path', 'receiver_id_number'];

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function trackAccount()
    {
        return $this->belongsTo(Account::class, 'track_account_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function partnerCurrency()
    {
        return $this->belongsTo(Currency::class, 'partner_currency_id');
    }

    public function actualCurrency()
    {
        return $this->belongsTo(Currency::class, 'actual_currency_id');
    }
    public function commissionCurrency()
    {
        return $this->belongsTo(Currency::class, 'commission_currency_id');
    }
}
