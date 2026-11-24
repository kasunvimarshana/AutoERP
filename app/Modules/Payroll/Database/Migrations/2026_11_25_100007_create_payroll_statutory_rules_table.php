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
        Schema::create('payroll_statutory_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_statutory_rules_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('code', 32);
            $table->string('name', 100);
            $table->decimal('employee_rate_percentage', 5, 2);
            $table->decimal('employer_rate_percentage', 5, 2);
            $table->string('applies_to_component_types', 100)->default('basic');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'payroll_statutory_rules_id_tenant_uk');
            $table->unique(['tenant_id', 'code'], 'payroll_statutory_rules_tenant_code_uk');
            $table->index(['tenant_id', 'is_active'], 'payroll_statutory_rules_tenant_active_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_statutory_rules_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_statutory_rules');
    }
};
