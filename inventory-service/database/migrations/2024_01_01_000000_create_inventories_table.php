<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // An append-only ledger for inventory tracking.
        // It provides an immutable history of stock changes.
        Schema::create('inventory_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('External Product ID from product-service');
            $table->string('transaction_type')->comment('E.g., INITIALIZE, RESTOCK, REDUCE, RESERVE, CANCEL_RESERVATION');
            $table->integer('quantity_change')->comment('Positive or Negative change');
            $table->string('reference_id')->nullable()->comment('Saga ID or Order ID for tracebility');
            $table->timestamps();
        });

        // Current materialized view/summary for fast read.
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique();
            $table->integer('available_stock')->default(0);
            $table->integer('reserved_stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('inventory_ledgers');
    }
};
