<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('stock_location_id')->nullable();
            $table->decimal('quantity_on_hand', 15, 3)->default(0);
            $table->decimal('quantity_reserved', 15, 3)->default(0);
            $table->decimal('quantity_available', 15, 3)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->enum('valuation_method', ['fifo', 'lifo', 'average'])->default('fifo');
            $table->timestamp('last_recount_date')->nullable();
            $table->timestamp('last_movement_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('stock_location_id');
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'warehouse_id']);
            $table->unique(['product_id', 'warehouse_id', 'stock_location_id'], 'unique_stock_level');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('stock_location_id')->references('id')->on('stock_locations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
