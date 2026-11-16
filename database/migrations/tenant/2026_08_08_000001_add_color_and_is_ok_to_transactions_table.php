<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('category')->index();
            $table->boolean('is_ok')->default(false)->after('color')->index();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['color']);
            $table->dropIndex(['is_ok']);
            $table->dropColumn(['color', 'is_ok']);
        });
    }
};
