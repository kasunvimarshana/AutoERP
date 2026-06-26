<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->enum('operation', ['assign', 'renew', 'extend', 'correct']);
            $table->foreignId('tenant_plan_revision_id')->constrained('tenant_plan_revisions', 'id')->restrictOnDelete();
            $table->unsignedBigInteger('supersedes_subscription_id')->nullable();
            $table->enum('contract_status', ['trial', 'active']);
            $table->timestamp('starts_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('change_reason', 500)->nullable();

            // Immutable commercial snapshots preserve the contract even when plan identity changes later.
            $table->string('plan_name');
            $table->string('plan_slug', 100);
            $table->unsignedInteger('plan_features_schema_version');
            $table->json('plan_features');
            $table->unsignedInteger('plan_limits_schema_version');
            $table->json('plan_limits');
            $table->decimal('price', 20, 6)->default('0.000000');
            $table->string('currency_code', 3);
            $table->string('currency_symbol', 20)->nullable();
            $table->enum('billing_interval', ['month', 'quarter', 'year']);

            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_subscriptions_created_by_idx');
            $table->string('created_by_type', 40)->default('system');
            $table->string('created_by_name')->nullable();
            $table->string('created_by_email')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['tenant_id', 'revision_number'], 'tenant_subscriptions_tenant_revision_uk');
            $table->unique(['id', 'tenant_id'], 'tenant_subscriptions_id_tenant_uk');
            $table->index(['tenant_id', 'starts_at', 'ends_at'], 'tenant_subscriptions_tenant_period_idx');
            $table->foreign(
                ['supersedes_subscription_id', 'tenant_id'],
                'tenant_subscriptions_supersedes_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('tenant_subscriptions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
