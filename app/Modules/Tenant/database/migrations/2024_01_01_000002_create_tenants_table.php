<?php

declare(strict_types=1);

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
            $table->string('slug')->unique('tenants_slug_uk');
            $table->string('domain')->unique('tenants_domain_uk')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('database_config')->nullable();
            $table->json('mail_config')->nullable();
            $table->json('cache_config')->nullable();
            $table->json('queue_config')->nullable();
            $table->json('feature_flags')->nullable();
            $table->json('api_keys')->nullable();
            $table->json('settings')->nullable();
            $table->string('plan')->default('free');
            $table->foreignId('tenant_plan_id')->nullable();
            $table->foreign('tenant_plan_id', 'tenants_tenant_plan_id_fk')
                ->references('id')
                ->on('tenant_plans')
                ->nullOnDelete();
            $table->enum('status', ['active', 'suspended', 'pending', 'cancelled'])
                ->default('active')
                ->index('tenants_status_idx');
            $table->boolean('active')->default(true)->index('tenants_active_idx');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common query patterns
            $table->index(['status', 'active'], 'tenants_status_active_idx');
            $table->index(['tenant_plan_id', 'status'], 'tenants_plan_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
