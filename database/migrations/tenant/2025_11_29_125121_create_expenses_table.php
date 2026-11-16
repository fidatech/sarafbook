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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->enum('expense_type', ['company', 'personal'])->default('company')->index();
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->foreignId('paid_from_account_id')->constrained('accounts');

            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('amount', 20, 2);

            $table->bigInteger('payment_currency_id')->nullable()->index();
            $table->decimal('payment_amount', 20, 2)->nullable();
            $table->decimal('rate', 20, 2);
            $table->enum('operation_type', ['multiply', 'divide'])->default('multiply')->index();

            $table->date('expense_date')->index();
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();


            // Link to accounting entry
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            $table->enum('status', ['pending', 'approved'])->default('approved')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['expense_type', 'expense_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
