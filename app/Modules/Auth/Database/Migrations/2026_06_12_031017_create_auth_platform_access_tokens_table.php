<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_platform_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('platform_session_id');
            $table->unsignedBigInteger('user_id');
            $table->string('token_key', 120);
            $table->string('token_hash');
            $table->json('scopes')->nullable();
            $table->string('grant_type', 80)->nullable();
            $table->string('status', 40)->default('active');
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['platform_session_id', 'user_id'], 'auth_platform_access_session_user_fk')
                ->references(['id', 'user_id'])
                ->on('auth_platform_sessions')
                ->cascadeOnDelete();
            $table->unique('token_key', 'auth_platform_access_tokens_key_uk');
            $table->index(['user_id', 'status'], 'auth_platform_access_user_status_idx');
            $table->index(['platform_session_id', 'status'], 'auth_platform_access_session_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_platform_access_tokens');
    }
};
