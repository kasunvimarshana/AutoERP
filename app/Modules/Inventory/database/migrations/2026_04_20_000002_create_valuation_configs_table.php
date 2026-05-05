<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the valuation_configs table.
 *
 * This table stores per-scope valuation method and allocation strategy
 * configurations.  Scope precedence (highest → lowest):
 *   product_id > warehouse_id > org_unit_id > tenant-only (all nulls)
 *
 * The optional transaction_type column further narrows the scope to a
 * specific movement type (e.g. 'receipt', 'shipment').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valuation_configs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id', 'valuation_configs_tenant_id_fk')
                ->cascadeOnDelete();

            // Scope columns — all nullable; a null value means "any"
            $table->foreignId('org_unit_id')
                ->nullable()
                ->constrained('org_units', 'id', 'valuation_configs_org_unit_id_fk')
                ->nullOnDelete();

            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->foreign('warehouse_id', 'valuation_configs_warehouse_id_fk')
                ->references('id')->on('warehouses')->nullOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products', 'id', 'valuation_configs_product_id_fk')
                ->nullOnDelete();

            // Optional movement-type narrowing
            $table->string('transaction_type', 50)->nullable();

            // Strategy configuration
            $table->enum('valuation_method', [
                'fifo', 'lifo', 'fefo', 'weighted_average', 'standard', 'specific',
            ])->default('fifo');

            $table->enum('allocation_strategy', [
                'fifo', 'lifo', 'fefo', 'nearest_bin', 'manual',
            ])->default('fifo');

            $table->boolean('is_active')->default(true);

            $table->json('metadata')->nullable();

            $table->timestamps();

            // Unique scope constraint: prevents duplicate configs for the same
            // tenant + product + warehouse + org_unit + transaction_type combo.
            $table->unique(
                ['tenant_id', 'product_id', 'warehouse_id', 'org_unit_id', 'transaction_type'],
                'valuation_configs_scope_uk',
            );

            $table->index(['tenant_id', 'is_active'], 'valuation_configs_tenant_active_idx');
            $table->index(['tenant_id', 'product_id'], 'valuation_configs_tenant_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valuation_configs');
    }
};
