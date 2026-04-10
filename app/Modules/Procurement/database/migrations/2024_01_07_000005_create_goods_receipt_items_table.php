<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity_received', 15, 4);
            $table->decimal('quantity_accepted', 15, 4)->default(0);
            $table->decimal('quantity_rejected', 15, 4)->default(0);
            $table->foreignId('batch_id')->nullable()->constrained();
            $table->date('expiry_date')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }
};