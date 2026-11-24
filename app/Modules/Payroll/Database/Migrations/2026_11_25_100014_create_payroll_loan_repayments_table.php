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
        Schema::create('payroll_loan_repayments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_repayments_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->unsignedBigInteger('payroll_employee_loan_id');
            $table->unsignedBigInteger('pay_run_id')->nullable();
            $table->unsignedBigInteger('payslip_id')->nullable();
            $table->date('repayment_date');
            $table->decimal('principal_amount', 15, 4);
            $table->decimal('interest_amount', 15, 4)->default(0.0000);
            $table->decimal('total_repaid', 15, 4);
            $table->decimal('balance_after', 15, 4);
            $table->string('status', 32)->default('scheduled'); // scheduled, deducted, manual_payment, reversed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'payroll_repayments_id_tenant_uk');
            $table->index(['tenant_id', 'payroll_employee_loan_id'], 'payroll_repayments_loan_ix');
            $table->index(['tenant_id', 'pay_run_id'], 'payroll_repayments_run_ix');
            $table->index(['tenant_id', 'payslip_id'], 'payroll_repayments_slip_ix');

            $table->foreign(['payroll_employee_loan_id', 'tenant_id'], 'payroll_repayments_loan_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_employee_loans')
                ->restrictOnDelete();

            $table->foreign(['pay_run_id', 'tenant_id'], 'payroll_repayments_run_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_pay_runs')
                ->restrictOnDelete();

            $table->foreign(['payslip_id', 'tenant_id'], 'payroll_repayments_slip_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_payslips')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_repayments_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_loan_repayments');
    }
};
