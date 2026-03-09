<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('product_id', 36);
            $table->string('warehouse_id', 36);
            $table->string('product_name', 255)->default('');
            $table->string('product_sku', 100)->default('');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 4)->default(0);
            $table->decimal('total_price', 12, 4)->default(0);
            $table->timestamps();
            $table->index('order_id');
            $table->index('product_id');
        });
    }
    public function down(): void { Schema::dropIfExists('order_items'); }
};
