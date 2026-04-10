<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseReceiptsTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id');
            $table->string('receipt_number')->unique();
            $table->timestamp('received_at');
            $table->date('receipt_date');
            $table->uuid('warehouse_id');
            $table->uuid('location_id')->nullable();
            $table->enum('status', ['draft', 'inspection', 'completed', 'cancelled']);
            $table->text('notes')->nullable();
            $table->json('inspection_results')->nullable();
            $table->uuid('received_by');
            $table->uuid('inspected_by')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->index(['receipt_number', 'purchase_order_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_receipts');
    }
}