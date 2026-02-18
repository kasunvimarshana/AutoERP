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
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['inventory', 'service', 'bundle', 'composite'])->default('inventory');
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            
            // Category and classification
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->string('brand')->nullable();
            
            // Pricing
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            
            // Units
            $table->string('unit_of_measure')->default('unit');
            $table->decimal('unit_weight', 10, 3)->nullable();
            $table->string('weight_unit')->nullable();
            
            // Inventory control
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->decimal('min_stock_level', 10, 2)->default(0);
            $table->decimal('max_stock_level', 10, 2)->default(0);
            $table->decimal('reorder_point', 10, 2)->default(0);
            
            // Taxation
            $table->boolean('is_taxable')->default(true);
            $table->string('tax_category')->nullable();
            
            // Images and attachments
            $table->json('images')->nullable();
            $table->json('attachments')->nullable();
            
            // Custom attributes
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'category']);
            $table->index('type');
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
