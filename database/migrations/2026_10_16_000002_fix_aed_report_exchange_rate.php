<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('aed_report_entries')->update([
            'exchange_rate' => 3.67,
            'converted_amount' => DB::raw('ROUND(aed_balance / 3.67, 2)'),
        ]);
    }

    public function down(): void
    {
        // Previous manually entered rates cannot be reconstructed.
    }
};
