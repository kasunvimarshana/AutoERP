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
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('type'); // inventory, service, combo, bundle
            $table->text('description')->nullable();
            $table->string('barcode')->nullable()->unique();

            // Pricing
            $table->decimal('buying_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('mrp', 15, 2)->nullable(); // Maximum Retail Price

            // Inventory tracking
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->boolean('track_expiry')->default(false);

            // Stock levels
            $table->decimal('reorder_level', 15, 2)->nullable();
            $table->decimal('min_stock_level', 15, 2)->nullable();
            $table->decimal('max_stock_level', 15, 2)->nullable();

            // Categories and classification
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('brand_id')->nullable()->constrained()->onDelete('set null');

            // Units
            $table->foreignId('unit_id')->nullable()->constrained()->onDelete('set null');

            // Tax
            $table->foreignId('tax_id')->nullable()->constrained()->onDelete('set null');

            // Status
            $table->boolean('is_active')->default(true);

            // Additional attributes
            $table->json('attributes')->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
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
