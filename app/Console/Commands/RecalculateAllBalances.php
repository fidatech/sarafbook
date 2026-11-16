<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AccountBalance;
use Illuminate\Support\Facades\DB;

class RecalculateAllBalances extends Command
{
    protected $signature = 'balances:recalculate';
    protected $description = 'Recalculate account balances from active ledger entries';

    public function handle(): int
    {
        $this->info('Recalculating balances...');
        DB::transaction(function () {
            // Keep the ledger stable during the rebuild; include inactive rows in the lock.
            DB::table('transactions')->orderBy('id')->lockForUpdate()->pluck('id');
            $rows = DB::table('transactions')
                ->where('status', 'active')
                ->select('account_id', 'currency_id')
                ->selectRaw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit,
                    SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit,
                    SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as balance")
                ->groupBy('account_id', 'currency_id')->get();

            // Also clear stale pairs whose last entry was deleted or made inactive.
            AccountBalance::query()->update(['credit' => 0, 'debit' => 0, 'balance' => 0]);
            foreach ($rows as $row) {
                AccountBalance::updateOrCreate(
                    ['account_id' => $row->account_id, 'currency_id' => $row->currency_id],
                    ['credit' => $row->credit, 'debit' => $row->debit, 'balance' => $row->balance]
                );
            }
        }, 3);
        $this->info('Done.');
        return self::SUCCESS;
    }
}
