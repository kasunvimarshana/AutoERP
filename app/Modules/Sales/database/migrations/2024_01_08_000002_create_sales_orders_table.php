<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->string('so_number')->unique();
            $table->date('order_date');
            $table->date('due_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->enum('status', [
                'draft', 'confirmed', 'allocated', 'picking', 
                'picked', 'packing', 'packed', 'shipped', 'delivered', 'cancelled'
            ])->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->unique(['company_id', 'so_number']);
        });
    }
};