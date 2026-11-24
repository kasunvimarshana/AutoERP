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
        Schema::create('payroll_salary_structure_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_structure_items_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->unsignedBigInteger('salary_structure_id');
            $table->unsignedBigInteger('salary_component_id');
            $table->string('calculation_type', 32); // fixed, percentage, formula
            $table->decimal('amount_or_rate', 15, 4)->default('0.0000');
            $table->unsignedBigInteger('percentage_basis_component_id')->nullable();
            $table->string('formula_expression', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'payroll_structure_items_id_tenant_uk');
            $table->unique(['salary_structure_id', 'salary_component_id'], 'payroll_structure_items_unique_comp_uk');
            $table->index(['tenant_id', 'salary_structure_id'], 'payroll_structure_items_struct_ix');

            $table->foreign(['salary_structure_id', 'tenant_id'], 'payroll_structure_items_structure_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_salary_structures')
                ->cascadeOnDelete();

            $table->foreign(['salary_component_id', 'tenant_id'], 'payroll_structure_items_component_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_salary_components')
                ->restrictOnDelete();

            $table->foreign(['percentage_basis_component_id', 'tenant_id'], 'payroll_structure_items_basis_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_salary_components')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_structure_items_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_salary_structure_items');
    }
};
