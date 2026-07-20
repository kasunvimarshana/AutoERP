<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vr_assignments_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('agreement_id');
            $table->foreignId('vehicle_id');
            $table->foreignId('source_assignment_id')->nullable();
            $table->foreignId('replaces_assignment_id')->nullable();
            $table->string('side', 20);
            $table->string('status', 20)->default(RentalAssignmentStatus::Planned->value);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->decimal('handover_odometer', 20, 6)->nullable();
            $table->decimal('return_odometer', 20, 6)->nullable();
            $table->foreignId('driver_employee_id')->nullable();
            $table->boolean('self_drive')->default(false);
            $table->string('replacement_reason', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'vr_assignments_id_tenant_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'vehicle_id', 'status'], 'vr_assignments_vehicle_status_ix');
            $table->index(['agreement_id', 'starts_at', 'ends_at'], 'vr_assignments_agreement_period_ix');
            $table->index(['driver_employee_id', 'starts_at', 'ends_at'], 'vr_assignments_driver_period_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_assignments_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['agreement_id', 'tenant_id'], 'vr_assignments_agreement_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_agreements')->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'vr_assignments_vehicle_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicles')->restrictOnDelete();
            $table->foreign(['source_assignment_id', 'tenant_id'], 'vr_assignments_source_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_assignments')->restrictOnDelete();
            $table->foreign(['replaces_assignment_id', 'tenant_id'], 'vr_assignments_replaces_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_assignments')->restrictOnDelete();
            $table->foreign(['driver_employee_id', 'tenant_id'], 'vr_assignments_driver_tenant_fk')
                ->references(['id', 'tenant_id'])->on('hr_employees')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'vr_assignments_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['closed_by', 'tenant_id'], 'vr_assignments_closed_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_assignments');
    }
};
