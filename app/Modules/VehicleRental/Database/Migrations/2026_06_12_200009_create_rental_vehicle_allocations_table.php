<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_vehicle_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'rental_vehicle_allocations_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('allocation_number', 100);
            $table->foreignId('agreement_id');
            $table->foreignId('vehicle_id');
            $table->foreignId('vehicle_ownership_id')->nullable();
            $table->string('vehicle_source_type', 30);
            $table->foreignId('source_allocation_id')->nullable();
            $table->foreignId('vehicle_finance_agreement_id')->nullable();
            $table->foreignId('replaces_allocation_id')->nullable();
            $table->dateTime('allocated_from');
            $table->dateTime('allocated_to')->nullable();
            $table->dateTime('actual_returned_at')->nullable();
            $table->decimal('start_odometer', 20, 6)->nullable();
            $table->decimal('end_odometer', 20, 6)->nullable();
            $table->string('status', 30)->default('planned');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'allocation_number'], 'rental_vehicle_allocations_tenant_number_uk');
            $table->index(['vehicle_id', 'allocated_from', 'allocated_to', 'status'], 'rental_vehicle_allocations_vehicle_period_ix');
            $table->index(['agreement_id', 'status', 'allocated_from'], 'rental_vehicle_allocations_agreement_status_ix');
            $table->index(['source_allocation_id', 'allocated_from', 'allocated_to'], 'rental_vehicle_allocations_source_period_ix');

            $table->unique(['id', 'tenant_id'], 'rental_vehicle_allocations_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_vehicle_allocations_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['agreement_id', 'tenant_id'], 'rental_vehicle_allocations_agreement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_agreements')
                ->cascadeOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'rental_vehicle_allocations_vehicle_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->restrictOnDelete();
            $table->foreign(['vehicle_ownership_id', 'tenant_id'], 'rental_vehicle_allocations_vehicle_ownership_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_ownerships')
                ->restrictOnDelete();
            $table->foreign(['source_allocation_id', 'tenant_id'], 'rental_vehicle_allocations_source_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_vehicle_allocations')
                ->restrictOnDelete();
            $table->foreign(['vehicle_finance_agreement_id', 'tenant_id'], 'rental_vehicle_allocations_veh_fin_agreement_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_finance_agreements')
                ->restrictOnDelete();
            $table->foreign(['replaces_allocation_id', 'tenant_id'], 'rental_vehicle_allocations_replaces_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_vehicle_allocations')
                ->restrictOnDelete();

            $table->foreign(['activated_by', 'tenant_id'], 'rental_vehicle_allocations_activated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['closed_by', 'tenant_id'], 'rental_vehicle_allocations_closed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'rental_vehicle_allocations_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_vehicle_allocations_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_vehicle_allocations');
    }
};
