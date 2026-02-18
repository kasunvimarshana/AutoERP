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
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('product_type', 50)->default('inventory'); // inventory, service, bundle, composite
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->unsignedBigInteger('base_uom_id')->nullable();

            // Tracking flags
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_batches')->default(false);
            $table->boolean('track_serials')->default(false);
            $table->boolean('has_expiry')->default(false);

            // Reorder management
            $table->decimal('reorder_level', 15, 2)->nullable();
            $table->decimal('reorder_quantity', 15, 2)->nullable();

            // Costing
            $table->string('cost_method', 50)->default('average'); // fifo, lifo, average, standard
            $table->decimal('standard_cost', 15, 4)->nullable();
            $table->decimal('last_purchase_cost', 15, 4)->nullable();
            $table->decimal('average_cost', 15, 4)->nullable();

            // Pricing
            $table->decimal('selling_price', 15, 4)->nullable();

            // Status
            $table->string('status', 50)->default('active'); // active, inactive, discontinued

            // Additional attributes
            $table->json('custom_attributes')->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('brand')->nullable();

            // Dimensions
            $table->decimal('weight', 10, 4)->nullable();
            $table->string('weight_uom', 20)->nullable();
            $table->decimal('length', 10, 4)->nullable();
            $table->decimal('width', 10, 4)->nullable();
            $table->decimal('height', 10, 4)->nullable();
            $table->string('dimension_uom', 20)->nullable();

            // Media
            $table->string('image_url')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index('sku');
            $table->index('barcode');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
