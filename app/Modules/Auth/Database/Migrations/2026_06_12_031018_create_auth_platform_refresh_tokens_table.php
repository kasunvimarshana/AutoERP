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
            $table->unsignedBigInteger('access_token_id');
            $table->unsignedBigInteger('parent_refresh_token_id')->nullable();
            $table->uuid('family_id');
            $table->unsignedBigInteger('platform_session_id');
            $table->unsignedBigInteger('platform_operator_id');
            $table->string('refresh_key', 64);
            $table->string('refresh_digest', 64);
            $table->string('status', 30);
            $table->dateTime('issued_at');
            $table->dateTime('expires_at');
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 255)->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->foreign(['access_token_id', 'platform_session_id', 'platform_operator_id'], 'auth_plat_refresh_access_fk')
                ->references(['id', 'platform_session_id', 'platform_operator_id'])->on('auth_platform_access_tokens')->restrictOnDelete();
            $table->foreign('parent_refresh_token_id', 'auth_plat_refresh_parent_fk')
                ->references('id')->on('auth_platform_refresh_tokens')->nullOnDelete();
            $table->foreign(['platform_session_id', 'platform_operator_id'], 'auth_plat_refresh_session_fk')
                ->references(['id', 'platform_operator_id'])->on('auth_platform_sessions')->restrictOnDelete();
            $table->unique('refresh_key', 'auth_plat_refresh_key_uk');
            $table->index(['family_id', 'status'], 'auth_plat_refresh_family_ix');
            $table->index(['platform_session_id', 'status', 'expires_at'], 'auth_plat_refresh_status_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_platform_refresh_tokens');
    }
};
