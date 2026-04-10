<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGs1CodesTable extends Migration
{
    public function up()
    {
        Schema::create('gs1_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('gtin')->unique();
            $table->string('sscc')->nullable()->unique();
            $table->string('gln')->nullable();
            $table->morphs('reference');
            $table->json('additional_data')->nullable();
            $table->timestamps();
            
            $table->index(['gtin', 'sscc']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('gs1_codes');
    }
}