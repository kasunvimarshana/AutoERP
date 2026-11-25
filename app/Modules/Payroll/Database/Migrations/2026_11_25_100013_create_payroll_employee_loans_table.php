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
        Schema::create('payroll_employee_loans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_loans_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->string('loan_reference', 64);
            $table->string('loan_type', 32); // loan, advance
            $table->decimal('principal_amount', 20, 6);
            $table->decimal('annual_interest_rate', 5, 2)->default(0.00);
            $table->unsignedInteger('term_months')->default(1);
            $table->decimal('monthly_installment', 20, 6);
            $table->decimal('total_payable', 20, 6);
            $table->decimal('total_paid', 20, 6)->default('0.000000');
            $table->decimal('balance_remaining', 20, 6);
            $table->date('disbursed_date')->nullable();
            $table->unsignedBigInteger('deduction_start_period_id')->nullable();
            $table->string('status', 32)->default('draft'); // draft, approved, active, completed, cancelled
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'payroll_loans_id_tenant_uk');
            $table->unique(['tenant_id', 'loan_reference'], 'payroll_loans_ref_uk');
            $table->index(['tenant_id', 'employee_id'], 'payroll_loans_emp_ix');
            $table->index(['tenant_id', 'status'], 'payroll_loans_status_ix');
            $table->index(['tenant_id', 'deduction_start_period_id'], 'payroll_loans_period_ix');

            $table->foreign(['employee_id', 'tenant_id'], 'payroll_loans_emp_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();

            $table->foreign(['deduction_start_period_id', 'tenant_id'], 'payroll_loans_period_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_pay_periods')
                ->restrictOnDelete();

            $table->foreign(['approved_by', 'tenant_id'], 'payroll_loans_approved_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_loans_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_employee_loans');
    }
};
