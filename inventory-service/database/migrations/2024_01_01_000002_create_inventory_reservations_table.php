<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_id')->index();
            $table->uuid('product_id')->index();
            $table->integer('quantity');
            $table->enum('status', ['pending', 'confirmed', 'released'])->default('pending');
            $table->uuid('saga_id')->index();
            $table->timestamps();

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
