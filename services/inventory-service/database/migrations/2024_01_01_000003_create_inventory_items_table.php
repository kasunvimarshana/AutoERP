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
            $table->uuid('product_id');
            $table->string('tenant_id')->index();
            $table->uuid('warehouse_id')->nullable();
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_sold')->default(0);
            $table->integer('reorder_level')->default(10);
            $table->integer('max_stock_level')->default(1000);
            $table->string('unit_of_measure', 50)->default('unit');
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('set null');

            // Prevent duplicate inventory records per product/warehouse combination.
            $table->unique(['product_id', 'warehouse_id']);

            $table->index(['tenant_id', 'warehouse_id']);
            $table->index('quantity_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
