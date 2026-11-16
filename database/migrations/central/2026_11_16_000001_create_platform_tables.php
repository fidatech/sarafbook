<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The platform's own tables (central database). Tenant tables are in database/migrations/tenant. */
return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price_yearly', 14, 2)->default(0);
            $table->decimal('setup_fee', 14, 2)->default(0);
            $table->string('currency', 3)->default('AFN');
            $table->unsignedInteger('max_users')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::connection('central')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 48)->unique();
            $table->string('name');
            $table->string('database', 64)->unique();
            $table->string('db_username', 32)->nullable();
            $table->text('db_password')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('status', 16)->default('trial')->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('release', 32)->default('stable');
            $table->json('features')->nullable();
            $table->json('settings')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('domain', 191)->unique();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::connection('central')->create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('AFN');
            $table->string('method', 24)->default('cash');
            $table->string('reference')->nullable();
            $table->timestamp('paid_at');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('action', 64)->index();
            $table->json('details')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        // sessions of the platform panel, and the queue shared by all Sarafis
        Schema::connection('central')->create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::connection('central')->create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        Schema::connection('central')->create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });

        Schema::connection('central')->create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::connection('central')->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['failed_jobs', 'jobs', 'cache_locks', 'cache', 'sessions', 'platform_audits', 'subscription_payments', 'platform_users', 'tenant_domains', 'tenants', 'plans'] as $table) {
            Schema::connection('central')->dropIfExists($table);
        }
    }
};
