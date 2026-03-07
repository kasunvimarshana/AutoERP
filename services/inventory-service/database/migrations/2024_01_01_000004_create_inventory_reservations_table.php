<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_item_id');
            $table->string('order_id')->index();
            $table->string('saga_id')->index();
            $table->integer('quantity');
            $table->enum('status', ['pending', 'confirmed', 'released', 'fulfilled'])
                  ->default('pending')
                  ->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('inventory_item_id')
                ->references('id')
                ->on('inventory_items')
                ->onDelete('cascade');

            $table->index(['saga_id', 'order_id']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
