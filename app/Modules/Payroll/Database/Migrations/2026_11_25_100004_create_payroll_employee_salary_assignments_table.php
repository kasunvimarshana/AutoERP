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
        Schema::create('payroll_employee_salary_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_assignments_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('salary_structure_id');
            $table->decimal('base_salary', 20, 6);
            $table->decimal('hourly_rate', 20, 6)->default('0.000000');
            $table->string('payment_method', 32)->default('bank_transfer'); // bank_transfer, cash, cheque
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'payroll_assignments_id_tenant_uk');
            $table->index(['tenant_id', 'employee_id'], 'payroll_assignments_emp_ix');
            $table->index(['tenant_id', 'salary_structure_id'], 'payroll_assignments_struct_ix');
            $table->index(['tenant_id', 'is_active'], 'payroll_assignments_active_ix');
            $table->index(['tenant_id', 'effective_from'], 'payroll_assignments_from_ix');

            $table->foreign(['employee_id', 'tenant_id'], 'payroll_assignments_employee_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();

            $table->foreign(['salary_structure_id', 'tenant_id'], 'payroll_assignments_structure_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_salary_structures')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_assignments_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_employee_salary_assignments');
    }
};
