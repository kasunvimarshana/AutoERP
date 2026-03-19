<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->index();
            $table->nestedSet(); // For hierarchical categories
            $table->timestamps();
        });

        // 2. Units of Measure (UOM)
        Schema::create('uoms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->index(); // e.g., PCS, KG, BOX
            $table->timestamps();
        });

        // 3. Products
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['PHYSICAL', 'SERVICE', 'DIGITAL', 'BUNDLE', 'COMPOSITE', 'VARIANT'])->default('PHYSICAL');
            $table->foreignUuid('category_id')->nullable()->constrained();
            $table->foreignUuid('base_uom_id')->constrained('uoms');
            $table->foreignUuid('buying_uom_id')->nullable()->constrained('uoms');
            $table->foreignUuid('selling_uom_id')->nullable()->constrained('uoms');
            $table->decimal('base_selling_price', 20, 4)->default(0); // BCMath precision
            $table->decimal('base_buying_cost', 20, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_trackable')->default(true); // Inventory tracking enabled
            $table->json('metadata')->nullable(); // Dynamic attributes
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']); // SKU unique per tenant
        });

        // 4. UOM Conversion Matrix
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('from_uom_id')->constrained('uoms');
            $table->foreignUuid('to_uom_id')->constrained('uoms');
            $table->decimal('factor', 20, 10); // e.g., 1 BOX = 12 PCS
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
        Schema::dropIfExists('products');
        Schema::dropIfExists('uoms');
        Schema::dropIfExists('categories');
    }
};
