<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_current_subscriptions', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->primary('tenant_current_subscriptions_tenant_pk');
            $table->unsignedBigInteger('tenant_subscription_id')->unique('tenant_current_subscriptions_subscription_uk');
            $table->string('state', 40)->default('assigned');
            $table->string('state_reason', 500)->nullable();
            $table->dateTime('state_changed_at');
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamp('assigned_at')->useCurrent();
            $table->unsignedBigInteger('assigned_by')->nullable()->index('tenant_current_subscriptions_assigned_by_ix');
            $table->timestamps();

            $table->foreign('tenant_id', 'tenant_current_subscriptions_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
            $table->foreign(
                ['tenant_subscription_id', 'tenant_id'],
                'tenant_current_subscriptions_subscription_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('tenant_subscriptions')
                ->restrictOnDelete();
            $table->index(['state', 'state_changed_at'], 'tenant_current_subscriptions_state_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_current_subscriptions');
    }
};
