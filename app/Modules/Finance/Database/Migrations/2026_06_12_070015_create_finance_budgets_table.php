<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_budgets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('fiscal_year_id')->nullable();
            $table->unsignedSmallInteger('budget_year');
            $table->string('name');
            $table->string('status', 30)->default('draft');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'organization_unit_id', 'budget_year', 'name'],
                'finance_budgets_scope_year_name_uk',
            );
            $table->index(['tenant_id', 'organization_unit_id', 'budget_year'], 'finance_budgets_scope_year_idx');

            $table->unique(['id', 'tenant_id'], 'finance_budgets_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'finance_budgets_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['fiscal_year_id', 'tenant_id'], 'finance_budgets_fiscal_year_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_fiscal_years')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_budgets');
    }
};
