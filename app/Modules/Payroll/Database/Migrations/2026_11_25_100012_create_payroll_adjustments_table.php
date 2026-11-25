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
        Schema::create('payroll_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_adjustments_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->string('adjustment_type', 32); // addition, deduction, retro_pay, reimbursement, correction
            $table->unsignedBigInteger('salary_component_id')->nullable();
            $table->decimal('amount', 20, 6);
            $table->unsignedBigInteger('effective_pay_period_id')->nullable();
            $table->string('status', 32)->default('pending'); // pending, applied, cancelled
            $table->unsignedBigInteger('applied_payslip_id')->nullable();
            $table->text('reason');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'payroll_adjustments_id_tenant_uk');
            $table->index(['tenant_id', 'employee_id'], 'payroll_adjustments_emp_ix');
            $table->index(['tenant_id', 'status'], 'payroll_adjustments_status_ix');
            $table->index(['tenant_id', 'effective_pay_period_id'], 'payroll_adjustments_period_ix');

            $table->foreign(['employee_id', 'tenant_id'], 'payroll_adjustments_emp_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();

            $table->foreign(['salary_component_id', 'tenant_id'], 'payroll_adjustments_comp_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_salary_components')
                ->restrictOnDelete();

            $table->foreign(['effective_pay_period_id', 'tenant_id'], 'payroll_adjustments_period_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_pay_periods')
                ->restrictOnDelete();

            $table->foreign(['applied_payslip_id', 'tenant_id'], 'payroll_adjustments_payslip_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_payslips')
                ->restrictOnDelete();

            $table->foreign(['approved_by', 'tenant_id'], 'payroll_adjustments_approved_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_adjustments_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
    }
};
