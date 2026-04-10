<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryAdjustmentsTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('adjustment_number')->unique();
            $table->enum('adjustment_type', ['positive', 'negative']);
            // $table->enum('reason', ['damage', 'loss', 'theft', 'expired', 'quality', 'cycle_count', 'other']);
            $table->string('reason');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->uuid('warehouse_id');
            $table->uuid('location_id')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->uuid('serial_number_id')->nullable();
            $table->foreign('serial_number_id')->references('id')->on('serial_numbers');
            $table->decimal('quantity', 20, 10);
            $table->uuid('uom_id');
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->decimal('total_value', 20, 6)->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('batch_id')->references('id')->on('batches');
            $table->foreign('uom_id')->references('id')->on('uoms');
            $table->index(['product_id', 'warehouse_id', 'adjustment_type']);
            $table->index(['batch_id', 'reason']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_adjustments');
    }
}