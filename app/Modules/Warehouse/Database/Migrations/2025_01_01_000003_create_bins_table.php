<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBinsTable extends Migration
{
    public function up()
    {
        Schema::create('bins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('location_id');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->string('code');
            $table->decimal('capacity', 20, 10)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bins');
    }
}