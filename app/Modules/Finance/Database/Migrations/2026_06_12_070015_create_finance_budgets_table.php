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
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('fiscal_year_id')->nullable()->constrained('finance_fiscal_years', 'id')->nullOnDelete();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_budgets');
    }
};
