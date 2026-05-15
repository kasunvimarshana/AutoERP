<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name')->comment('Annual Budget 2025", "Marketing Q1');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->string('budget_type')->default('expense')->comment('expense, revenue, profit, cash_flow');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('draft')->comment('draft, submitted, approved, closed');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'name'], 'budgets_name_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'fiscal_year_id'], 'budgets_fiscal_year_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
