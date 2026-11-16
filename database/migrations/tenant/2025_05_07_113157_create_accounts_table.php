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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('code')->unique()->index();
            $table->string('contact')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('company')->nullable()->index();
            $table->foreignId('account_sub_category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->string('bi_icon')->nullable();
            $table->string('bi_icon_color')->nullable();
            $table->string('profile_bg')->nullable();
            $table->boolean('is_safe')->default(false)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->engine = 'InnoDB';
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
