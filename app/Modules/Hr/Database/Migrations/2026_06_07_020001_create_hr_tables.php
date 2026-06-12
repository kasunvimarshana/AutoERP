<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_departments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code'], 'hr_departments_tenant_code_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'hr_departments_tenant_org_idx');
        });

        foreach (['designations', 'employment_types'] as $name) {
            Schema::create('hr_'.$name, function (Blueprint $table) use ($name): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
                $table->string('code');
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'code'], "hr_{$name}_tenant_code_uk");
                $table->index(['tenant_id', 'organization_unit_id'], "hr_{$name}_tenant_org_idx");
            });
        }

        foreach (['skills', 'certifications', 'licenses'] as $name) {
            Schema::create('hr_'.$name, function (Blueprint $table) use ($name): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
                $table->string('code');
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'code'], "hr_{$name}_tenant_code_uk");
                $table->index(['tenant_id', 'organization_unit_id'], "hr_{$name}_tenant_org_idx");
            });
        }

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

        Schema::create('hr_employee_contacts', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_contacts');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('contact_name');
            $table->string('relationship')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->boolean('is_emergency_contact')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'employee_id'], 'hr_employee_contacts_scope_idx');
        });

        Schema::create('hr_employee_addresses', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_addresses');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('address_type');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'employee_id'], 'hr_employee_addresses_scope_idx');
        });

        Schema::create('hr_employee_documents', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_documents');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('document_type');
            $table->string('document_number')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'employee_id'], 'hr_employee_documents_scope_idx');
            $table->index('expiry_date');
        });

        Schema::create('hr_employee_skill_assignments', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_skills');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('hr_skills')->cascadeOnDelete();
            $table->string('proficiency_level');
            $table->decimal('years_of_experience', 20, 6)->default('0.000000');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['employee_id', 'skill_id'], 'hr_employee_skills_uk');
            $table->index(['tenant_id', 'skill_id'], 'hr_employee_skills_lookup_idx');
        });

        Schema::create('hr_employee_certification_assignments', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_certifications');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('certification_id')->constrained('hr_certifications')->cascadeOnDelete();
            $table->string('certificate_number')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['employee_id', 'certification_id'], 'hr_employee_certifications_uk');
            $table->index(['tenant_id', 'certification_id'], 'hr_employee_certifications_lookup_idx');
        });

        Schema::create('hr_employee_license_assignments', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_licenses');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('license_id')->constrained('hr_licenses')->cascadeOnDelete();
            $table->string('license_number')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['employee_id', 'license_id'], 'hr_employee_licenses_uk');
            $table->index(['tenant_id', 'license_id'], 'hr_employee_licenses_lookup_idx');
        });

        Schema::create('hr_employee_rates', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_rates');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('rate_type');
            $table->decimal('amount', 20, 6);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'employee_id', 'rate_type'], 'hr_employee_rates_lookup_idx');
            $table->index(['effective_from', 'effective_to'], 'hr_employee_rates_dates_idx');
        });

        Schema::create('hr_employee_availabilities', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_availability');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('availability_date')->nullable();
            $table->string('availability_status');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'employee_id', 'availability_status'], 'hr_employee_availability_lookup_idx');
            $table->index(['source_type', 'source_id'], 'hr_employee_availability_source_idx');
            $table->index(['starts_at', 'ends_at'], 'hr_employee_availability_dates_idx');
        });

        Schema::create('hr_employee_status_histories', function (Blueprint $table): void {
            $table->id();
            $this->scope($table, 'hr_emp_status_history');
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
            $table->index(['tenant_id', 'employee_id', 'changed_at'], 'hr_employee_status_history_idx');
        });
    }

    public function down(): void
    {
        foreach ([
            'hr_employee_status_histories',
            'hr_employee_availabilities',
            'hr_employee_rates',
            'hr_employee_license_assignments',
            'hr_employee_certification_assignments',
            'hr_employee_skill_assignments',
            'hr_employee_documents',
            'hr_employee_addresses',
            'hr_employee_contacts',
            'hr_employees',
            'hr_licenses',
            'hr_certifications',
            'hr_skills',
            'hr_employment_types',
            'hr_designations',
            'hr_departments',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function scope(Blueprint $table, string $constraintPrefix): void
    {
        $table->foreignId('tenant_id');
        $table->foreign('tenant_id', $constraintPrefix.'_tenant_fk')
            ->references('id')
            ->on('tenants')
            ->cascadeOnDelete();
        $table->foreignId('organization_unit_id')->nullable();
        $table->foreign('organization_unit_id', $constraintPrefix.'_org_fk')
            ->references('id')
            ->on('organization_units')
            ->nullOnDelete();
    }
};
