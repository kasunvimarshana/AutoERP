<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
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

            $table->string('employee_code', 60);
            $table->string('first_name', 120);
            $table->string('last_name', 120)->nullable();
            $table->string('display_name', 180)->nullable();
            $table->string('full_name', 220)->nullable();
            $table->string('gender', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('national_id_number', 120)->nullable();
            $table->string('passport_number', 120)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('mobile', 100)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types')->nullOnDelete();
            $table->foreignId('reporting_manager_id')
                ->nullable()
                ->constrained('employees', 'id', 'employees_reporting_manager_fk')
                ->nullOnDelete();
            $table->date('joining_date')->nullable();
            $table->date('leaving_date')->nullable();
            $table->string('employment_status', 60)->default('draft');
            $table->boolean('is_active')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->unsignedBigInteger('deactivated_by')->nullable();
            $table->unsignedBigInteger('suspended_by')->nullable();
            $table->unsignedBigInteger('terminated_by')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'employee_code'], 'employees_employee_code_uk');
            $table->index(['tenant_id', 'full_name'], 'employees_full_name_idx');
            $table->index(['tenant_id', 'employment_status', 'is_active'], 'employees_status_active_idx');
            $table->index(['tenant_id', 'department_id'], 'employees_department_idx');
            $table->index(['tenant_id', 'designation_id'], 'employees_designation_idx');
            $table->index(['tenant_id', 'reporting_manager_id'], 'employees_reporting_manager_idx');
            $table->index(['tenant_id', 'email'], 'employees_email_idx');
        });

        Schema::table('departments', function (Blueprint $table): void {
            $table->foreign('manager_employee_id', 'departments_manager_employee_fk')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->dropForeign('departments_manager_employee_fk');
        });

        Schema::dropIfExists('employees');
    }
};
