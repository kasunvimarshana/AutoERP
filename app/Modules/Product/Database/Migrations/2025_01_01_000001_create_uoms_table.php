<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUomsTable extends Migration
{
    public function up()
    {
        Schema::create('uoms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 10)->unique();            // EA, BOX, KG
            $table->string('name');                          // Each, Box, Kilogram
            $table->string('symbol')->nullable();
            $table->enum('category', ['quantity', 'weight', 'volume', 'length', 'area', 'time']);
            $table->unsignedTinyInteger('decimal_places')->default(0);
            $table->boolean('is_base')->default(false);
            $table->uuid('base_uom_id')->nullable();
            $table->decimal('conversion_factor', 20, 10)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('base_uom_id')->references('id')->on('uoms');
            $table->index('code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('uoms');
    }
}