<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('guard_name')->default('api');
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('roles');
    }
};
