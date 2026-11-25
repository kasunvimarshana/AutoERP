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
        Schema::create('payroll_payslips', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_payslips_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('payslip_number', 64);
            $table->unsignedBigInteger('pay_run_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('salary_structure_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 32)->default('draft'); // draft, approved, paid, reversed
            $table->decimal('eligible_days', 5, 2)->default('0.00');
            $table->decimal('standard_days', 5, 2)->default('0.00');
            $table->decimal('worked_days', 5, 2)->default('0.00');
            $table->decimal('unpaid_leave_days', 5, 2)->default('0.00');
            $table->decimal('absent_days', 5, 2)->default('0.00');
            $table->decimal('overtime_hours', 7, 2)->default('0.00');
            $table->decimal('double_time_hours', 7, 2)->default('0.00');
            $table->decimal('base_salary', 20, 6)->default('0.000000');
            $table->decimal('prorated_basic_salary', 20, 6)->default('0.000000');
            $table->decimal('gross_pay', 20, 6)->default('0.000000');
            $table->decimal('total_allowances', 20, 6)->default('0.000000');
            $table->decimal('total_deductions', 20, 6)->default('0.000000');
            $table->decimal('employee_statutory_deductions', 20, 6)->default('0.000000');
            $table->decimal('employer_statutory_contributions', 20, 6)->default('0.000000');
            $table->decimal('tax_deduction', 20, 6)->default('0.000000');
            $table->decimal('net_pay', 20, 6)->default('0.000000');
            $table->string('payment_method', 32)->default('bank_transfer');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'payroll_payslips_id_tenant_uk');
            $table->unique(['tenant_id', 'payslip_number'], 'payroll_payslips_tenant_number_uk');
            $table->unique(['tenant_id', 'pay_run_id', 'employee_id'], 'payroll_payslips_run_emp_uk');
            $table->index(['tenant_id', 'employee_id'], 'payroll_payslips_employee_ix');
            $table->index(['tenant_id', 'status'], 'payroll_payslips_status_ix');
            $table->index(['tenant_id', 'period_start', 'period_end'], 'payroll_payslips_dates_ix');

            $table->foreign(['pay_run_id', 'tenant_id'], 'payroll_payslips_pay_run_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_pay_runs')
                ->restrictOnDelete();

            $table->foreign(['employee_id', 'tenant_id'], 'payroll_payslips_employee_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();

            $table->foreign(['salary_structure_id', 'tenant_id'], 'payroll_payslips_structure_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_salary_structures')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_payslips_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payslips');
    }
};
