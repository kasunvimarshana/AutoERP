<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('client_key', 120);
            $table->string('client_name', 180);
            $table->string('client_secret_hash')->nullable();
            $table->string('status', 30);
            $table->json('allowed_grant_types');
            $table->json('allowed_scopes');
            $table->json('redirect_uris');
            $table->boolean('is_confidential');
            $table->boolean('is_first_party');
            $table->timestamp('secret_rotated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'auth_client_id_tenant_uk');
            $table->unique(['tenant_id', 'client_key'], 'auth_client_key_uk');
            $table->index(['tenant_id', 'status', 'expires_at'], 'auth_client_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_clients');
    }
};
