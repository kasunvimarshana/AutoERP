<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_login_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'auth_login_attempts_tenant_fk')->restrictOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('login_identifier_hash', 64);
            $table->boolean('was_successful');
            $table->string('failure_code', 80)->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent', 1024)->nullable();
            $table->dateTime('attempted_at');
            $table->timestamps();

            $table->foreign(['user_id', 'tenant_id'], 'auth_login_user_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'login_identifier_hash', 'attempted_at'], 'auth_login_account_ix');
            $table->index(['tenant_id', 'ip_address', 'attempted_at'], 'auth_login_ip_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_login_attempts');
    }
};
