<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('from_currency_id');
            $table->foreignId('to_currency_id');
            $table->decimal('rate', 18, 6);
            $table->enum('type', ['purchase', 'sell'])->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->unique(['date', 'from_currency_id', 'to_currency_id', 'type'], 'unique_daily_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
