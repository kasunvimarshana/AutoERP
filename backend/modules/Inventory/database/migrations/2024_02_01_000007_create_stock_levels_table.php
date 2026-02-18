<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('on_hand', 15, 2)->default(0);
            $table->decimal('reserved', 15, 2)->default(0);
            $table->decimal('available', 15, 2)->default(0); // on_hand - reserved
            $table->decimal('on_order', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'product_id', 'warehouse_id', 'location_id', 'batch_number', 'serial_number'], 'stock_level_unique');
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
