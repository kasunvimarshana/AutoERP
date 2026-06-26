<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_platform_login_attempts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('platform_operator_id')->nullable();
            $table->string('login_identifier_hash', 64);
            $table->boolean('was_successful');
            $table->string('failure_code', 80)->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent', 1024)->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->foreign('platform_operator_id', 'auth_plat_login_operator_fk')
                ->references('id')->on('platform_operators')->restrictOnDelete();
            $table->index(['login_identifier_hash', 'attempted_at'], 'auth_plat_login_account_idx');
            $table->index(['ip_address', 'attempted_at'], 'auth_plat_login_ip_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_platform_login_attempts');
    }
};
