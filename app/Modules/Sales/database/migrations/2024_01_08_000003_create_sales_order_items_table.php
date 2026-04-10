<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity_ordered', 15, 4);
            $table->decimal('quantity_allocated', 15, 4)->default(0);
            $table->decimal('quantity_picked', 15, 4)->default(0);
            $table->decimal('quantity_shipped', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('line_total', 15, 2)->virtualAs('quantity_ordered * unit_price');
            $table->foreignId('uom_id')->constrained('uoms');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }
};