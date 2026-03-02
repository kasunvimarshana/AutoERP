<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('uom_id');
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity_on_hand', 20, 4);
            $table->decimal('quantity_reserved', 20, 4)->default(0);
            $table->decimal('quantity_available', 20, 4);
            $table->string('costing_method'); // fifo/lifo/weighted_average
            $table->decimal('cost_price', 20, 4);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'warehouse_id', 'stock_location_id', 'product_id', 'batch_number', 'serial_number'],
                'stock_items_unique'
            );
            // Composite index for FEFO pharmaceutical compliance queries:
            // getStockByFEFO() filters by product_id + warehouse_id, orders by expiry_date ASC.
            $table->index(['product_id', 'warehouse_id', 'expiry_date'], 'stock_items_fefo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
