<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'auth_access_tokens_tenant_fk')->restrictOnDelete();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('token_key', 64);
            $table->string('token_digest', 64);
            $table->json('scopes');
            $table->string('grant_type', 40);
            $table->string('status', 30);
            $table->dateTime('issued_at');
            $table->dateTime('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 255)->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->foreign(['session_id', 'tenant_id', 'user_id'], 'auth_access_session_fk')
                ->references(['id', 'tenant_id', 'user_id'])->on('auth_sessions')->restrictOnDelete();
            $table->foreign(['client_id', 'tenant_id'], 'auth_access_client_fk')
                ->references(['id', 'tenant_id'])->on('auth_clients')->restrictOnDelete();
            $table->unique('token_key', 'auth_access_key_uk');
            $table->unique(['id', 'tenant_id'], 'auth_access_tokens_id_tenant_uk');
            $table->unique(['id', 'tenant_id', 'session_id', 'user_id'], 'auth_access_graph_uk');
            $table->index(['session_id', 'status', 'expires_at'], 'auth_access_session_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_access_tokens');
    }
};
