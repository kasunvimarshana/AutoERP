<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_identities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('user_id');
            $table->string('provider_user_key', 190);
            $table->string('status', 30);
            $table->string('primary_marker', 20)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_authenticated_at')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'auth_identity_id_tenant_uk');
            $table->foreign(['provider_id', 'tenant_id'], 'auth_identity_provider_fk')
                ->references(['id', 'tenant_id'])->on('auth_providers')->restrictOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'auth_identity_user_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->unique(['tenant_id', 'provider_id', 'provider_user_key'], 'auth_identity_subject_uk');
            $table->unique(['tenant_id', 'user_id', 'primary_marker'], 'auth_identity_primary_uk');
            $table->index(['tenant_id', 'user_id', 'status'], 'auth_identity_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_identities');
    }
};
