<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_platform_refresh_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('access_token_id');
            $table->unsignedBigInteger('platform_session_id');
            $table->unsignedBigInteger('platform_operator_id');
            $table->string('refresh_key', 120);
            $table->string('refresh_hash');
            $table->boolean('rotated')->default(false);
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('replaced_by_expires_at')->nullable();
            $table->string('status', 40)->default('active');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('access_token_id', 'auth_platform_refresh_access_fk')
                ->references('id')->on('auth_platform_access_tokens')->cascadeOnDelete();
            $table->foreign(['platform_session_id', 'platform_operator_id'], 'auth_platform_refresh_session_operator_fk')
                ->references(['id', 'platform_operator_id'])->on('auth_platform_sessions')->cascadeOnDelete();
            $table->unique('refresh_key', 'auth_platform_refresh_tokens_key_uk');
            $table->index(['platform_operator_id', 'status'], 'auth_platform_refresh_operator_status_idx');
            $table->index(['platform_session_id', 'status'], 'auth_platform_refresh_session_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_platform_refresh_tokens');
    }
};
