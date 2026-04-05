<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id'); $table->decimal('quantity',15,4);
            $table->decimal('unit_price',15,4); $table->decimal('tax_rate',5,2)->default(0);
            $table->decimal('discount_percent',5,2)->default(0); $table->decimal('line_total',15,4);
            $table->string('notes')->nullable(); $table->index('sales_order_id');
        });
    }
    public function down(): void { Schema::dropIfExists('sales_order_lines'); }
};
