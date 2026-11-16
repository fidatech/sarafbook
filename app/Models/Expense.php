<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Expense extends Model
{
    use LogsActivity;

    protected static $logName = 'expenses';
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->useLogName('expenses')
            ->setDescriptionForEvent(fn(string $eventName) => "Expense {$eventName}");
    }

    protected $fillable = [
        'expense_type',
        'expense_category_id',
        'paid_from_account_id',
        'currency_id',
        'amount',

        'payment_currency_id',
        'payment_amount',
        'rate',
        'operation_type',

        'expense_date',
        'description',
        'attachment_path',
        'transaction_id',
        'status',
        'created_by'
    ];

    public function paymentCurrency() {
        return $this->belongsTo(Currency::class, 'payment_currency_id');
    }

    public function expenseCategory() {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
    public function paidFromAccount() {
        return $this->belongsTo(Account::class, 'paid_from_account_id');
    }

    public function currency() {
        return $this->belongsTo(Currency::class);
    }

    public function transaction() {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by');
    }

}
