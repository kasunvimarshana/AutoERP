<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('delivery_number', 50)->unique();
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->enum('status', ['draft', 'picked', 'packed', 'shipped', 'delivered', 'cancelled'])->default('draft');
            $table->date('ship_date')->nullable();
            $table->string('carrier', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['tenant_id', 'sales_order_id', 'status']);
            $table->index('delivery_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};