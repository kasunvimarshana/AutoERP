<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->uuid('warehouse_id');
            $table->uuid('location_id')->nullable();
            $table->decimal('quantity_on_hand', 15, 4)->default(0);
            $table->decimal('quantity_reserved', 15, 4)->default(0);
            $table->decimal('quantity_in_transit', 15, 4)->default(0);
            $table->decimal('quantity_available', 15, 4)->default(0)
                  ->comment('on_hand - reserved');
            $table->decimal('average_cost', 15, 4)->default(0);
            $table->string('unit_of_measure', 30)->default('piece');
            $table->timestamps();

            $table->unique(['tenant_id', 'product_id', 'variant_id', 'warehouse_id', 'location_id'],
                           'inventory_items_unique');
            $table->index(['product_id', 'warehouse_id']);

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('location_id')->references('id')->on('warehouse_locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
