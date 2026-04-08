<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique()->nullable();
            $table->string('logo_path')->nullable();
            $table->json('database_config')->nullable();
            $table->json('mail_config')->nullable();
            $table->json('cache_config')->nullable();
            $table->json('queue_config')->nullable();
            $table->json('feature_flags')->nullable();
            $table->json('api_keys')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenants');
    }
};
