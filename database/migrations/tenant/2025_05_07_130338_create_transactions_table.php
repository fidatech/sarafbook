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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('table_name')->nullable()->index();
            $table->bigInteger('table_row_id')->nullable()->index();
            $table->uuid('linked_id')->nullable()->index();
            $table->bigInteger('account_id')->index();
            $table->bigInteger('payment_currency_id')->index();
            $table->decimal('payment_amount', 20, 2);
            $table->bigInteger('actual_currency_id')->index();
            $table->decimal('actual_amount', 20, 2);
            $table->bigInteger('currency_id')->index();
            $table->decimal('amount', 20, 2);
            $table->enum('transaction_type', ['credit', 'debit'])->index();
            $table->boolean('is_cash')->default(false);
            $table->text('note')->nullable();
            $table->string('reference')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active')->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->bigInteger('created_by')->index();
            $table->string('attachment')->nullable();
            $table->string('signature')->nullable();
            $table->string('image')->nullable();
            $table->string('category')->nullable()->index(); // e.g., 'expense', 'exchange_profit', 'commission', etc.
            $table->timestamps();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
