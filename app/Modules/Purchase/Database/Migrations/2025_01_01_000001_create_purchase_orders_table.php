<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('po_number')->unique();
            $table->uuid('supplier_id');
            $table->uuid('warehouse_id');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->enum('status', ['draft', 'sent', 'confirmed', 'shipped', 'partially_received', 'received', 'cancelled', 'closed']);
            $table->decimal('subtotal', 20, 6);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('shipping_cost', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6);
            $table->uuid('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('terms')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->index(['po_number', 'status', 'supplier_id']);
            $table->index(['order_date', 'expected_delivery_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
}