<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->text('description')->nullable();
            $table->string('module', 100)->nullable();
            $table->string('guard_name', 50)->default('api');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['slug', 'guard_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
