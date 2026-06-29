<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_lifecycle_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'payment_lifecycle_events_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('payment_id');
            $table->string('dimension', 50);
            $table->string('from_state', 50)->nullable();
            $table->string('to_state', 50);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->dateTime('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'dimension', 'occurred_at'], 'payment_lifecycle_events_payment_ix');
            $table->index(['tenant_id', 'organization_unit_id'], 'payment_lifecycle_events_tenant_org_ix');

            $table->unique(['id', 'tenant_id'], 'payment_lifecycle_events_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'payment_lifecycle_events_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['payment_id', 'tenant_id'], 'payment_lifecycle_events_payment_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->restrictOnDelete();
            $table->foreign(['changed_by', 'tenant_id'], 'payment_lifecycle_events_actor_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_lifecycle_events');
    }
};
