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
        Schema::create('exchanges', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('type', ['purchase', 'sell']);
            $table->enum('mode', ['cash', 'customer']);
            $table->foreignId('account_id');
            $table->foreignId('from_currency_id');
            $table->foreignId('to_currency_id');
            $table->decimal('from_amount', 18, 2);
            $table->decimal('to_amount', 18, 2);
            $table->decimal('rate', 18, 6);
            $table->decimal('base_rate', 20, 6)->nullable();
            $table->decimal('usd_amount', 20, 6)->nullable();
            $table->decimal('profit', 20, 6)->nullable();
            // 🔥 NEW FIELD
            $table->enum('operation_type', ['divide', 'multiply']);
            $table->string('note')->nullable();
            $table->uuid('linked_id')->nullable();
            $table->foreignId('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchanges');
    }
};
