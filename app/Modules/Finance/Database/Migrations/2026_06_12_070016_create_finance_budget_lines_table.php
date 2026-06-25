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
            $table->foreignId('budget_id')->constrained('finance_budgets', 'id')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts', 'id')->restrictOnDelete();
            $table->foreignId('fiscal_period_id')->nullable()->constrained('finance_fiscal_periods', 'id')->nullOnDelete();
            $table->foreignId('dimension_id')->nullable()->constrained('finance_dimensions', 'id')->nullOnDelete();
            $table->unsignedTinyInteger('budget_month')->nullable();
            $table->decimal('amount', 20, 6)->default('0.000000');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'account_id'], 'finance_budget_lines_scope_account_idx');
            $table->index(['budget_id', 'fiscal_period_id'], 'finance_budget_lines_budget_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_budget_lines');
    }
};
