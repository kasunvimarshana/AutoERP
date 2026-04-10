<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsTable extends Migration
{
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->string('code');
            $table->string('name');
            $table->enum('type', ['zone', 'aisle', 'rack', 'shelf', 'bin', 'pallet']);
            $table->uuid('parent_id')->nullable();
            $table->json('dimensions')->nullable();
            $table->decimal('max_weight', 20, 6)->nullable();
            $table->decimal('max_volume', 20, 6)->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('level')->nullable();    // A1, B2
            $table->boolean('is_active')->default(true);
            $table->json('attributes')->nullable();
            $table->json('dimensions')->nullable(); // length, width, height
            $table->timestamps();
            
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('locations')->onDelete('cascade');
            $table->unique(['warehouse_id', 'code']);
            $table->index(['warehouse_id', 'type', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('locations');
    }
}