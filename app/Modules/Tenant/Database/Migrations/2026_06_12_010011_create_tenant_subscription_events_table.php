<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_subscription_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->unsignedBigInteger('tenant_subscription_id');
            $table->unsignedBigInteger('previous_subscription_id')->nullable();
            $table->enum('event_type', ['assigned', 'renewed', 'extended', 'corrected', 'cancelled', 'expired']);
            $table->string('reason', 500)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable()->index('tenant_subscription_events_actor_idx');
            $table->string('actor_type', 40)->default('system');
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->timestamp('occurred_at');

            $table->foreign(
                ['tenant_subscription_id', 'tenant_id'],
                'tenant_subscription_events_subscription_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('tenant_subscriptions')
                ->restrictOnDelete();
            $table->foreign(
                ['previous_subscription_id', 'tenant_id'],
                'tenant_subscription_events_previous_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('tenant_subscriptions')
                ->restrictOnDelete();
            $table->index(['tenant_id', 'occurred_at'], 'tenant_subscription_events_tenant_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscription_events');
    }
};
