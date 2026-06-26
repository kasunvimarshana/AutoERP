<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_authorization_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('user_id');
            $table->string('code_key', 64);
            $table->string('code_digest', 64);
            $table->json('scopes');
            $table->string('code_challenge', 128);
            $table->string('redirect_uri', 2048);
            $table->string('status', 30);
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->foreign(['client_id', 'tenant_id'], 'auth_code_client_fk')
                ->references(['id', 'tenant_id'])->on('auth_clients')->restrictOnDelete();
            $table->foreign(['session_id', 'tenant_id', 'user_id'], 'auth_code_session_fk')
                ->references(['id', 'tenant_id', 'user_id'])->on('auth_sessions')->restrictOnDelete();
            $table->unique('code_key', 'auth_code_key_uk');
            $table->index(['tenant_id', 'client_id', 'status', 'expires_at'], 'auth_code_client_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_authorization_codes');
    }
};
