<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_closings', function (Blueprint $table) {

            $table->id();

            $table->date('closing_date')->unique();

            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('cash_balance', 20, 4)->default(0);

            $table->decimal('profit_balance', 20, 4)->default(0);

            $table->decimal('loss_balance', 20, 4)->default(0);

            $table->decimal('unpaid_balance', 20, 4)->default(0);

            $table->decimal('final_balance', 20, 4)->default(0);

            $table->json('currency_balances')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_closings');
    }
};
