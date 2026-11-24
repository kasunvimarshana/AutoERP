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
        Schema::create('payroll_salary_components', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_salary_components_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('code', 32);
            $table->string('name', 100);
            $table->string('type', 32); // earning, deduction, statutory, employer_contribution
            $table->string('calculation_type', 32); // fixed, percentage, hourly_rate, formula
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_statutory_base')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('gl_account_name', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'payroll_salary_components_id_tenant_uk');
            $table->unique(['tenant_id', 'code'], 'payroll_salary_components_tenant_code_uk');
            $table->index(['tenant_id', 'type'], 'payroll_salary_components_tenant_type_ix');
            $table->index(['tenant_id', 'is_active'], 'payroll_salary_components_tenant_active_ix');
            $table->index(['tenant_id', 'organization_unit_id'], 'payroll_salary_components_tenant_org_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_salary_components_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_salary_components');
    }
};
