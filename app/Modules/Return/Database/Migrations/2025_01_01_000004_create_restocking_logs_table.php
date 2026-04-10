<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestockingLogsTable extends Migration
{
    public function up()
    {
        Schema::create('restocking_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('return_item_id');
            $table->uuid('inventory_transaction_id');
            $table->decimal('quantity_restocked', 20, 10);
            $table->decimal('unit_cost', 20, 6);
            $table->json('valuation_layer_info')->nullable();
            $table->timestamps();
            
            $table->foreign('return_item_id')->references('id')->on('return_items')->onDelete('cascade');
            $table->foreign('inventory_transaction_id')->references('id')->on('inventory_transactions');
            $table->index(['return_item_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('restocking_logs');
    }
}