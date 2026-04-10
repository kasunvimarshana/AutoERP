<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_number')->unique();
            $table->enum('transaction_type', ['purchase', 'sales', 'return', 'return_in', 'return_out', 'adjustment', 'transfer', 'scrap', 'cycle_count', 'revaluation']);
            $table->nullableMorphs('reference'); // reference to purchase_order, sales_order, etc.
            $table->enum('movement_type', ['inbound', 'outbound', 'transfer']);
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->uuid('from_warehouse_id')->nullable();
            $table->uuid('to_warehouse_id')->nullable();
            $table->uuid('from_location_id')->nullable();
            $table->uuid('to_location_id')->nullable();
            // $table->uuid('warehouse_id');
            // $table->uuid('location_id')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->uuid('serial_number_id')->nullable();
            $table->decimal('quantity', 20, 10);
            $table->uuid('uom_id');
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->decimal('total_cost', 20, 6)->nullable();
            $table->uuid('valuation_layer_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->json('reference_data')->nullable();
            $table->text('reason')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('transaction_date');
            $table->timestamps();
            $table->softDeletes();
            

            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('serial_number_id')->references('id')->on('serial_numbers');
            $table->foreign('uom_id')->references('id')->on('uoms');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants');
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses');
            $table->foreign('from_location_id')->references('id')->on('locations');
            $table->foreign('to_location_id')->references('id')->on('locations');
            $table->foreign('batch_id')->references('id')->on('batches');
            $table->foreign('uom_id')->references('id')->on('uoms');
            $table->index(['transaction_type', 'transaction_date', 'product_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['batch_id', 'movement_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_transactions');
    }
}