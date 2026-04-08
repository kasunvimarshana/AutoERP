<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('category_id')->nullable();
            $table->string('sku', 100)->unique();
            $table->string('barcode', 100)->nullable()->index();
            $table->string('name', 300);
            $table->text('description')->nullable();
            // physical, service, digital, combo, variable
            $table->string('type', 30)->default('physical');
            // active, inactive, discontinued
            $table->string('status', 30)->default('active');
            $table->string('unit_of_measure', 30)->default('piece');
            $table->decimal('weight', 10, 4)->nullable();
            $table->string('weight_unit', 10)->nullable();
            $table->decimal('dimensions_length', 10, 4)->nullable();
            $table->decimal('dimensions_width', 10, 4)->nullable();
            $table->decimal('dimensions_height', 10, 4)->nullable();
            $table->string('dimensions_unit', 10)->nullable();
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->decimal('selling_price', 15, 4)->default(0);
            $table->string('currency_code', 10)->default('USD');
            $table->string('tax_class', 50)->nullable();
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_trackable')->default(true);
            $table->boolean('is_purchasable')->default(true);
            $table->boolean('is_sellable')->default(true);
            $table->decimal('min_stock_level', 15, 4)->default(0);
            $table->decimal('max_stock_level', 15, 4)->nullable();
            $table->decimal('reorder_point', 15, 4)->default(0);
            $table->decimal('reorder_quantity', 15, 4)->default(0);
            $table->integer('lead_time_days')->default(0);
            $table->string('image_path', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);

            $table->foreign('category_id')->references('id')->on('product_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
