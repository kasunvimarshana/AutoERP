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
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_employees_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('employee_number');
            $table->string('code')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('display_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->foreignId('designation_id')->nullable();
            $table->foreignId('employment_type_id')->nullable();
            $table->foreignId('reporting_manager_id')->nullable();
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
            $table->index(['tenant_id', 'organization_unit_id'], 'hr_employees_tenant_org_ix');
            $table->index(['department_id', 'designation_id', 'employment_type_id'], 'hr_employees_workforce_ix');
            $table->index('reporting_manager_id', 'hr_employees_reporting_manager_ix');
            $table->index('status', 'hr_employees_status_ix');
            $table->index('availability_status', 'hr_employees_availability_status_ix');

            $table->unique(['id', 'tenant_id'], 'hr_employees_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_employees_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['department_id', 'tenant_id'], 'hr_employees_department_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_departments')
                ->restrictOnDelete();
            $table->foreign(['designation_id', 'tenant_id'], 'hr_employees_designation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_designations')
                ->restrictOnDelete();
            $table->foreign(['employment_type_id', 'tenant_id'], 'hr_employees_employment_type_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employment_types')
                ->restrictOnDelete();
            $table->foreign(['reporting_manager_id', 'tenant_id'], 'hr_employees_reporting_manager_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();

            $table->foreign(['approved_by', 'tenant_id'], 'hr_employees_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employees');
    }
};
