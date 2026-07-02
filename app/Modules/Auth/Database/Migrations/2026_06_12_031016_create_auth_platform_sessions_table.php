<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_platform_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id');
            $table->foreignId('platform_operator_id')->constrained('platform_operators', indexName: 'auth_platform_sessions_operator_fk')->restrictOnDelete();
            $table->string('status', 30);
            $table->string('ip_address', 45);
            $table->string('user_agent', 1024)->nullable();
            $table->string('device_name', 160)->nullable();
            $table->dateTime('authenticated_at');
            $table->dateTime('last_activity_at');
            $table->dateTime('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 255)->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique('public_id', 'auth_plat_session_public_uk');
            $table->unique(['id', 'platform_operator_id'], 'auth_plat_session_graph_uk');
            $table->index(['platform_operator_id', 'status', 'expires_at'], 'auth_plat_session_status_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_platform_sessions');
    }
};
