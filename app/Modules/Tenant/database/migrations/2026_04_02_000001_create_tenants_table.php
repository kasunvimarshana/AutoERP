<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Domain\ValueObjects\TenantPlan;
use Modules\Tenant\Domain\ValueObjects\TenantStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 255);
            $table->string('slug', 100)->unique();
            $table->enum('status', [TenantStatus::ACTIVE, TenantStatus::SUSPENDED, TenantStatus::TRIAL, TenantStatus::CANCELLED])->default(TenantStatus::TRIAL);
            $table->enum('plan', [TenantPlan::FREE, TenantPlan::STARTER, TenantPlan::PROFESSIONAL, TenantPlan::ENTERPRISE])->default(TenantPlan::FREE);
            $table->string('domain')->nullable()->unique();
            $table->string('logo_path')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
