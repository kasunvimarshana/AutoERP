<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('employee_number');
            $table->string('code')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('display_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('hr_designations')->nullOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('hr_employment_types')->nullOnDelete();
            $table->foreignId('reporting_manager_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->date('joined_date')->nullable();
            $table->date('resigned_date')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('status')->default('pending_approval');
            $table->string('availability_status')->default('available');
            $table->decimal('default_hourly_rate', 20, 6)->default('0.000000');
            $table->decimal('default_daily_rate', 20, 6)->default('0.000000');
            $table->decimal('default_service_rate', 20, 6)->default('0.000000');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'employee_number'], 'hr_employees_tenant_number_uk');
            $table->unique(['tenant_id', 'code'], 'hr_employees_tenant_code_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'hr_employees_tenant_org_idx');
            $table->index(['department_id', 'designation_id', 'employment_type_id'], 'hr_employees_workforce_idx');
            $table->index('reporting_manager_id');
            $table->index('status');
            $table->index('availability_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employees');
    }
};
