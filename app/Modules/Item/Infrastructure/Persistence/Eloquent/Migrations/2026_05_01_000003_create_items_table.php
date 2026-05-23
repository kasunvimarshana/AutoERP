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
            $table->string('type')->default('PHYSICAL')->comment('PHYSICAL, SERVICE, DIGITAL, COMBO, VARIABLE');
            $table->string('name');
            $table->string('slug')->nullable()->comment('URL-friendly unique name indicator');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status')->default('DRAFT')->comment('DRAFT, ACTIVE, INACTIVE, DISCONTINUED');

            // Units of measure
            $table->foreignId('base_uom_id')->constrained('unit_of_measures');
            $table->foreignId('purchase_uom_id')->nullable()->constrained('unit_of_measures');
            $table->foreignId('sales_uom_id')->nullable()->constrained('unit_of_measures');

            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();

            // Inventory tracking flags – only meaningful when is_stockable = true
            $table->boolean('is_batch_tracked')->default(false);
            $table->boolean('is_lot_tracked')->default(false);
            $table->boolean('is_serial_tracked')->default(false);
            $table->boolean('is_stockable')->default(false);
            $table->string('valuation_method')->nullable()->comment('Defines the inventory cost flow assumption and stock valuation policy used for financial costing, inventory asset capitalization, and cost recognition (FIFO, LIFO, Weighted Average, Standard Cost, or Specific Identification)'); // Inventory costing strategy used to determine stock issue valuation and financial inventory asset calculation such as FIFO, LIFO, Weighted Average, Standard Cost, or Specific Identification
            $table->string('allocation_method')->nullable()->comment('Defines the proportional allocation basis used to distribute shared costs, operational charges, resources, or overhead amounts across related items or transactions'); // Cost or resource distribution strategy used to proportionally allocate shared operational, logistics, freight, service, or overhead expenses across items based on quantity, value, weight, volume, time, or percentage
            $table->decimal('standard_cost', 20, 4)->nullable()->comment('Pre-determined target baseline asset value');

            // Account references
            $table->foreignId('income_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('General ledger channel for product revenue');
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('General ledger channel for inventory direct cost');
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('General ledger channel for asset valuation');
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('General ledger channel for non-revenue overhead expenses');
            // RETURNS
            $table->foreignId('sales_return_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('Used when customer returns goods (sales return adjustment)');
            $table->foreignId('purchase_return_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('Used when returning goods to supplier');
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

            // Pricing
            $table->decimal('cost_price', 20, 4)->nullable()->comment('Actual supplier acquisition rate per piece');
            $table->decimal('sales_price', 20, 4)->nullable()->comment('Standard commercial customer checkout baseline rate');

            // Service fields
            $table->decimal('estimated_service_time_hours', 20, 4)->nullable();

            $table->string('incentive_type')->default('fixed')->nullable();   // percentage, fixed
            $table->decimal('incentive_value', 20, 4)->default(0);

            $table->decimal('minimum_stock', 20, 4)->default(0);
            $table->decimal('maximum_stock', 20, 4)->nullable();
            $table->decimal('reorder_point', 20, 4)->default(0);
            $table->decimal('reorder_quantity', 20, 4)->nullable();
            $table->decimal('safety_stock', 20, 4)->default(0);
            $table->integer('lead_time_days')->default(0);
            $table->integer('review_period_days')->default(30);
            $table->boolean('auto_replenishment_enabled')->default(false);
            $table->boolean('allow_auto_purchase_order')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name'], 'items_name_uk');
            $table->index(['tenant_id', 'type'], 'items_type_idx');
            $table->index(['tenant_id', 'is_active'], 'items_active_idx');
            $table->index(['tenant_id', 'name'], 'items_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
