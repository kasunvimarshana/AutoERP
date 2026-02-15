<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('slug')->index();
            $table->text('description')->nullable();
            $table->enum('type', ['inventory', 'service', 'combo', 'bundle', 'digital'])->default('inventory');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_purchasable')->default(true);
            $table->boolean('is_sellable')->default(true);
            
            // Pricing
            $table->decimal('buying_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('mrp', 15, 2)->nullable(); // Maximum Retail Price
            $table->decimal('wholesale_price', 15, 2)->nullable();
            
            // Units
            $table->foreignId('buying_unit_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('selling_unit_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('stock_unit_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->decimal('unit_conversion_factor', 10, 4)->default(1.0000);
            
            // Discount and Margin
            $table->enum('buying_discount_type', ['none', 'flat', 'percentage'])->default('none');
            $table->decimal('buying_discount_value', 10, 2)->default(0);
            $table->enum('selling_discount_type', ['none', 'flat', 'percentage'])->default('none');
            $table->decimal('selling_discount_value', 10, 2)->default(0);
            $table->enum('profit_margin_type', ['flat', 'percentage'])->default('percentage');
            $table->decimal('profit_margin_value', 10, 2)->default(0);
            
            // Taxation
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->boolean('is_tax_inclusive')->default(false);
            
            // Inventory Management
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_serial')->default(false);
            $table->boolean('track_batch')->default(false);
            $table->boolean('has_expiry')->default(false);
            $table->integer('expiry_alert_days')->nullable();
            $table->enum('valuation_method', ['fifo', 'fefo', 'lifo', 'average'])->default('fifo');
            
            // Stock Levels
            $table->decimal('min_stock_level', 15, 4)->default(0);
            $table->decimal('max_stock_level', 15, 4)->nullable();
            $table->decimal('reorder_level', 15, 4)->default(0);
            $table->decimal('reorder_quantity', 15, 4)->default(0);
            
            // Physical Attributes
            $table->decimal('weight', 10, 4)->nullable();
            $table->string('weight_unit', 10)->nullable();
            $table->decimal('length', 10, 4)->nullable();
            $table->decimal('width', 10, 4)->nullable();
            $table->decimal('height', 10, 4)->nullable();
            $table->string('dimension_unit', 10)->nullable();
            
            // Additional Info
            $table->string('barcode')->nullable()->index();
            $table->string('manufacturer')->nullable();
            $table->string('brand')->nullable();
            $table->string('model_number')->nullable();
            $table->string('warranty_period')->nullable();
            $table->text('tags')->nullable();
            $table->json('images')->nullable();
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
            $table->unique(['tenant_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
