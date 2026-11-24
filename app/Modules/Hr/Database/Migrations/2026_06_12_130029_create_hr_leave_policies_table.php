<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_leave_policies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_leave_policies_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('leave_type_id');
            $table->foreignId('department_id')->nullable();
            $table->foreignId('employment_type_id')->nullable();
            $table->string('name', 100);
            $table->decimal('annual_allocation_days', 5, 2)->default('14.00');
            $table->string('accrual_frequency', 32)->default('annual_upfront');
            $table->decimal('max_carry_forward_days', 5, 2)->default('5.00');
            $table->unsignedSmallInteger('carry_forward_expiry_months')->default(3);
            $table->unsignedSmallInteger('max_consecutive_days')->default(10);
            $table->boolean('probation_restricted')->default(false);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_leave_policies_id_tenant_uk');
            $table->index(['leave_type_id', 'department_id', 'employment_type_id'], 'hr_leave_policies_scope_ix');
            $table->index(['tenant_id', 'effective_from'], 'hr_leave_policies_effective_ix');

            $table->foreign(['leave_type_id', 'tenant_id'], 'hr_leave_policies_type_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_leave_types')
                ->restrictOnDelete();
            $table->foreign(['department_id', 'tenant_id'], 'hr_leave_policies_dept_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_departments')
                ->restrictOnDelete();
            $table->foreign(['employment_type_id', 'tenant_id'], 'hr_leave_policies_emp_type_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employment_types')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_leave_policies_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_policies');
    }
};
