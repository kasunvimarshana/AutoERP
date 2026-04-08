<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Domain\ValueObjects\UserStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('password', 255);
            $table->string('remember_token', 100)->nullable();
            $table->enum('status', [
                UserStatus::ACTIVE,
                UserStatus::INACTIVE,
                UserStatus::SUSPENDED,
                UserStatus::PENDING,
            ])->default(UserStatus::ACTIVE);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('avatar_path')->nullable();
            $table->json('preferences')->nullable();
            $table->string('locale', 10)->default('en');
            $table->string('timezone', 50)->default('UTC');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
