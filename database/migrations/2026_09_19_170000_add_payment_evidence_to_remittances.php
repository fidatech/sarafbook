<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remittances', function (Blueprint $table) {
            $table->string('receiver_id_number')->nullable();
            $table->string('receiver_id_copy_path')->nullable();
            $table->string('receiver_fingerprint_path')->nullable();
            $table->string('receiver_photo_path')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('paid_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('remittances', function (Blueprint $table) {
            $table->dropColumn(['receiver_id_number', 'receiver_id_copy_path', 'receiver_fingerprint_path', 'receiver_photo_path', 'paid_by', 'paid_at']);
        });
    }
};
