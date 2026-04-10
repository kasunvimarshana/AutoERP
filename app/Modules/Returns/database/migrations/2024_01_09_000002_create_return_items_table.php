<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('original_order_item_id')->nullable();
            $table->decimal('quantity_returned', 15, 4);
            $table->decimal('quantity_accepted', 15, 4)->default(0);
            $table->decimal('quantity_rejected', 15, 4)->default(0);
            $table->foreignId('batch_id')->nullable()->constrained();
            $table->foreignId('lot_id')->nullable()->constrained();
            $table->foreignId('serial_id')->nullable()->constrained('serials');
            $table->enum('condition', ['good', 'damaged', 'expired', 'defective'])->default('good');
            $table->enum('action', ['restock', 'scrap', 'repair', 'return_to_vendor', 'liquidate'])->default('restock');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('refund_amount', 15, 2);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }
};