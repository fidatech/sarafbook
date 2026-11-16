<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\AccountBalance;
use Illuminate\Support\Facades\DB;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        $this->applyChange($transaction->getAttributes());
    }

    public function updated(Transaction $transaction): void
    {
        if (!$transaction->wasChanged(['account_id', 'currency_id', 'amount', 'transaction_type', 'status'])) return;
        DB::transaction(function () use ($transaction) {
            $this->applyChange($transaction->getRawOriginal(), true);
            $this->applyChange($transaction->getAttributes());
        });
    }

    public function deleted(Transaction $transaction): void
    {
        $this->applyChange($transaction->getAttributes(), true);
    }

    private function applyChange(array $entry, bool $reverse = false): void
    {
        if (($entry['status'] ?? 'active') !== 'active') return;
        $amount = (float) $entry['amount'] * ($reverse ? -1 : 1);
        $credit = $entry['transaction_type'] === 'credit';
        $balance = AccountBalance::firstOrCreate([
            'account_id' => $entry['account_id'], 'currency_id' => $entry['currency_id'],
        ], ['credit' => 0, 'debit' => 0, 'balance' => 0]);
        // Atomic arithmetic avoids losing another request's simultaneous posting.
        DB::table('account_balances')->where('id', $balance->id)->incrementEach([
            $credit ? 'credit' : 'debit' => $amount,
            'balance' => $credit ? $amount : -$amount,
        ], ['updated_at' => now()]);
    }
}
