<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->uuid('uuid')->unique('tenants_uuid_uk');
            $table->string('code', 50)->unique('tenants_code_uk');
            $table->string('name');
            $table->string('slug', 100)->unique('tenants_slug_uk');
            $table->string('logo_path')->nullable();
            $table->boolean('cross_org_transactions')->default(false);
            $table->foreignId('base_currency_id')->nullable()->constrained('currencies', 'id')->restrictOnDelete();
            $table->enum('status', ['draft', 'active', 'inactive', 'suspended', 'archived'])->default('draft');
            $table->string('status_reason', 500)->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('tenants_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenants_updated_by_idx');
            $table->timestamps();
            $table->index(['status', 'name'], 'tenants_status_name_idx');
            $table->unique(['id', 'base_currency_id'], 'tenants_id_currency_uk');
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('tenant_plan_revision_id')->constrained('tenant_plan_revisions', 'id')->restrictOnDelete();
            $table->enum('status', ['trial', 'active', 'expired', 'cancelled']);
            $table->timestamp('starts_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_subscriptions_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_subscriptions_updated_by_idx');
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'tenant_subscriptions_id_tenant_uk');
            $table->index(['tenant_id', 'status', 'ends_at'], 'tenant_subscriptions_tenant_status_end_idx');
        });

        Schema::create('tenant_current_subscriptions', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->primary()->constrained('tenants', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_subscription_id')->unique('tenant_current_subscriptions_subscription_uk');
            $table->timestamp('assigned_at');
            $table->unsignedBigInteger('assigned_by')->nullable()->index('tenant_current_subscriptions_assigned_by_idx');

            $table->foreign(
                ['tenant_subscription_id', 'tenant_id'],
                'tenant_current_subscriptions_subscription_tenant_fk',
            )->references(['id', 'tenant_id'])->on('tenant_subscriptions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_current_subscriptions');
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('tenants');
    }
};
