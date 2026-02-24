<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_goods_receipt_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('goods_receipt_id')->index();
            $table->uuid('purchase_order_line_id')->index();
            $table->uuid('product_id')->nullable()->index();
            $table->decimal('qty_received', 18, 8)->default(0);
            $table->decimal('qty_accepted', 18, 8)->default(0);
            $table->decimal('qty_rejected', 18, 8)->default(0);
            $table->string('rejection_reason')->nullable();
            $table->string('lot_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->uuid('location_id')->nullable()->index();
        });
    }
    public function down(): void { Schema::dropIfExists('purchase_goods_receipt_lines'); }
};
