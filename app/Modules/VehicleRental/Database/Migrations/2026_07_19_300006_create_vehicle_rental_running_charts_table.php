<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Enums\RentalRunningChartStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_running_charts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vr_running_charts_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('chart_number', 100);
            $table->foreignId('assignment_id');
            $table->foreignId('replaces_running_chart_id')->nullable();
            $table->date('operational_date');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->foreignId('driver_employee_id')->nullable();
            $table->string('ac_mode', 20)->nullable();
            $table->decimal('start_odometer', 20, 6);
            $table->decimal('end_odometer', 20, 6);
            $table->decimal('total_km', 20, 6);
            $table->decimal('garage_km', 20, 6)->default('0.000000');
            $table->decimal('commercial_km', 20, 6);
            $table->decimal('normal_overtime_hours', 20, 6)->default('0.000000');
            $table->decimal('double_overtime_hours', 20, 6)->default('0.000000');
            $table->decimal('triple_overtime_hours', 20, 6)->default('0.000000');
            $table->unsignedInteger('night_out_count')->default(0);
            $table->string('trip_origin', 255)->nullable();
            $table->string('trip_destination', 255)->nullable();
            $table->string('purpose', 255)->nullable();
            $table->string('odometer_variance_reason', 500)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default(RentalRunningChartStatus::Draft->value);
            $table->boolean('active_marker')->nullable()->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'chart_number'], 'vr_running_charts_tenant_number_uk');
            $table->unique(['id', 'tenant_id'], 'vr_running_charts_id_tenant_uk');
            $table->unique(['assignment_id', 'operational_date', 'active_marker'], 'vr_running_charts_assignment_date_active_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'status', 'operational_date'], 'vr_running_charts_scope_status_date_ix');
            $table->index(['driver_employee_id', 'starts_at', 'ends_at'], 'vr_running_charts_driver_period_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_running_charts_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['assignment_id', 'tenant_id'], 'vr_running_charts_assignment_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_assignments')->restrictOnDelete();
            $table->foreign(['replaces_running_chart_id', 'tenant_id'], 'vr_running_charts_replaces_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_running_charts')->restrictOnDelete();
            $table->foreign(['driver_employee_id', 'tenant_id'], 'vr_running_charts_driver_tenant_fk')
                ->references(['id', 'tenant_id'])->on('hr_employees')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'vr_running_charts_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['finalized_by', 'tenant_id'], 'vr_running_charts_finalized_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['reversed_by', 'tenant_id'], 'vr_running_charts_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_running_charts');
    }
};
