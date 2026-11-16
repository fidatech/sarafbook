<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            if (!Schema::hasColumn('exchanges', 'profit_currency_id')) {
                $table->foreignId('profit_currency_id')
                    ->nullable()
                    ->after('profit')
                    ->constrained('currencies')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            if (Schema::hasColumn('exchanges', 'profit_currency_id')) {
                $table->dropConstrainedForeignId('profit_currency_id');
            }
        });
    }
};
