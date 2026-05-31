<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('category_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('item_brands')->nullOnDelete();
            $table->foreignId('item_type_id')->nullable()->constrained('item_types')->nullOnDelete();
            $table->string('type')->default('inventory_product')->comment('Generic item classification such as inventory_product, service, labour, non_inventory, combo, rental_charge, external_service, or customer_supplied');
            $table->string('name');
            $table->string('slug')->nullable()->comment('URL-friendly unique name indicator');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status')->default('DRAFT')->comment('DRAFT, ACTIVE, INACTIVE, DISCONTINUED');

            // Units of measure. Context-specific defaults stay generic; business modules decide how to use them.
            $table->foreignId('base_uom_id')->constrained('unit_of_measures');
            $table->foreignId('default_receipt_uom_id')->nullable()->constrained('unit_of_measures');
            $table->foreignId('default_issue_uom_id')->nullable()->constrained('unit_of_measures');
            $table->foreignId('default_consumption_uom_id')->nullable()->constrained('unit_of_measures');
            $table->foreignId('default_charge_uom_id')->nullable()->constrained('unit_of_measures');

            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();

            // Inventory tracking flags - only meaningful when is_stockable is true
            $table->boolean('is_batch_tracked')->default(false);
            $table->boolean('is_lot_tracked')->default(false);
            $table->boolean('is_serial_tracked')->default(false);
            $table->boolean('is_stockable')->default(false);
            $table->boolean('is_purchasable')->default(true);
            $table->boolean('is_sellable')->default(true);
            $table->boolean('is_service')->default(false);
            $table->boolean('is_rentable')->default(false);
            $table->boolean('is_chargeable')->default(false);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_variable')->default(false);
            $table->string('valuation_method')->nullable()->comment('Defines the inventory cost flow assumption and stock valuation policy used for financial costing, inventory asset capitalization, and cost recognition (FIFO, LIFO, Weighted Average, Standard Cost, or Specific Identification)'); // Inventory costing strategy used to determine stock issue valuation and financial inventory asset calculation such as FIFO, LIFO, Weighted Average, Standard Cost, or Specific Identification
            $table->string('allocation_method')->nullable()->comment('Defines the proportional allocation basis used to distribute shared costs, operational charges, resources, or overhead amounts across related items or transactions'); // Cost or resource distribution strategy used to proportionally allocate shared operational, logistics, freight, service, or overhead expenses across items based on quantity, value, weight, volume, time, or percentage
            $table->decimal('standard_cost', 20, 4)->nullable()->comment('Pre-determined target baseline asset value');

            // Account references
            $table->foreignId('income_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('General ledger channel for product revenue');
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('General ledger channel for inventory direct cost');
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('General ledger channel for asset valuation');
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('General ledger channel for non-revenue overhead expenses');
            $table->foreignId('return_in_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('Generic return receipt account mapping');
            $table->foreignId('return_out_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('Generic return issue account mapping');
            // INVENTORY VARIANCE (VERY IMPORTANT FOR COUNT ADJUSTMENT)
            $table->foreignId('inventory_gain_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('Used when stock increases due to adjustments');
            $table->foreignId('inventory_loss_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('Used when stock decreases due to damage, scrap, or shrinkage');
            // STOCK TRANSFER (OPTIONAL BUT PROFESSIONAL)
            $table->foreignId('stock_transfer_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('Used when a transfer requires a temporary clearing account');
            // PRODUCTION / WIP (if manufacturing module exists)
            $table->foreignId('wip_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('Work In Progress account for production consumption');
            // DISCOUNT / PRICE DIFFERENCE HANDLING
            $table->foreignId('price_variance_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('Used when cost vs invoice price mismatch occurs');

            $table->boolean('is_active')->default(true);

            $table->foreignId('default_currency_id')->nullable()->constrained('currencies')->nullOnDelete();

            $table->decimal('minimum_stock', 20, 4)->default(0);
            $table->decimal('maximum_stock', 20, 4)->nullable();
            $table->decimal('reorder_point', 20, 4)->default(0);
            $table->decimal('reorder_quantity', 20, 4)->nullable();
            $table->decimal('safety_stock', 20, 4)->default(0);
            $table->integer('lead_time_days')->default(0);
            $table->integer('review_period_days')->default(30);
            $table->boolean('auto_replenishment_enabled')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name'], 'items_name_uk');
            $table->unique(['tenant_id', 'sku'], 'items_tenant_sku_uk');
            $table->index(['tenant_id', 'type'], 'items_type_idx');
            $table->index(['tenant_id', 'is_active'], 'items_active_idx');
            $table->index(['tenant_id', 'name'], 'items_name_idx');
            $table->index(['tenant_id', 'barcode'], 'items_tenant_barcode_idx');
            $table->index(['tenant_id', 'item_type_id'], 'items_tenant_item_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
