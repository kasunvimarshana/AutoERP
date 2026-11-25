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
        Schema::create('payroll_tax_tables', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_tax_tables_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('code', 32);
            $table->string('name', 100);
            $table->unsignedSmallInteger('effective_year');
            $table->decimal('standard_personal_relief', 20, 6)->default('0.000000');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'payroll_tax_tables_id_tenant_uk');
            $table->unique(['tenant_id', 'code'], 'payroll_tax_tables_tenant_code_uk');
            $table->index(['tenant_id', 'effective_year'], 'payroll_tax_tables_tenant_year_ix');
            $table->index(['tenant_id', 'is_active'], 'payroll_tax_tables_tenant_active_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_tax_tables_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_tax_tables');
    }
};
