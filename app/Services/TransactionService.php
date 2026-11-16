<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Post new transaction entries.
     *
     * @param string $module     (table/module name)
     * @param int    $rowId      (row id in that module)
     * @param array  $entries    (multiple transaction rows)
     * @return array             (created transactions)
     */
    public static function post(string $module, int $rowId, array $entries): array
    {
        $created = [];

        DB::transaction(function () use (&$created, $module, $rowId, $entries) {

            foreach ($entries as $entry) {

                $created[] = Transaction::create([
                    'table_name'       => $module,
                    'table_row_id'     => $rowId,

                    'account_id'       => $entry['account_id'],
                    'currency_id'      => $entry['currency_id'],

                    'amount'           => $entry['amount'],
                    'transaction_type' => $entry['transaction_type'],

                    'is_cash'          => $entry['is_cash'] ?? false,
                    'note'             => $entry['note'] ?? null,

                    'status'           => 'active',
                    'is_visible'       => $entry['is_visible'] ?? true,

                    'created_by'       => auth()->id(),
                ]);
            }
        });

        return $created;
    }


    /**
     * Delete all transactions for a module + row
     */
    public static function delete(string $module, int $rowId): int
    {
        return Transaction::where('table_name', $module)
            ->where('table_row_id', $rowId)
            ->delete();
    }


    /**
     * Repost = delete existing + add new
     */
    public static function repost(string $module, int $rowId, array $entries): array
    {
        self::delete($module, $rowId);

        return self::post($module, $rowId, $entries);
    }


    /**
     * Get transactions for module + row
     */
    public static function get(string $module, int $rowId)
    {
        return Transaction::where('table_name', $module)
            ->where('table_row_id', $rowId)
            ->get();
    }

    public static function deleteByLinkedId(?string $linkedId): int
    {
        if (!$linkedId) {
            return 0;
        }

        $count = 0;

        Transaction::where('linked_id', $linkedId)
            ->get()
            ->each(function ($transaction) use (&$count) {
                $transaction->delete(); // observer fires
                $count++;
            });

        return $count;
    }
}
