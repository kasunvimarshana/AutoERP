<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('usage_number', 100);
            $table->foreignId('vehicle_allocation_id');
            $table->foreignId('vehicle_id');
            $table->foreignId('driver_assignment_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->date('usage_date');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->decimal('start_odometer', 20, 6);
            $table->decimal('end_odometer', 20, 6);
            $table->decimal('distance_km', 20, 6)->default('0.000000');
            $table->decimal('chargeable_distance_km', 20, 6)->default('0.000000');
            $table->decimal('garage_distance_km', 20, 6)->default('0.000000');
            $table->decimal('internal_distance_km', 20, 6)->default('0.000000');
            $table->unsignedInteger('working_minutes')->default(0);
            $table->unsignedInteger('normal_overtime_minutes')->default(0);
            $table->unsignedInteger('double_overtime_minutes')->default(0);
            $table->unsignedInteger('triple_overtime_minutes')->default(0);
            $table->decimal('night_out_count', 20, 6)->default('0.000000');
            $table->string('trip_from')->nullable();
            $table->string('trip_to')->nullable();
            $table->string('trip_purpose')->nullable();
            $table->text('odometer_variance_reason')->nullable();
            $table->unsignedInteger('operational_sequence')->default(1);
            $table->string('status', 30)->default('draft');
            $table->char('fingerprint', 64);
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->dateTime('reversed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'usage_number'], 'rental_usage_logs_tenant_number_uk');
            $table->unique(['tenant_id', 'fingerprint'], 'rental_usage_logs_fingerprint_uk');
            $table->index(['vehicle_allocation_id', 'usage_date', 'status'], 'rental_usage_logs_allocation_date_idx');
            $table->index(['vehicle_id', 'usage_date', 'status'], 'rental_usage_logs_vehicle_date_idx');
            $table->index(['driver_id', 'usage_date', 'status'], 'rental_usage_logs_driver_date_idx');
            $table->index(['vehicle_id', 'status', 'started_at', 'id'], 'rental_usage_logs_odometer_chain_idx');

            $table->unique(['id', 'tenant_id'], 'rental_usage_logs_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_usage_logs_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_allocation_id', 'tenant_id'], 'rental_usage_logs_vehicle_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_vehicle_allocations')
                ->cascadeOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'rental_usage_logs_vehicle_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->restrictOnDelete();
            $table->foreign(['driver_assignment_id', 'tenant_id'], 'rental_usage_logs_driver_assignment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_driver_assignments')
                ->restrictOnDelete();
            $table->foreign(['driver_id', 'tenant_id'], 'rental_usage_logs_driver_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();

            $table->foreign(['submitted_by', 'tenant_id'], 'rental_usage_logs_submitted_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['approved_by', 'tenant_id'], 'rental_usage_logs_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['reversed_by', 'tenant_id'], 'rental_usage_logs_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'rental_usage_logs_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_usage_logs_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_usage_logs');
    }
};
