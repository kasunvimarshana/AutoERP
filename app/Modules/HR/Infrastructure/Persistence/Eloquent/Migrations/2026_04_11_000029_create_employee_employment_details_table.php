<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_employment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('employee_id')->constrained('employees', 'id')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments', 'id')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations', 'id')->nullOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types', 'id')->nullOnDelete();
            $table->string('employment_status', 60)->default('draft');
            $table->date('joining_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('leaving_date')->nullable();
            $table->foreignId('reporting_manager_id')->nullable()->constrained('employees', 'id')->nullOnDelete();
            $table->unsignedBigInteger('work_location_id')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'employee_id'], 'employee_employment_details_employee_uk');
            $table->index(['tenant_id', 'department_id'], 'employee_employment_details_department_idx');
            $table->index(['tenant_id', 'designation_id'], 'employee_employment_details_designation_idx');
            $table->index(['tenant_id', 'employment_status'], 'employee_employment_details_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_employment_details');
    }
};
