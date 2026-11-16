<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aed_report_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->decimal('aed_balance', 20, 2);
            $table->decimal('exchange_rate', 20, 8)->comment('AED per 1 USD');
            $table->decimal('converted_amount', 20, 2)->comment('USD');
            $table->date('date');
            $table->timestamps();
            $table->unique(['account_id', 'currency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aed_report_entries');
    }
};
