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
        Schema::create('payroll_payslip_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_payslip_items_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->unsignedBigInteger('payslip_id');
            $table->unsignedBigInteger('salary_component_id')->nullable();
            $table->string('item_type', 32); // earning, allowance, overtime, deduction, employee_statutory, employer_statutory, tax, adjustment
            $table->string('item_code', 32);
            $table->string('item_name', 100);
            $table->decimal('rate', 20, 6)->default('0.000000');
            $table->decimal('hours_or_quantity', 7, 2)->default('0.00');
            $table->decimal('amount', 20, 6)->default('0.000000');
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_statutory')->default(false);
            $table->string('calculation_basis', 255)->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'payroll_payslip_items_id_tenant_uk');
            $table->index(['tenant_id', 'payslip_id'], 'payroll_payslip_items_slip_ix');
            $table->index(['tenant_id', 'item_type'], 'payroll_payslip_items_type_ix');

            $table->foreign(['payslip_id', 'tenant_id'], 'payroll_payslip_items_payslip_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_payslips')
                ->restrictOnDelete();

            $table->foreign(['salary_component_id', 'tenant_id'], 'payroll_payslip_items_component_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_salary_components')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_payslip_items_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payslip_items');
    }
};
