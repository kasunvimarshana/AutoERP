<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseReceiptItemsTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_receipt_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('receipt_id');
            $table->uuid('goods_receipt_id');
            $table->uuid('purchase_order_item_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->decimal('quantity', 20, 10);
            $table->decimal('quantity_received', 20, 10);
            $table->decimal('quantity_accepted', 20, 10);
            $table->decimal('quantity_rejected', 20, 10)->default(0);
            $table->uuid('uom_id');
            $table->decimal('unit_cost', 20, 6);
            $table->uuid('batch_id')->nullable();
            $table->json('serial_numbers')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->foreign('receipt_id')->references('id')->on('purchase_receipts');
            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->onDelete('cascade');
            $table->foreign('purchase_order_item_id')->references('id')->on('purchase_order_items');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants');
            $table->foreign('uom_id')->references('id')->on('uoms');
            $table->foreign('batch_id')->references('id')->on('batches');
            $table->index(['goods_receipt_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_receipt_items');
    }
}