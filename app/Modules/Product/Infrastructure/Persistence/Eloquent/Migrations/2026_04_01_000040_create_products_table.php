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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('product_brands')->nullOnDelete();
            $table->string('type')->default('physical')->comment('physical, service, digital, combo, variable');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();

            // Units of measure
            $table->foreignId('base_uom_id')->constrained('units_of_measure');
            $table->foreignId('purchase_uom_id')->nullable()->constrained('units_of_measure');
            $table->foreignId('sales_uom_id')->nullable()->constrained('units_of_measure');

            $table->foreignId('tax_group_id')->nullable();

            // Inventory tracking flags – only meaningful when is_stockable = true
            $table->boolean('is_batch_tracked')->default(false);
            $table->boolean('is_lot_tracked')->default(false);
            $table->boolean('is_serial_tracked')->default(false);
            $table->boolean('is_stockable')->default(false);

            $table->string('valuation_method')->nullable()->comment('fifo, lifo, fefo, weighted_average, standard');
            $table->decimal('standard_cost', 20, 4)->nullable();

            // Account references
            $table->foreignId('income_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('inventory_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('expense_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();

            $table->boolean('is_active')->default(true);

            // Pricing
            $table->decimal('purchase_price', 20, 4)->nullable();
            $table->decimal('sales_price', 20, 4)->nullable();

            // Service fields
            $table->decimal('estimated_service_time_hours', 8, 2)->nullable();

            $table->string('incentive_type')->default('fixed')->nullable();   // percentage, fixed
            $table->decimal('incentive_value', 10, 4)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'sku'], 'products_sku_uk');
            $table->unique(['tenant_id', 'organization_unit_id', 'slug'], 'products_slug_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'type'], 'products_type_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'is_active'], 'products_active_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'name'], 'products_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
