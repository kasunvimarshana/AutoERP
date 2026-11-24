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
        Schema::create('hr_leave_balances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_leave_bal_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('employee_id');
            $table->foreignId('leave_type_id');
            $table->unsignedSmallInteger('calendar_year');
            $table->decimal('opening_balance', 5, 2)->default('0.00');
            $table->decimal('accrued_days', 5, 2)->default('0.00');
            $table->decimal('carried_forward_days', 5, 2)->default('0.00');
            $table->decimal('used_days', 5, 2)->default('0.00');
            $table->decimal('pending_approval_days', 5, 2)->default('0.00');
            $table->decimal('expired_days', 5, 2)->default('0.00');
            $table->decimal('current_balance', 5, 2)->default('0.00');
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_leave_bal_id_tenant_uk');
            $table->unique(['tenant_id', 'employee_id', 'leave_type_id', 'calendar_year'], 'hr_leave_bal_emp_type_yr_uk');
            $table->index(['employee_id', 'calendar_year'], 'hr_leave_bal_emp_yr_ix');

            $table->foreign(['employee_id', 'tenant_id'], 'hr_leave_bal_emp_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['leave_type_id', 'tenant_id'], 'hr_leave_bal_type_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_leave_types')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_leave_bal_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_balances');
    }
};
