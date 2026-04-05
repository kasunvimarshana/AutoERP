<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id'); $table->decimal('quantity_ordered',15,4);
            $table->decimal('quantity_received',15,4); $table->decimal('unit_cost',15,4)->default(0);
            $table->string('batch_number')->nullable(); $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable(); $table->text('notes')->nullable();
            $table->index('goods_receipt_id');
        });
    }
    public function down(): void { Schema::dropIfExists('goods_receipt_lines'); }
};
