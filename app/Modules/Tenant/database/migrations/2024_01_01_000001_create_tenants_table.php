<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique('tenants_slug_uq');
            $table->string('domain')->unique('tenants_domain_uq')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('database_config')->nullable();
            $table->json('mail_config')->nullable();
            $table->json('cache_config')->nullable();
            $table->json('queue_config')->nullable();
            $table->json('feature_flags')->nullable();
            $table->json('api_keys')->nullable();
            $table->json('settings')->nullable();
            $table->string('plan')->default('free');
            $table->foreignId('tenant_plan_id')->nullable()->constrained('tenant_plans')->nullOnDelete();
            $table->enum('status', ['active', 'suspended', 'pending', 'cancelled'])
                ->default('active')
                ->index('idx_tenants_status');
            $table->boolean('active')->default(true)->index('idx_tenants_active');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common query patterns
            $table->index(['status', 'active'], 'idx_tenants_status_active');
            $table->index(['tenant_plan_id', 'status'], 'idx_tenants_plan_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};