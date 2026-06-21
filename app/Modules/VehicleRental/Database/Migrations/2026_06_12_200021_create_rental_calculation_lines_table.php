<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_calculation_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('calculation_run_id')->constrained('rental_calculation_runs')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('usage_context_id')->nullable()->constrained('rental_usage_contexts')->nullOnDelete();
            $table->foreignId('expense_allocation_id')->nullable()->constrained('rental_expense_allocations')->nullOnDelete();
            $table->foreignId('custody_event_item_id')->nullable()->constrained('rental_custody_event_items')->nullOnDelete();
            $table->string('source_type', 80);
            $table->unsignedBigInteger('source_id');
            $table->string('component_code', 50);
            $table->text('description');
            $table->decimal('measured_quantity', 20, 6)->default('0.000000');
            $table->decimal('allowed_quantity', 20, 6)->default('0.000000');
            $table->decimal('chargeable_quantity', 20, 6)->default('0.000000');
            $table->string('unit', 30)->nullable();
            $table->decimal('rate', 20, 6)->default('0.000000');
            $table->decimal('multiplier', 20, 6)->default('1.000000');
            $table->decimal('net_amount', 20, 6)->default('0.000000');
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('withholding_amount', 20, 6)->default('0.000000');
            $table->decimal('total_amount', 20, 6)->default('0.000000');
            $table->string('applied_rule', 100);
            $table->json('rule_snapshot')->nullable();
            $table->char('fingerprint', 64);
            $table->string('status', 30)->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['calculation_run_id', 'line_number'], 'rental_calculation_lines_run_line_uk');
            $table->unique(['tenant_id', 'fingerprint'], 'rental_calculation_lines_fingerprint_uk');
            $table->index(['usage_context_id', 'component_code'], 'rental_calculation_lines_usage_component_idx');
            $table->index(['source_type', 'source_id'], 'rental_calculation_lines_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_calculation_lines');
    }
};
