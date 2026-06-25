<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_line_employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_service_job_id');
            $table->foreignId('vehicle_service_job_line_id');
            $table->foreignId('employee_id');
            $table->string('role_type', 30);
            $table->decimal('assigned_hours', 20, 6)->default('0.000000');
            $table->decimal('rate', 20, 6)->default('0.000000');
            $table->string('commission_type', 20)->default('none');
            $table->decimal('commission_value', 20, 6)->default('0.000000');
            $table->decimal('commission_amount', 20, 6)->default('0.000000');
            $table->string('status', 30)->default('assigned');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['vehicle_service_job_line_id', 'employee_id', 'role_type'],
                'vehicle_service_line_employees_assignment_uk',
            );
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_line_employees_tenant_org_idx');

            $table->unique(['id', 'tenant_id'], 'vehicle_service_line_employees_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_service_line_employees_organization_unit_6c5ed7af_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_service_job_id', 'tenant_id'], 'vehicle_service_line_employees_vehicle_service_j_ad8bb866_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_service_jobs')
                ->cascadeOnDelete();
            $table->foreign(['vehicle_service_job_line_id', 'tenant_id'], 'vehicle_service_line_employees_job_line_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_service_job_lines')
                ->cascadeOnDelete();
            $table->foreign(['employee_id', 'tenant_id'], 'vehicle_service_line_employees_employee_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_line_employees');
    }
};
