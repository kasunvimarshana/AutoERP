<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->decimal('period_1_amount', 20, 4)->default(0)->comment('January');
            $table->decimal('period_2_amount', 20, 4)->default(0)->comment('February');
            $table->decimal('period_3_amount', 20, 4)->default(0)->comment('March');
            $table->decimal('period_4_amount', 20, 4)->default(0)->comment('April');
            $table->decimal('period_5_amount', 20, 4)->default(0)->comment('May');
            $table->decimal('period_6_amount', 20, 4)->default(0)->comment('June');
            $table->decimal('period_7_amount', 20, 4)->default(0)->comment('July');
            $table->decimal('period_8_amount', 20, 4)->default(0)->comment('August');
            $table->decimal('period_9_amount', 20, 4)->default(0)->comment('September');
            $table->decimal('period_10_amount', 20, 4)->default(0)->comment('October');
            $table->decimal('period_11_amount', 20, 4)->default(0)->comment('November');
            $table->decimal('period_12_amount', 20, 4)->default(0)->comment('December');
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'budget_id', 'account_id', 'cost_center_id'], 'budget_lines_budget_account_cost_center_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};
