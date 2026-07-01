<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_user_password_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'auth_user_password_credentials_tenant_fk')->restrictOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('password_hash');
            $table->string('status', 30);
            $table->dateTime('changed_at');
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'auth_user_credentials_id_tenant_uk');
            $table->unique(['tenant_id', 'user_id'], 'auth_user_credential_user_uk');
            $table->foreign(['user_id', 'tenant_id'], 'auth_user_credential_user_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'status'], 'auth_user_credential_status_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_user_password_credentials');
    }
};
