<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * exchanges.mode held only "cash" and "customer", but the exchange is saved with how it was
 * posted (saraf, no_track, track, treasure), so saving an exchange for a customer or a Saraf
 * failed. The column becomes a short text, and operation_type gets "multiply" as its default.
 * Existing values are kept as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exchanges') || DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE exchanges MODIFY mode VARCHAR(20) NOT NULL DEFAULT 'customer'");
        DB::statement("ALTER TABLE exchanges MODIFY operation_type ENUM('divide', 'multiply') NOT NULL DEFAULT 'multiply'");
    }

    public function down(): void
    {
        // the wider column is kept: narrowing it again could cut saved values
    }
};
