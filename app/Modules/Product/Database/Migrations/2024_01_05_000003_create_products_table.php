<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('category_id')->nullable()->constrained('product_categories');
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('description')->nullable();
            $table->enum('type', ['physical', 'service', 'digital', 'combo'])->default('physical');
            $table->boolean('is_variable')->default(false);
            $table->unsignedBigInteger('parent_product_id')->nullable();
            
            // UOM Configuration
            $table->foreignId('base_uom_id')->constrained('uoms');
            $table->foreignId('purchase_uom_id')->constrained('uoms');
            $table->foreignId('sale_uom_id')->constrained('uoms');
            $table->foreignId('inventory_uom_id')->constrained('uoms');
            
            // Tracking
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_lot')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->boolean('track_expiry')->default(false);
            
            // Valuation
            $table->decimal('standard_cost', 15, 4)->nullable();
            $table->enum('cost_method', ['standard', 'actual', 'weighted_average'])->default('actual');
            
            // GS1
            $table->string('gtin')->nullable();
            $table->string('gln')->nullable();
            $table->json('gs1_metadata')->nullable();
            
            // Constraints
            $table->decimal('reorder_level', 15, 4)->default(0);
            $table->decimal('reorder_quantity', 15, 4)->default(0);
            $table->decimal('max_stock', 15, 4)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['company_id', 'sku']);
            $table->foreign('parent_product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }
};