<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('keycloak_id', 255)->unique()->nullable();
            $table->string('email', 255)->unique();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('username', 100)->unique()->nullable();
            $table->json('roles')->nullable()->comment('Array of role strings, e.g. ["admin","manager"]');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->json('preferences')->nullable()->comment('Arbitrary user preference key/value pairs');
            $table->string('avatar_url', 500)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('department', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('keycloak_id');
            $table->index('email');
            $table->index('is_active');
            $table->index('department');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
