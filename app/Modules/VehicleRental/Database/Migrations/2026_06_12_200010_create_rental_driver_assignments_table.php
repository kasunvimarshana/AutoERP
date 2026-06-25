<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_driver_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('agreement_id');
            $table->foreignId('vehicle_allocation_id')->nullable();
            $table->foreignId('employee_id');
            $table->string('assignment_role', 20)->default('primary');
            $table->dateTime('assigned_from');
            $table->dateTime('assigned_to')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->string('status', 20)->default('planned');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'assigned_from', 'assigned_to', 'status'], 'rental_driver_assignments_employee_period_idx');
            $table->index(['vehicle_allocation_id', 'assigned_from', 'assigned_to'], 'rental_driver_assignments_allocation_period_idx');
            $table->index(['agreement_id', 'status'], 'rental_driver_assignments_agreement_status_idx');

            $table->unique(['id', 'tenant_id'], 'rental_driver_assignments_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_driver_assignments_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['agreement_id', 'tenant_id'], 'rental_driver_assignments_agreement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_agreements')
                ->cascadeOnDelete();
            $table->foreign(['vehicle_allocation_id', 'tenant_id'], 'rental_driver_assignments_vehicle_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_vehicle_allocations')
                ->cascadeOnDelete();
            $table->foreign(['employee_id', 'tenant_id'], 'rental_driver_assignments_employee_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'rental_driver_assignments_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_driver_assignments_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_driver_assignments');
    }
};
