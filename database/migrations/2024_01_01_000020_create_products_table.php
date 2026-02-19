<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('symbol', 20);
            $table->string('type')->default('quantity'); // quantity, weight, volume, length, time
            $table->decimal('conversion_factor', 20, 8)->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'symbol']);
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('type'); // goods, service, digital, bundle, composite
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_purchasable')->default(true);
            $table->boolean('is_saleable')->default(true);
            $table->boolean('is_trackable')->default(true);
            $table->foreignUuid('buy_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignUuid('sell_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('buy_unit_cost', 20, 8)->default(0);
            $table->decimal('base_price', 20, 8)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('lock_version')->default(0); // optimistic locking
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'type', 'is_active']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->decimal('price_adjustment', 20, 8)->default(0);
            $table->decimal('cost_adjustment', 20, 8)->default(0);
            $table->json('attributes')->nullable(); // {"color": "red", "size": "L"}
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
        });

        Schema::create('product_bundles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bundle_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('component_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 20, 8)->default(1);
            $table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->timestamps();

            $table->unique(['bundle_product_id', 'component_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bundles');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('units');
    }
};
