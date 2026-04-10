<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSerialNumbersTable extends Migration
{
    public function up()
    {
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->string('serial')->unique();
            $table->string('serial_number')->unique();
            $table->uuid('product_id');
            $table->uuid('batch_id')->nullable();
            $table->foreignId('lot_id')->nullable()->constrained();
            // $table->enum('current_status', ['in_stock', 'reserved', 'sold', 'returned', 'scrapped'])->default('in_stock');
            // $table->uuid('location_id')->nullable();
            $table->uuid('current_location_id')->nullable();
            $table->enum('status', ['in_stock', 'reserved', 'shipped', 'returned', 'damaged', 'scrapped', 'in_transit']);
            $table->uuid('last_movement_id')->nullable();
            $table->timestamps();
            $table->uuid('last_transaction_id')->nullable();
            $table->timestamp('last_movement_at')->nullable();
            $table->json('history')->nullable();
            $table->timestamps();
            
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('batch_id')->references('id')->on('batches');
            // $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('current_location_id')->references('id')->on('locations');
            $table->index(['serial_number', 'status', 'product_id']);
            $table->index(['batch_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('serial_numbers');
    }
}