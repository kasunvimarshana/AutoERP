<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary('cache_key_pk');
            $table->mediumText('value');
            $table->integer('expiration')->index('cache_expiration_ix');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary('cache_locks_key_pk');
            $table->string('owner');
            $table->integer('expiration')->index('cache_locks_expiration_ix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
