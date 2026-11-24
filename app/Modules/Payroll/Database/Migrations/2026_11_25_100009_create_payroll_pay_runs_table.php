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
        Schema::create('payroll_pay_runs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_pay_runs_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('pay_run_number', 64);
            $table->unsignedBigInteger('pay_period_id');
            $table->unsignedBigInteger('salary_structure_id')->nullable();
            $table->string('pay_run_type', 32)->default('standard'); // standard, supplementary, correction, bonus
            $table->string('status', 32)->default('draft'); // draft, calculated, verified, approved, paid, reversed
            $table->decimal('total_gross_pay', 15, 4)->default('0.0000');
            $table->decimal('total_deductions', 15, 4)->default('0.0000');
            $table->decimal('total_employee_statutory', 15, 4)->default('0.0000');
            $table->decimal('total_employer_statutory', 15, 4)->default('0.0000');
            $table->decimal('total_tax', 15, 4)->default('0.0000');
            $table->decimal('total_net_pay', 15, 4)->default('0.0000');
            $table->unsignedInteger('total_employees')->default(0);
            $table->unsignedBigInteger('calculated_by')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->unsignedBigInteger('finance_journal_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'payroll_pay_runs_id_tenant_uk');
            $table->unique(['tenant_id', 'pay_run_number'], 'payroll_pay_runs_number_uk');
            $table->index(['tenant_id', 'pay_period_id'], 'payroll_pay_runs_period_ix');
            $table->index(['tenant_id', 'status'], 'payroll_pay_runs_status_ix');
            $table->index(['tenant_id', 'pay_run_type'], 'payroll_pay_runs_type_ix');

            $table->foreign(['pay_period_id', 'tenant_id'], 'payroll_pay_runs_period_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_pay_periods')
                ->restrictOnDelete();

            $table->foreign(['salary_structure_id', 'tenant_id'], 'payroll_pay_runs_structure_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_salary_structures')
                ->restrictOnDelete();

            $table->foreign(['calculated_by', 'tenant_id'], 'payroll_pay_runs_calc_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['approved_by', 'tenant_id'], 'payroll_pay_runs_app_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['paid_by', 'tenant_id'], 'payroll_pay_runs_paid_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['reversed_by', 'tenant_id'], 'payroll_pay_runs_rev_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['finance_journal_id', 'tenant_id'], 'payroll_pay_runs_journal_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_journal_entries')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_pay_runs_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_pay_runs');
    }
};
