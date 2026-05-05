<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'products_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories', 'id', 'products_category_id_fk')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('product_brands', 'id', 'products_brand_id_fk')->nullOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id', 'products_org_unit_id_fk')->nullOnDelete();
            $table->enum('type', ['physical', 'service', 'digital', 'combo', 'variable'])->default('physical');
            $table->string('name');
            $table->string('slug');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->foreignId('base_uom_id')->constrained('units_of_measure', 'id', 'products_base_uom_id_fk');
            $table->foreignId('purchase_uom_id')->nullable()->constrained('units_of_measure', 'id', 'products_purchase_uom_id_fk');
            $table->foreignId('sales_uom_id')->nullable()->constrained('units_of_measure', 'id', 'products_sales_uom_id_fk');
            $table->foreignId('tax_group_id')->nullable();
            $table->decimal('uom_conversion_factor', 20, 10)->default(1);
            $table->boolean('is_batch_tracked')->default(false);
            $table->boolean('is_lot_tracked')->default(false);
            $table->boolean('is_serial_tracked')->default(false);
            $table->enum('valuation_method', ['fifo', 'lifo', 'fefo', 'weighted_average', 'standard'])->default('fifo');
            $table->decimal('standard_cost', 20, 6)->nullable();
            // Products account references
            $table->foreignId('income_account_id')->nullable(); // will reference accounts later
            $table->foreignId('cogs_account_id')->nullable();
            $table->foreignId('inventory_account_id')->nullable();
            $table->foreignId('expense_account_id')->nullable();
            $table->foreign('income_account_id', 'products_income_account_id_fk')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('cogs_account_id', 'products_cogs_account_id_fk')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('inventory_account_id', 'products_inventory_account_id_fk')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('expense_account_id', 'products_expense_account_id_fk')->references('id')->on('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->decimal('purchase_price', 20, 6)->nullable();
            $table->decimal('sales_price', 20, 6)->nullable();

            $table->foreign('tax_group_id')->references('id')->on('tax_groups')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku'], 'products_tenant_sku_uk');
            $table->index(['tenant_id', 'type'], 'products_tenant_type_idx');
            $table->unique(['tenant_id', 'slug'], 'products_tenant_slug_uk');
            $table->index(['tenant_id', 'is_active'], 'products_tenant_active_idx');
            $table->index(['tenant_id', 'name'], 'products_tenant_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
