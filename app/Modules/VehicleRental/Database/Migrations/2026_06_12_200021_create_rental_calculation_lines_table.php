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
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'rental_calculation_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('calculation_run_id');
            $table->unsignedInteger('line_number');
            $table->foreignId('usage_context_id')->nullable();
            $table->foreignId('expense_allocation_id')->nullable();
            $table->foreignId('custody_event_item_id')->nullable();
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
            $table->foreignId('tax_group_id')->nullable();
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
            $table->index(['usage_context_id', 'component_code'], 'rental_calculation_lines_usage_component_ix');
            $table->index(['source_type', 'source_id'], 'rental_calculation_lines_source_ix');

            $table->unique(['id', 'tenant_id'], 'rental_calculation_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_calculation_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['calculation_run_id', 'tenant_id'], 'rental_calculation_lines_calculation_run_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_calculation_runs')
                ->cascadeOnDelete();
            $table->foreign(['usage_context_id', 'tenant_id'], 'rental_calculation_lines_usage_context_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_usage_contexts')
                ->restrictOnDelete();
            $table->foreign(['expense_allocation_id', 'tenant_id'], 'rental_calculation_lines_expense_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_expense_allocations')
                ->restrictOnDelete();
            $table->foreign(['custody_event_item_id', 'tenant_id'], 'rental_calculation_lines_custody_event_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_custody_event_items')
                ->restrictOnDelete();
            $table->foreign(['tax_group_id', 'tenant_id'], 'rental_calculation_lines_tax_group_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_groups')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'rental_calculation_lines_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_calculation_lines_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_calculation_lines');
    }
};
