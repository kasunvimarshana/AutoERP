<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('barcode')->nullable()->unique();
            $table->enum('type', ['physical', 'service', 'digital', 'combo', 'variable'])->default('physical');
            $table->string('sku')->unique();
            $table->string('gtin', 14)->nullable()->index(); // GS1 GTIN-14
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_variable')->default(false);
            $table->uuid('parent_id')->nullable();
            $table->uuid('uom_id'); // inventory UOM
            $table->foreign('uom_id')->references('id')->on('uoms');
            $table->boolean('is_tracked')->default(true);
            $table->boolean('is_serialized')->default(false);
            $table->boolean('is_batch_tracked')->default(false);
            $table->boolean('is_lot_controlled')->default(false);
            $table->boolean('is_expirable')->default(false);
            $table->boolean('is_expiry_tracked')->default(false);
            $table->unsignedInteger('shelf_life_days')->nullable();
            $table->uuid('default_uom_id');
            $table->foreignId('base_uom_id')->constrained('unit_of_measures');
            $table->foreignId('purchase_uom_id')->constrained('unit_of_measures');
            $table->foreignId('sales_uom_id')->constrained('unit_of_measures');
            $table->enum('valuation_method', ['fifo', 'lifo', 'weighted_avg', 'specific'])->nullable();
            $table->enum('stock_rotation_strategy', ['fefo', 'fifo', 'lifo', 'nearest_expiry'])->nullable();
            $table->enum('allocation_algorithm', ['fifo', 'fefo', 'lifo', 'nearest_expiry', 'manual'])->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->decimal('weight', 20, 6)->nullable();
            $table->decimal('volume', 20, 6)->nullable();
            $table->json('dimensions')->nullable();
            $table->json('attributes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('parent_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('default_uom_id')->references('id')->on('uoms');
            $table->index(['sku', 'type', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}