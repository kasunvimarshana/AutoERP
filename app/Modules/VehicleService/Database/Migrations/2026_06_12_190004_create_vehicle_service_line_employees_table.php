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
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_service_job_id')->constrained('vehicle_service_jobs')->cascadeOnDelete();
            $table->foreignId('vehicle_service_job_line_id');
            $table->foreign('vehicle_service_job_line_id', 'vehicle_service_line_employees_job_line_fk')
                ->references('id')
                ->on('vehicle_service_job_lines')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->restrictOnDelete();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_line_employees');
    }
};
