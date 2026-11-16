<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Combined indexes for the filters the lists and statements use most: one account in one
 * currency over a period, the entries of one posting, and remittances by kind, state and date.
 * Only indexes are added; no data changes.
 */
return new class extends Migration
{
    protected array $indexes = [
        'transactions' => [
            'transactions_account_currency_created_index' => ['account_id', 'currency_id', 'created_at'],
            'transactions_source_index' => ['table_name', 'table_row_id'],
            'transactions_status_created_index' => ['status', 'created_at'],
        ],
        'remittances' => [
            'remittances_type_status_date_index' => ['type', 'status', 'date'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($indexes as $name => $columns) {
                if (! Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }
};
