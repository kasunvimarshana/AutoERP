<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id'); $table->decimal('quantity',15,4); 
            $table->decimal('unit_cost',15,4); $table->decimal('total_cost',15,4);
            $table->string('notes')->nullable(); $table->index('purchase_order_id');
        });
    }
    public function down(): void { Schema::dropIfExists('purchase_order_lines'); }
};
