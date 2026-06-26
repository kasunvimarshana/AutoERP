<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache_locks', function (Blueprint $table): void {
            $table->string('key')->primary('cache_locks_key_pk');
            $table->string('owner');
            $table->integer('expiration')->index('cache_locks_expiration_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
    }
};
