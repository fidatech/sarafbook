<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The remittance form offers "the partner takes the commission" (pay), but the column held only
 * none, company and split, so such a remittance could not be saved. "pay" is added.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('remittances') || DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE remittances MODIFY commission_mode ENUM('none', 'company', 'split', 'pay') NOT NULL DEFAULT 'none'");
    }

    public function down(): void
    {
        // kept: removing "pay" would cut saved values
    }
};
