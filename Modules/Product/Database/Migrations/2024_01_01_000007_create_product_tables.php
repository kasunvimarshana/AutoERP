<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('symbol');
            $table->string('type');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'symbol']);
            $table->index('tenant_id');
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('product_categories')->onDelete('cascade');
            $table->unique(['tenant_id', 'code']);
            $table->index('tenant_id');
            $table->index('parent_id');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('code');
            $table->string('type');
            $table->text('description')->nullable();
            $table->uuid('category_id')->nullable();
            $table->uuid('buying_unit_id')->nullable();
            $table->uuid('selling_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('product_categories')->onDelete('set null');
            $table->foreign('buying_unit_id')->references('id')->on('units')->onDelete('set null');
            $table->foreign('selling_unit_id')->references('id')->on('units')->onDelete('set null');

            $table->unique(['tenant_id', 'code']);
            $table->index('tenant_id');
            $table->index('type');
            $table->index('category_id');
        });

        Schema::create('product_unit_conversions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');
            $table->uuid('from_unit_id');
            $table->uuid('to_unit_id');
            $table->decimal('conversion_factor', 20, 10);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('from_unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('to_unit_id')->references('id')->on('units')->onDelete('cascade');

            $table->index('tenant_id');
            $table->index('product_id');
        });

        Schema::create('product_bundles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('bundle_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 20, 10);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('bundle_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->index('tenant_id');
            $table->index('bundle_id');
        });

        Schema::create('product_composites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('composite_id');
            $table->uuid('component_id');
            $table->decimal('quantity', 20, 10);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('composite_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('component_id')->references('id')->on('products')->onDelete('cascade');

            $table->index('tenant_id');
            $table->index('composite_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_composites');
        Schema::dropIfExists('product_bundles');
        Schema::dropIfExists('product_unit_conversions');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('units');
    }
};
