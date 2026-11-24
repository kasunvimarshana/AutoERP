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
        Schema::create('payroll_salary_structures', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_salary_structures_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('code', 32);
            $table->string('name', 100);
            $table->string('cycle_type', 32)->default('monthly'); // monthly, bi_weekly, weekly
            $table->foreignId('currency_id')->nullable()->constrained('currencies', indexName: 'payroll_structures_currency_fk')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'payroll_salary_structures_id_tenant_uk');
            $table->unique(['tenant_id', 'code'], 'payroll_salary_structures_tenant_code_uk');
            $table->index(['tenant_id', 'cycle_type'], 'payroll_salary_structures_tenant_cycle_ix');
            $table->index(['tenant_id', 'is_active'], 'payroll_salary_structures_tenant_active_ix');
            $table->index(['tenant_id', 'organization_unit_id'], 'payroll_salary_structures_tenant_org_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_salary_structures_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_salary_structures');
    }
};
