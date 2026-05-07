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

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('category_id')->nullable()->constrained('product_categories', 'id', 'products_category_id_fk')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('product_brands', 'id', 'products_brand_id_fk')->nullOnDelete();
            $table->enum('type', ['physical', 'service', 'digital', 'combo', 'variable'])
                  ->default('physical')
                  ->comment('physical=goods, service=labour, digital, combo=bundle, variable=has variants');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();

            // Unit of measure
            $table->foreignId('base_uom_id')->constrained('units_of_measure', 'id', 'products_base_uom_id_fk');
            $table->foreignId('purchase_uom_id')->nullable()->constrained('units_of_measure', 'id', 'products_purchase_uom_id_fk');
            $table->foreignId('sales_uom_id')->nullable()->constrained('units_of_measure', 'id', 'products_sales_uom_id_fk');

            $table->foreignId('tax_group_id')->nullable();

            // Inventory tracking fields – only meaningful when is_stockable = true
            $table->boolean('is_batch_tracked')->default(false);
            $table->boolean('is_lot_tracked')->default(false);
            $table->boolean('is_serial_tracked')->default(false);

            $table->boolean('is_stockable')
                  ->default(false)
                  ->comment('TRUE = inventory item (generates stock movements), FALSE = non‑inventory');
            // $table->boolean('is_stockable')
            //       ->storedAs('inventory_account_id IS NOT NULL')
            //       ->comment('TRUE = inventory item (generates stock movements), FALSE = non‑inventory');

            $table->enum('valuation_method', ['fifo', 'lifo', 'fefo', 'weighted_average', 'standard'])->default('fifo');
            $table->decimal('standard_cost', 20, 6)->nullable();
            // Products account references
            $table->foreignId('income_account_id')->nullable(); // will reference accounts later
            $table->foreignId('cogs_account_id')->nullable();
            $table->foreignId('inventory_account_id')->nullable();
            $table->foreignId('expense_account_id')->nullable();

            // Accounting – among these, inventory_account_id is the decisive one
            $table->foreign('income_account_id', 'products_income_account_id_fk')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('cogs_account_id', 'products_cogs_account_id_fk')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('inventory_account_id', 'products_inventory_account_id_fk')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('expense_account_id', 'products_expense_account_id_fk')->references('id')->on('accounts')->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();

            // Pricing
            $table->decimal('purchase_price', 20, 6)->nullable();
            $table->decimal('sales_price', 20, 6)->nullable();

            $table->foreign('tax_group_id')->references('id')->on('tax_groups')->nullOnDelete();

            $table->decimal('estimated_service_time_hours', 8, 2)
                ->nullable()
                ->comment('Standard duration for labour operations');

            $table->enum('incentive_type', ['percentage', 'fixed'])
                ->default('fixed')
                ->nullable()
                ->comment('% of sales price, or a flat amount per unit');
            $table->decimal('incentive_value', 10, 6)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'sku'], 'products_tenant_sku_uk');
            $table->index(['tenant_id', 'type'], 'products_tenant_type_idx');
            $table->unique(['tenant_id', 'org_unit_id', 'slug'], 'products_tenant_slug_uk');
            $table->index(['tenant_id', 'is_active'], 'products_tenant_active_idx');
            $table->index(['tenant_id', 'name'], 'products_tenant_name_idx');
        });

        // // Service items must NEVER receive an inventory account – this is the real enforcement
        // DB::statement("ALTER TABLE products ADD CONSTRAINT chk_service_no_inventory_account
        //     CHECK (product_type != 'service' OR inventory_account_id IS NULL)");

        // // If you also want to keep the full inventory‑column guard (optional but safe)
        // DB::statement("ALTER TABLE products ADD CONSTRAINT chk_service_inventory_fields
        //     CHECK (product_type != 'service' OR (
        //         is_batch_tracked = 0 AND
        //         is_lot_tracked = 0 AND
        //         is_serial_tracked = 0 AND
        //         valuation_method IS NULL AND
        //         standard_cost IS NULL
        //     ))");
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
