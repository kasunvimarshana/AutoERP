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
            $table->unsignedBigInteger('platform_session_id');
            $table->unsignedBigInteger('platform_operator_id');
            $table->string('token_key', 64);
            $table->string('token_digest', 64);
            $table->json('scopes');
            $table->string('grant_type', 40);
            $table->string('status', 30);
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 255)->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->foreign(['platform_session_id', 'platform_operator_id'], 'auth_plat_access_session_fk')
                ->references(['id', 'platform_operator_id'])->on('auth_platform_sessions')->restrictOnDelete();
            $table->unique('token_key', 'auth_plat_access_key_uk');
            $table->unique(['id', 'platform_session_id', 'platform_operator_id'], 'auth_plat_access_graph_uk');
            $table->index(['platform_session_id', 'status', 'expires_at'], 'auth_plat_access_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_platform_access_tokens');
    }
};
