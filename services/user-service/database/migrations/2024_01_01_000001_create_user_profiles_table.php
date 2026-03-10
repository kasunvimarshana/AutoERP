<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create user_profiles table (User Service)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('auth_user_id')->comment('Cross-service Auth Service user ID');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('avatar_url')->nullable();
            $table->json('address')->nullable();
            $table->json('preferences')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('timezone')->default('UTC');
            $table->string('locale')->default('en');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'auth_user_id']);
            $table->unique(['tenant_id', 'email']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
