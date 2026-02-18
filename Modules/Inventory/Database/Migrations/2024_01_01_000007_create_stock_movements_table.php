<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('stock_location_id')->nullable();
            $table->enum('movement_type', [
                'receipt', 'shipment', 'transfer_in', 'transfer_out',
                'adjustment_in', 'adjustment_out', 'return', 'consumption',
            ]);
            $table->string('movement_number', 50)->unique();
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 4);
            $table->char('currency', 3)->default('USD');
            $table->timestamp('movement_date');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('from_warehouse_id')->nullable();
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_warehouse_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('stock_location_id');
            $table->index('movement_type');
            $table->index('movement_number');
            $table->index('movement_date');
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('stock_location_id')->references('id')->on('stock_locations')->onDelete('set null');
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('from_location_id')->references('id')->on('stock_locations')->onDelete('set null');
            $table->foreign('to_location_id')->references('id')->on('stock_locations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
