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
        Schema::create('remittances', function (Blueprint $table) {
            $table->id();

            $table->date('date')->index();

            // incoming: from saraf -> cash (pending until paid)
            // outgoing: cash -> to saraf (paid immediately)
            // transfer: from saraf -> cash -> to saraf (paid immediately)
            $table->enum('type', ['incoming', 'outgoing'])->index();

            $table->unsignedBigInteger('from_account_id')->nullable()->index(); // ghazni saraf (incoming/transfer)
            $table->unsignedBigInteger('to_account_id')->nullable()->index();   // kandahar saraf (outgoing/transfer)

            $table->string('hawala_number')->nullable()->index();
            $table->string('track_hawala_number')->nullable()->index();
            $table->string('sender')->nullable();
            $table->string('receiver')->nullable();

            $table->unsignedBigInteger('currency_id')->index();
            $table->decimal('amount', 20, 2);

            $table->decimal('exchange_rate', 20, 6)->nullable();
            $table->enum('exchange_mode', ['divide', 'multiply'])->nullable()->index();
            $table->unsignedBigInteger('partner_currency_id')->nullable();
            $table->decimal('partner_amount', 20, 2)->nullable();
            $table->unsignedBigInteger('actual_currency_id')->nullable();
            $table->decimal('actual_amount', 20, 2)->nullable();

            $table->enum('remittance_mode', ['track', 'no_track', 'treasure'])->default('treasure')->index();
            $table->unsignedBigInteger('track_account_id')->nullable()->index();

            // Commission mode:
            // none       -> no commission
            // company -> only company commission
            // split      -> kabul + partner (same currency)
            $table->enum('commission_mode', ['none', 'company', 'split'])->default('none')->index();
            $table->unsignedBigInteger('commission_currency_id')->nullable()->index();
            $table->decimal('commission_kabul_amount', 20, 2)->default(0);
            $table->decimal('commission_partner_amount', 20, 2)->default(0);

            // status:
            // incoming default pending; outgoing/transfer default paid
            $table->enum('status', ['pending', 'paid', 'rejected'])->default('paid')->index();

            $table->uuid('linked_id')->nullable()->index(); // links to transactions rows
            $table->text('description')->nullable();

            $table->unsignedBigInteger('created_by')->index();

            $table->timestamps();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittances');
    }
};
