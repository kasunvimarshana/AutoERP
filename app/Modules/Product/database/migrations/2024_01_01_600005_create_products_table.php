<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->enum('type', ['physical', 'service', 'digital', 'combo', 'variable'])->default('physical');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('base_uom_id')->constrained('units_of_measure');
            $table->foreignId('purchase_uom_id')->nullable()->constrained('units_of_measure');
            $table->foreignId('sales_uom_id')->nullable()->constrained('units_of_measure');
            $table->decimal('uom_conversion_factor', 20, 10)->default(1); // base ↔ purchase/sales
            $table->boolean('is_batch_tracked')->default(false);
            $table->boolean('is_lot_tracked')->default(false);
            $table->boolean('is_serial_tracked')->default(false);
            $table->enum('valuation_method', ['fifo', 'lifo', 'fefo', 'weighted_average', 'standard'])->default('fifo');
            $table->decimal('standard_cost', 15, 4)->nullable();
            // Products account references
            $table->foreignId('income_account_id')->nullable(); // will reference accounts later
            $table->foreignId('cogs_account_id')->nullable();
            $table->foreignId('inventory_account_id')->nullable();
            $table->foreignId('expense_account_id')->nullable();
            $table->foreign('income_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('cogs_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('inventory_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('expense_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku'], 'uq_products_tenant_sku');
            $table->index(['tenant_id', 'type'], 'idx_products_tenant_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};