<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_budget_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('budget_id');
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'finance_budget_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('account_id');
            $table->foreignId('dimension_id')->nullable();
            $table->unsignedTinyInteger('budget_month')->nullable();
            $table->decimal('amount', 20, 6)->default('0.000000');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'account_id'], 'finance_budget_lines_scope_account_ix');
            $table->unique(['id', 'tenant_id'], 'finance_budget_lines_id_tenant_uk');
            $table->foreign(['budget_id', 'tenant_id'], 'finance_budget_lines_budget_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_budgets')
                ->cascadeOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'finance_budget_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['account_id', 'tenant_id'], 'finance_budget_lines_account_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_accounts')
                ->restrictOnDelete();
            $table->foreign(['dimension_id', 'tenant_id'], 'finance_budget_lines_dimension_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_dimensions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_budget_lines');
    }
};
