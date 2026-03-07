<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique()->index();
            $table->string('product_sku')->unique()->index();
            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('minimum_quantity')->default(10);
            $table->string('location')->nullable();
            $table->enum('status', ['available', 'low_stock', 'out_of_stock', 'reserved'])->default('out_of_stock')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('last_restock_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
