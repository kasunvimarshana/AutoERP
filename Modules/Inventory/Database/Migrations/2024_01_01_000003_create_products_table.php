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
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_category_id');
            $table->unsignedBigInteger('unit_of_measure_id');
            $table->string('sku', 50)->unique();
            $table->string('barcode', 50)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('product_type', ['stockable', 'consumable', 'service'])->default('stockable');
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->decimal('list_price', 15, 2);
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->enum('dimension_unit', ['cm', 'm', 'in', 'ft'])->nullable();
            $table->enum('weight_unit', ['g', 'kg', 'oz', 'lb'])->nullable();
            $table->integer('reorder_level')->nullable();
            $table->integer('reorder_quantity')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->integer('shelf_life_days')->nullable();
            $table->boolean('is_serialized')->default(false);
            $table->boolean('is_batch_tracked')->default(false);
            $table->string('image_url')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('product_category_id');
            $table->index('unit_of_measure_id');
            $table->index('sku');
            $table->index('barcode');
            $table->index('status');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'product_type']);
            $table->foreign('product_category_id')->references('id')->on('product_categories');
            $table->foreign('unit_of_measure_id')->references('id')->on('unit_of_measures');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
